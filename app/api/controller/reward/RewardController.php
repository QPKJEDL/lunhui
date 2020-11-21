<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020-11-11
 * Time: 8:43
 */
namespace app\api\controller\reward;
use app\models\reward\Reward;
use app\models\reward\RewardGet;
use think\exception\ValidateException;
use crmeb\services\UtilService;
use app\Request;
/*
 * 悬赏任务类
 */
Class RewardController{

    /*
     * 悬赏任务大厅 列表
     */
    public function lst(Request $request){
        $post = UtilService::postMore([
            ['lastid', 0]
        ], $request);
        $lastId=(int)$post["lastid"];
        $list=Reward::getTaskList($lastId);
        return app('json')->successful($list);
    }

    /*
     * 根据任务编号搜索
     */
    public function find(Request $request){
        $post = UtilService::postMore([
            ['order_sn', '']
        ], $request);
        $orderSn=$post["order_sn"];
        if($orderSn){
            $info=Reward::getTheTask($orderSn);
        }else{
            $info=Reward::getTaskList();
        }
        return app('json')->successful($info);
    }

    /*
     * 我接的任务
     */
    public function myGetTask(Request $request){
        $uid=8;
        $post = UtilService::postMore([
            ['status', 0],
            ['lastid', 0]
        ], $request);
        $status=(int)$post["status"];
        $lastId=(int)$post["lastid"];
        $list=RewardGet::getMyGetTaskList($uid,$status,$lastId);
        return app('json')->successful($list);
    }

    /*
     * 任务详情
     */
    public function taskInfo(Request $request){
        $post = UtilService::postMore([
            ['task_put_id', 0]
        ], $request);
        $taskId=$post["task_put_id"];
        $info=Reward::getTaskInfo($taskId);
        $info["step"]=json_decode($info["step"],true);
        return app('json')->successful($info);
    }

    /*
     *商家主页
     */
    public function thePutHomePage(Request $request){
        $post = UtilService::postMore([
            ['uid', 0],
            ['lastid', 0]
        ], $request);
        $uid=(int)$post["uid"];
        $lastId=(int)$post["lastid"];
        $info=Reward::getPutHomeList($uid,$lastId);
        return app('json')->successful($info);
    }

    /*
    * 商家中心，首页数据统计
    */
    public static function putData(Request $request){
        $uid=7;
        $data=Reward::getPutData($uid);
        return app('json')->successful($data);
    }

    /*
     * 商家的所有任务
     */
    public static function myPutTask(Request $request){
        $uid=7;
        $post = UtilService::postMore([
            ['status'],
            ['lastid', 0]
        ], $request);
        $status=(int)$post["status"];
        $lastId=(int)$post["lastid"];
        $list=Reward::getMyPutTask($uid,$status,$lastId);
        return app('json')->successful($list);

    }

    /*
     * 悬赏任务初步编辑支付
     */
    public function taskPutPay(Request $request){
        $uid=7;
        $post = UtilService::postMore([
            ['logo_url', ''],
            ['type', 0],
            ['title', ''],
            ['plat_name', ''],
            ['sub_time', 0],
            ['check_time', 0],
            ['content','' ],
            ['task_price', 0],
            ['per_price', 0],
            ['task_people', 0]
        ], $request);

        if(empty($post['logo_url'])){
            return app('json')->fail('请上传LOGO图');
        }
        if($post["type"]<0){
            return app('json')->fail('请选择次数类型');
        }
        if(empty($post['title'])){
            return app('json')->fail('请输入悬赏标题');
        }
        if(empty($post['plat_name'])){
            return app('json')->fail('请输入平台名称');
        }
        if($post["sub_time"]<0){
            return app('json')->fail('请输入提交时间');
        }
        if($post["check_time"]<0){
            return app('json')->fail('请输入审核时间');
        }
        if(empty($post['content'])){
            return app('json')->fail('请输入任务说明');
        }
        if($post["task_price"]<=0){
            return app('json')->fail('请输入悬赏单价');
        }
        if($post["per_price"]<=0){
            return app('json')->fail('请输入用户单价');
        }
        if($post["task_people"]<=0){
            return app('json')->fail('请输入悬赏名额');
        }
        $data["logo_url"]=$post['logo_url'];
        $data["type"]=$post['type'];
        $data["title"]=$post['title'];
        $data["plat_name"]=$post['plat_name'];
        $data["sub_time"]=$post['sub_time'];
        $data["check_time"]=$post['check_time'];
        $data["content"]=$post['content'];
        $data["task_price"]=$post['task_price'];
        $data["per_price"]=$post['per_price'];
        $data["task_people"]=(int)$post['task_people'];

        $res=Reward::putTask($uid,$data);
        if(!$res["back"]){
            return app('json')->fail($res["msg"]);
        }
        return app('json')->successful($res["back"]);

    }


    /*
     * 商家悬赏任务下的报名列表
     */
    public function putTaskGetList(Request $request){
        $uid=7;
        $post = UtilService::postMore([
            ['task_put_id'],
            ['lastid',0]
        ], $request);
        $taskPutId=(int)$post["task_put_id"];
        $lastId=(int)$post["lastid"];
        $list=Reward::myPutGetList($uid,$taskPutId,$lastId);
        return app('json')->successful($list);
    }



    /*
     * 商家审核用户的任务,通过
     */
    public function checkPassGet(Request $request){
        $uid=7;
        $post = UtilService::postMore([
            ['task_put_id'],
            ['task_get_uid',0]
        ], $request);
        $taskPutId=(int)$post["task_put_id"];
        $taskGetUid=(int)$post["task_get_uid"];
        $res=Reward::putCheckPassGet($uid,$taskPutId,$taskGetUid);
        if(!$res["back"]){
            return app('json')->fail($res["msg"]);
        }
        return app('json')->successful($res["msg"]);

    }

    /*
     * 商家审核用户的任务，驳回
     */
    public function checkRejectGet(Request $request){
        $uid=7;
        $post = UtilService::postMore([
            ['task_put_id'],
            ['task_get_uid',0]
        ], $request);
        $taskPutId=(int)$post["task_put_id"];
        $taskGetUid=(int)$post["task_get_uid"];
        $res=Reward::putCheckRejectGet($uid,$taskPutId,$taskGetUid);
        if(!$res["back"]){
            return app('json')->fail($res["msg"]);
        }
        return app('json')->successful($res["msg"]);
    }




    /*
     * 用户报名任务
     */
    public function taskGet(Request $request){
        $uid=8;
        $post = UtilService::postMore([
            ['task_put_id']
        ], $request);
        $taskPutId=(int)$post["task_put_id"];
        $res=RewardGet::getTheTask($uid,$taskPutId);
        if(!$res["back"]){
            return app('json')->fail($res["msg"]);
        }
        return app('json')->successful($res["msg"]);

    }

    /*
     * 用户上传任务完成信息，图或者手机号
     */
    public function taskUp(Request $request){
        $uid=8;
        $post = UtilService::postMore([
            ['task_put_id'],
            ['upload']
        ], $request);
        $up=$post["upload"];
        $taskPutId=(int)$post["task_put_id"];

        if(empty($up)){
            return app('json')->fail("请上传数据");
        }

        $res=RewardGet::upTaskData($uid,$taskPutId,$up);
        if(!$res["back"]){
            return app('json')->fail($res["msg"]);
        }
        return app('json')->successful($res["msg"]);
    }

    /*
     * 用户取消任务
     */
    public function taskGetUndo(Request $request){
        $uid=8;
        $post = UtilService::postMore([
            ['task_put_id']
        ], $request);
        $taskPutId=(int)$post["task_put_id"];
        $res=RewardGet::undoTask($uid,$taskPutId);
        if(!$res["back"]){
            return app('json')->fail($res["msg"]);
        }
        return app('json')->successful($res["msg"]);

    }



    /*
     * 账单明细
     */
    public function myBill(Request $request){
        $uid=7;
        $post = UtilService::postMore([
            ['lastid',0],
            ['date',0]
        ], $request);
        $lastId=(int)$post["lastid"];
        $date=$post["date"];
        $list=Reward::mybill($uid,$lastId,$date);
        return app('json')->successful($list);

    }
}