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
 * 悬赏 用户报名任务 表
 */
Class RewardGet extends BaseModel{
    /**
     * 数据表主键
     * @var string
     */
    protected $pk = 'id';

    /**
     * 模型名称
     * @var string
     */
    protected $name = 'task_get';

    use ModelTrait;


    /*
     * 用户所有报名接受的任务
     */
    public static function getMyGetTaskList($uid,$status,$lastId){
        if($status>0){
            $where[]=[['task_get.status','=',$status]];
        }
        if($lastId){
            $where[]=[['task_get.id','<',$lastId]];
        }
        $where[]=[['task_get.uid','=',$uid]];
        $list=Db::view("task_get","id as task_get_id,order_sn,task_put_id,status,creatime,uptime,savetime")
                  ->view("task_put","title,logo_url,per_price","task_get.task_put_id=task_put.id")
                  ->where($where)->order("task_get.creatime","desc")->limit(10)
                  ->select()->toArray();
        foreach ($list as $key=>$value){
            $list[$key]["word"]=self::taskWord($value["status"]);
            $list[$key]["remark"]=self::taskWord($value["status"]);
            $list[$key]["creatime"]=date("Y-m-d H:i:s",$list[$key]["creatime"]);
            if($list[$key]["status"]==2){
                $list[$key]["savetime"]=date("Y-m-d H:i:s",$list[$key]["uptime"]);
            }else{
                $list[$key]["savetime"]=date("Y-m-d H:i:s",$list[$key]["savetime"]);
            }
            unset($list[$key]["paytime"]);
        }
        return $list;
    }

    /*
     * 状态文字
     */
    private static function taskWord($status){
        $msg="";
        switch ($status){
            case 1:
                $msg="已报名";
                break;
            case 2:
                $msg="待审核";
                break;
            case 3:
                $msg="未通过";
                break;
            case 4:
                $msg="申述中";
                break;
            case 5:
                $msg="已通过";
                break;
            case 6:
                $msg="已超时";
                break;
            case 7:
                $msg="已取消";
                break;
        }
        return $msg;
    }

    /*
         * 文字描述
         */
    public static function taskRemark($status){
        $msg="";
        switch ($status){
            case 1:
                $msg="已报名";
                break;
            case 2:
                $msg="待审核";
                break;
            case 3:
                $msg="未通过";
                break;
            case 4:
                $msg="申述中";
                break;
            case 5:
                $msg="已通过";
                break;
            case 6:
                $msg="已超时";
                break;
            case 7:
                $msg="已取消";
                break;
        }
        return $msg;
    }



    /*
     * 用户报名任务
     */
    public static function getTheTask($uid,$taskPutId){
        $res["back"]=false;
        $res["msg"]="";

        // 启动事务
        Db::startTrans();
        try {
            $taskInfo=Db::name("task_put")->where("id",$taskPutId)->lock(true)->find();
            if($uid==$taskInfo["uid"]){
                Db::rollback();
                $res["msg"]="不能报名自己的任务";
                return $res;
            }
            if($taskInfo["done_num"]==$taskInfo["total_num"]){
                Db::rollback();
                $res["msg"]="悬赏已完成";
                return $res;
            }
            if($taskInfo["get_num"]==$taskInfo["total_num"]){
                Db::rollback();
                $res["msg"]="截止报名";
                return $res;
            }
            //暂时:已成功一次则不允许报名一次
            $has=self::where(array("uid"=>$uid,"task_put_id"=>$taskPutId,"status"=>5))->find();
            if($has){
                Db::rollback();
                $res["msg"]="该任务已完成过";
                return $res;
            }
            $get=[
                "uid"=>$uid,
                "task_put_uid"=>$taskInfo["uid"],
                "task_put_id"=>$taskPutId,
                "order_sn"=>getOrderSn(),
                "status"=>1,
                "creatime"=>time()
            ];
            $id=self::insertGetId($get);
            if(!$id){
                Db::rollback();
                $res["msg"]="报名失败";
                return $res;
            }
            Db::name("task_put")->where("id",$taskPutId)->inc("get_num",1)->update();
            Db::commit();
            $res["back"]=true;
            $res["msg"]="报名成功";
            $res["task_get_id"]=$id;
            return $res;

        }catch (\Exception $e) {
            Db::rollback();
            $res["msg"]="服务器开小差";
            return $res;
        }
    }
    /*
     * 用户上传任务完成数据
     */
    public static function upTaskData($uid,$taskGetId,$taskPutId,$up){
        $res["back"]=false;
        $res["msg"]="";

        //stepID是否存在
        foreach ($up as $k=>$v){
            $is=RewardStep::where("id","=",$v["id"])->find();
            if(!$is){
               $res["msg"]="步骤错误";
               return  $res;
            }
        }

        $list=[];
        //上传任务数据
        foreach ($up as $key=>$value){
            $list[$key]["task_get_id"]=$taskGetId;//报名任务ID
            $list[$key]["task_get_uid"]=$uid;//接单人
            $list[$key]["task_put_id"]=$taskPutId;//任务ID
            $list[$key]["task_step_id"]=$value["id"];//步骤ID
            $list[$key]["value"]=$value["value"];
            $list[$key]["creatime"]=time();
            $list[$key]["savetime"]=time();
        }


        $taskInfo=Reward::where("id",$taskPutId)->find();

        if(!$taskInfo){
            $res["msg"]="任务不存在";
            return $res;
        }

        // 启动事务
        Db::startTrans();
        try {

            $getInfo=self::where(array("id"=>$taskGetId,"uid"=>$uid,"task_put_id"=>$taskPutId))->lock(true)->find();

            if(!$getInfo){
                Db::rollback();
                $res["msg"]="用户接取任务传参错误";
                return $res;
            }

            if($getInfo["status"]==2){
                Db::rollback();
                $res["msg"]="审核中";
                return $res;
            }

            if($taskInfo["sub_time"]>0){
                $over=time()-$getInfo["creatime"];
                $isOver=$over-$taskInfo["sub_time"]*3600;
                if($isOver>0){
                    self::where(array("id"=>$taskGetId,"uid"=>$uid,"task_put_id"=>$taskPutId))->update(array("status"=>6));
                    Db::rollback();
                    $res["msg"]="已超时";
                    return $res;
                }
            }

            $upload=RewardUpStep::insertAll($list);
            if(!$upload){
                Db::rollback();
                $res["msg"]="上传失败";
                return $res;
            }

            $update=self::where(array("id"=>$taskGetId,"uid"=>$uid,"task_put_id"=>$taskPutId))->update(array("uptime"=>time(),"status"=>2));
            if(!$update){
                $res["msg"]="任务更新失败";
                return $res;
            }
            Db::commit();
            $res["back"]=true;
            $res["msg"]="上传成功";
            return $res;
        }catch (\Exception $e) {
            Db::rollback();
            $res["msg"]="服务器开小差";
            return $res;
        }

    }

    /*
     * 用户取消任务
     */
    public static function undoTask($uid,$taskGetId,$taskPutId){
        //取消任务，改状态，减报名人数
        $res["back"]=false;
        $res["msg"]="";

        // 启动事务
        Db::startTrans();
        try {

            $getInfo=Db::name("task_get")->where([["id","=",$taskGetId],["uid","=",$uid],["task_put_id","=",$taskPutId],["status","in",[1,2]]])->find();
            if(!$getInfo){
                Db::rollback();
                $res["msg"]="任务已处理";
                return $res;
            }

            $taskInfo=Db::name("task_put")->where("id",$taskPutId)->lock(true)->find();
            if($taskInfo["get_num"]<=0){
                Db::rollback();
                $res["msg"]="任务出错";
                return $res;
            }

            $off=Db::name("task_put")->where("id",$taskPutId)->dec("get_num",1)->update();
            if(!$off){
                Db::rollback();
                $res["msg"]="请求错误";
                return $res;
            }

            $update=self::where(array("id"=>$taskGetId,"uid"=>$uid,"task_put_id"=>$taskPutId))->update(array("savetime"=>time(),"status"=>7));
            if(!$update){
                Db::rollback();
                $res["msg"]="取消失败";
                return $res;
            }
            Db::commit();
            $res["back"]=true;
            $res["msg"]="取消成功";
            return $res;
        }catch (\Exception $e) {
            Db::rollback();
            $res["msg"]="服务器开小差";
            return $res;
        }

    }


}