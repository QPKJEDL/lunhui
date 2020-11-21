<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020-11-11
 * Time: 8:45
 */
namespace app\models\reward;
use crmeb\traits\ModelTrait;
use crmeb\basic\BaseModel;
use think\facade\Db;

/*
 * 悬赏 发布任务
 */
Class Reward extends BaseModel{
    /**
     * 数据表主键
     * @var string
     */
    protected $pk = 'id';

    /**
     * 模型名称
     * @var string
     */
    protected $name = 'task_put';

    use ModelTrait;


    /*
     * 获取所有发布的任务
     */
    public static function getTaskList($lastId=0){
        if($lastId){
            $where[]=[['id','<',$lastId]];
        }
        $where []=[['status','=',0]];
        $list=self::where($where)->field('id as task_put_id,logo_url,order_sn as task_put_order_sn,title,plat_name,sub_time,check_time,total_num,done_num,per_price')->order("creatime","desc")->limit(20)->select()->toArray();
        return $list;
    }

    /*
     * 任务编号搜索获取指定任务
     */
    public static function getTheTask($orderSn){
        $info=self::where("order_sn",$orderSn)->field('id as task_put_id,logo_url,order_sn as task_put_order_sn,title,plat_name,sub_time,check_time,total_num,done_num,per_price')->find()->toArray();
        return $info;
    }

    /*
     * 获取任务详情
     */
    public static function getTaskInfo($taskId){
        $info=self::where("id",$taskId)->field('id as task_put_id,uid,logo_url,order_sn as task_put_order_sn,title,plat_name,sub_time,check_time,total_num,done_num,per_price,step')->find()->toArray();
        $info["per_price"]=$info["per_price"]/100;
        return $info;
    }

    /*
     * 商家的主页，任务列表
     */
    public static function getPutHomeList($uid,$lastId){
        $userInfo=Db::name("user")->where("uid",$uid)->field("account,avatar")->find();
        $userInfo["account"]=substr_replace($userInfo["account"],'****',3,4);

        if($lastId){
            $where[]=[['id','<',$lastId]];
        }
        $where []=[['status','=',0],["uid","=",$uid]];
        $list=self::where($where)->field('id as task_put_id,logo_url,order_sn as task_put_order_sn,title,plat_name,sub_time,check_time,total_num,done_num,per_price')->order("creatime","desc")->limit(20)->select()->toArray();

        $data["user"]=$userInfo;
        $data["list"]=$list;
        return $data;
    }
    /*
     * 商家中心，数据统计
     */
    public static function getPutData($uid){
        $sql="select COUNT(CASE WHEN `status` = 1 THEN 0 END) AS s1,
                    COUNT(CASE WHEN `status` = 2 THEN 0 END) AS s2,
                    COUNT(CASE WHEN `status` = 3 THEN 0 END) AS s3,
                    COUNT(CASE WHEN `status` = 4 THEN 0 END) AS s4,
                    COUNT(CASE WHEN `status` = 6 THEN 0 END) AS s5,
                    COUNT(CASE WHEN `status` = 5 THEN 0 END) AS s6
            from eb_task_put
            where uid=:uid            
            ";
        $data=Db::query($sql,["uid"=>$uid]);
        return $data;
    }

    public static function getMyPutTask($uid,$status,$lastId){
        if($status>0){
            $where[]=[['status','=',$status]];
        }
        if($lastId){
            $where[]=[['id','<',$lastId]];
        }
        $where[]=[['uid','=',$uid]];
        $list=self::where($where)->field('id as task_put_id,logo_url,order_sn as task_put_order_sn,title,plat_name,sub_time,check_time,total_num,done_num,per_price')->order("creatime","desc")->limit(1)->select()->toArray();
        return $list;
    }


    /*
     * 商家发布任务，支付
     */
    public static function putTask($uid,$data){
        $res["back"]=false;
        $res["msg"]="";
        $fee=Db::name("admin_option")->where("key","=","taskFee")->value("value");

        $task_price=$data['task_price']*100;
        $per_price=$task_price-$task_price*$fee/100;
        $task_people=(int)$data['task_people'];
        $allMoney=$per_price*$task_people/100;


        if($task_price<0||$per_price<0||$allMoney<0){
            $res["msg"]="金额错误";
            return $res;
        }


        // 启动事务
        Db::startTrans();
        try {
            $balance=Db::name("user")->where("uid",$uid)->lock(true)->value("now_money");
            if($balance<$allMoney){
                Db::rollback();
                return "余额不足";
            }
            $pay=Db::name("user")->where("uid",$uid)->dec("now_money",$allMoney)->update();
            if(!$pay){
                Db::rollback();
                $res["msg"]="余额扣除失败";
                return $res;
            }
            $data["uid"]=$uid;
            $data["task_price"]=$task_price;
            $data["per_price"]=$per_price;
            $data["order_sn"]=getOrderSn();
            $data["status"]=5;//已付款
            $data["creatime"]=time();
            $data["paytime"]=time();

            $putId=self::insertGetId($data);
            if(!$putId){
                Db::rollback();
                $res["msg"]="支付失败";
                return $res;
            }

            $bill=[
                "uid"=>$uid,
                "link_id"=>$putId,
                "title"=>"悬赏任务",
                "category"=>"now_money",
                "type"=>"pay_task",
                "number"=>$allMoney,
                "balance"=>$balance-$allMoney,
                "mark"=>"支付".$allMoney."发布任务",
                "add_time"=>time(),

            ];
            $addBill=Db::name("user_bill")->insert($bill);

            if(!$addBill){
                Db::rollback();
                $res["msg"]="入账失败";
                return $res;
            }
            Db::commit();
            $res["back"]=$putId;
            $res["msg"]="支付成功";
            return $res;

        } catch (\Exception $e) {
            Db::rollback();
            $res["msg"]="服务器开小差";
            return $res;
        }
    }

    /*
     * 商家发布任务下的报名列表
     */
    public static function myPutGetList($uid,$taskPutId,$lastId){
        if($lastId){
            $where[]=[['id','<',$lastId]];
        }
        $where[]=[['task_put_uid','=',$uid],["task_put_id","=",$taskPutId]];
        $list=RewardGet::where($where)->order("creatime","desc")->limit(20)->withoutField("task_put_uid")->select()->toArray();
        foreach ($list as $key=>$value){
            $list[$key]["up"]=json_decode($list[$key]["up"],true);
        }
        return $list;
    }

    /*
     * 商家审核用户的任务,通过
     */
    public static function putCheckPassGet($uid,$taskPutId,$taskGetUid){
        //任务手续费
        $taskFee=Db::name("admin_option")->where("key","=","taskFee")->value("value");


        $res["back"]=false;
        $res["msg"]="";
        $taskInfo=self::where("id",$taskPutId)->find();

        //手续费
        $feeMoney=$taskInfo["per_price"]*$taskFee/10000;

        //单人实收金额
        $perMoney=$taskInfo["per_price"]/100-$feeMoney;

        //任务过期时间
        $overTime=$taskInfo["sub_time"]*3600;

        // 启动事务
        Db::startTrans();
        try {
            //是否已过期
            $taskGetInfo=Db::name("task_get")->where(array("uid"=>$taskGetUid,"task_put_id"=>$taskPutId,"status"=>2))->lock(true)->find();
            if(!$taskGetInfo){
                Db::rollback();
                $res["msg"]="数据错误";
                return $res;
            }
            $over=time()-$taskGetInfo["creatime"];
            $isOver=$over-$overTime;
            if($isOver>0){
                Db::rollback();
                $res["msg"]="已超时";
                return $res;
            }

            //报名用户当前余额
            $balance=Db::name("user")->where("uid",$taskGetUid)->value("now_money");

            //改变完成数量
            $done=Db::name("task_put")->where("id",$taskPutId)->inc("done_num",1)->update();
            if(!$done){
                Db::rollback();
                $res["msg"]="通过错误";
                return $res;
            }

            //给报名用户上级返佣
            //上级
            $parentId=Db::name("user")->where("uid",$taskGetUid)->value("spread_uid");
            if($parentId){
                //上级当前余额
                $parentBalance=Db::name("user")->where("uid",$parentId)->value("now_money");
                //上级账变
                $parAdd=Db::name("user")->where("uid",$parentId)->inc("now_money",$feeMoney)->update();
                if($parAdd){
                    Db::rollback();
                    $res["msg"]="请求错误";
                    return $res;
                }
                $parBill=[
                    "uid"=>$parentId,
                    "link_id"=>$taskGetInfo["id"],
                    "title"=>"任务完成奖励返佣",
                    "category"=>"now_money",
                    "type"=>"task_fee",
                    "number"=>$feeMoney,
                    "balance"=>$parentBalance+$feeMoney,
                    "mark"=>"获得".$feeMoney."任务返佣",
                    "add_time"=>time(),
                ];
                if(!$parBill){
                    Db::rollback();
                    $res["msg"]="服务错误";
                    return $res;
                }
            }

            //报名人员账变
            $add=Db::name("user")->where("uid",$taskGetUid)->inc("now_money",$perMoney)->update();
            if(!$add){
                Db::rollback();
                $res["msg"]="报名用户发放金额错误";
                return $res;
            }

            $bill=[
                "uid"=>$taskGetUid,
                "link_id"=>$taskPutId,
                "title"=>"悬赏任务完成奖励",
                "category"=>"now_money",
                "type"=>"done_task",
                "number"=>$perMoney,
                "balance"=>$balance+$perMoney,
                "mark"=>"获得".$perMoney."任务奖励",
                "add_time"=>time(),

            ];
            $addBill=Db::name("user_bill")->insert($bill);

            if(!$addBill){
                Db::rollback();
                $res["msg"]="报名用户入账失败";
                return $res;
            }
            //更新用户报名任务信息
            $data=[
                "task_money"=>$taskInfo["per_price"]/100,
                "fee_money"=>$feeMoney*100,
                "get_money"=>$perMoney*100,
                "status"=>5,
                "savetime"=>time()
            ];
            Db::name("task_get")->where(array("uid"=>$taskGetUid,"task_put_id"=>$taskPutId))->update($data);
            Db::commit();
            $res["back"]=true;
            $res["msg"]="通过成功";
            return $res;
        }catch (\Exception $e) {
            Db::rollback();
            $res["msg"]="服务器开小差";
            return $res;
        }
    }

    /*
     * 商家审核用户的任务,驳回
     */
    public static function putCheckRejectGet($uid,$taskPutId,$taskGetUid){
        $res["back"]=false;
        $res["msg"]="";
        $taskInfo=self::where("id",$taskPutId)->find();
        //任务过期时间
        $overTime=$taskInfo["sub_time"]*3600;
        // 启动事务
        Db::startTrans();
        try {
            //是否已过期
            $taskGetInfo=Db::name("task_get")->where(array("uid"=>$taskGetUid,"task_put_id"=>$taskPutId,"status"=>2))->lock(true)->find();
            if(!$taskGetInfo){
                Db::rollback();
                $res["msg"]="报名已处理";
                return $res;
            }
            $over=time()-$taskGetInfo["creatime"];
            $isOver=$over-$overTime;
            if($isOver>0){
                Db::rollback();
                $res["msg"]="已超时";
                return $res;
            }
            //改变报名数量
            $done=Db::name("task_put")->where("id",$taskPutId)->inc("get_num",1)->update();
            if(!$done){
                Db::rollback();
                $res["msg"]="驳回失败";
                return $res;
            }
            //更新用户报名任务信息
            $data=[
                "status"=>3,
                "savetime"=>time()
            ];
            Db::name("task_get")->where(array("uid"=>$taskGetUid,"task_put_id"=>$taskPutId))->update($data);
            Db::commit();
            $res["back"]=true;
            $res["msg"]="驳回成功";
            return $res;

        }catch (\Exception $e) {
            Db::rollback();
            $res["msg"]="服务器开小差";
            return $res;
        }
    }


    /*
     * 账单明细
     */
    public static function mybill($uid,$lastId,$date){
        if($lastId){
            $where[]=[['id','<',$lastId]];
        }
        $start=strtotime(date("Ymd"));
        if($date){
            $start=strtotime($date);
        }
        $end=$start+86400;
        $where[]=[['uid','=',$uid]];
        $list=Db::name("user_bill")->where($where)->whereTime("add_time","between",[$start,$end])->field("number,mark")->order("add_time","desc")->limit(20)->select()->toArray();

        return $list;
    }
}