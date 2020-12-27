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
use think\facade\Config;

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
     * 根据任务标题搜索
     */
    public function find(Request $request){
        $post = UtilService::postMore([
            ['title', '']
        ], $request);
        $title=$post["title"];
        if(!empty($title)){
            $list=Reward::getTheTask($title);
        }else{
            $list=Reward::getTaskList();
        }
        return app('json')->successful($list);
    }

    /*
     * 我接的任务
     */
    public function myGetTask(Request $request){
        $uid = $request->uid();
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
        $uid=$request->uid();
        $post = UtilService::postMore([
            ['task_put_id', 0]
        ], $request);
        $taskId=$post["task_put_id"];
        $info=Reward::getTaskInfo($uid,$taskId);
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
     *我的主页
     */
    public function myHomePage(Request $request){
        $uid=$request->uid();
        $post = UtilService::postMore([
            ['lastid', 0]
        ], $request);
        $lastId=(int)$post["lastid"];
        $info=Reward::getPutHomeList($uid,$lastId);
        return app('json')->successful($info);
    }


    /*
    * 商家中心，首页数据统计
    */
    public static function putData(Request $request){
        $uid = $request->uid();
        $data=Reward::getPutData($uid);
        return app('json')->successful($data);
    }

    /*
     * 商家的所有任务
     */
    public static function myPutTask(Request $request){
        $uid = $request->uid();
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
     * 悬赏任务支付
     */
    public function taskPutPay(Request $request){
        $uid =$request->uid();
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
        $res["task_put_id"]=$res["back"];
        unset($res["back"]);
        unset($res["msg"]);
        return app('json')->successful($res);

    }

    /*
     * 发布任务
     */
    public function taskPutOpen(Request $request){
        $uid =$request->uid();
        $post = UtilService::postMore([
            ['task_put_id', ''],
            ['step', ''],
        ], $request);
        if(empty($post['task_put_id'])){
            return app('json')->fail('请选择任务');
        }
        if(empty($post['step'])){
            return app('json')->fail('请编辑任务步骤');
        }
        $taskPutId=(int)$post["task_put_id"];
        $step=json_decode($post["step"],true);
        $res=Reward::taskOpen($uid,$taskPutId,$step);

        if(!$res["back"]){
            return app('json')->fail($res["msg"]);
        }
        unset($res["back"]);
        return app('json')->successful("发布成功");

    }


    /*
     * 商家悬赏任务下的报名列表审单
     */
    public function putTaskGetList(Request $request){
        $uid = $request->uid();
        $post = UtilService::postMore([
            ['task_put_id'],
            ['lastid',0],
            ['status'],
        ], $request);
        $taskPutId=(int)$post["task_put_id"];
        $lastId=(int)$post["lastid"];
        $status=(int)$post["status"];
        $list=Reward::myPutGetList($taskPutId,$lastId,$status);
        return app('json')->successful($list);
    }



    /*
     * 商家审核用户的任务,通过
     */
    public function checkPassGet(Request $request){
        $uid = $request->uid();
        $post = UtilService::postMore([
            ['task_get_id'],
            ['task_put_id'],
            ['task_get_uid',0]
        ], $request);
        $taskGetId=(int)$post["task_get_id"];
        $taskPutId=(int)$post["task_put_id"];
        $taskGetUid=(int)$post["task_get_uid"];
        $res=Reward::putCheckPassGet($uid,$taskGetId,$taskPutId,$taskGetUid);
        if(!$res["back"]){
            return app('json')->fail($res["msg"]);
        }
        return app('json')->successful($res["msg"]);

    }

    /*
     * 商家审核用户的任务，驳回
     */
    public function checkRejectGet(Request $request){
        $uid = $request->uid();
        $post = UtilService::postMore([
            ['task_get_id'],
            ['task_put_id'],
            ['task_get_uid',0],
            ['remark']
        ], $request);
        $taskGetId=(int)$post["task_get_id"];
        $taskPutId=(int)$post["task_put_id"];
        $taskGetUid=(int)$post["task_get_uid"];
        $remark=htmlspecialchars($post["remark"]);
        $res=Reward::putCheckRejectGet($uid,$taskGetId,$taskPutId,$taskGetUid,$remark);
        if(!$res["back"]){
            return app('json')->fail($res["msg"]);
        }
        return app('json')->successful($res["msg"]);
    }




    /*
     * 用户报名任务
     */
    public function taskGet(Request $request){
        $uid = $request->uid();
        $post = UtilService::postMore([
            ['task_put_id']
        ], $request);
        $taskPutId=(int)$post["task_put_id"];
        $res=RewardGet::getTheTask($uid,$taskPutId);
        if(!$res["back"]){
            return app('json')->fail($res["msg"]);
        }
        unset($res["back"]);
        return app('json')->successful($res);
    }

    /*
     * 用户上传任务完成信息，图或者手机号
     */
    public function taskUp(Request $request){
        $uid =$request->uid();
        $post = UtilService::postMore([
            ['task_get_id'],
            ['task_put_id'],
            ['upload']
        ], $request);
        $up=$post["upload"];
        $taskGetId=(int)$post["task_get_id"];
        $taskPutId=(int)$post["task_put_id"];

        if(empty($up)){
            return app('json')->fail("请上传数据");
        }
        $up=json_decode($up,true);
        $res=RewardGet::upTaskData($uid,$taskGetId,$taskPutId,$up);
        if(!$res["back"]){
            return app('json')->fail($res["msg"]);
        }
        return app('json')->successful($res["msg"]);
    }

    /*
     * 用户取消任务
     */
    public function taskGetUndo(Request $request){
        $uid = $request->uid();
        $post = UtilService::postMore([
            ['task_get_id'],
            ['task_put_id']
        ], $request);
        $taskGetId=(int)$post["task_get_id"];
        $taskPutId=(int)$post["task_put_id"];
        $res=RewardGet::undoTask($uid,$taskGetId,$taskPutId);
        if(!$res["back"]){
            return app('json')->fail($res["msg"]);
        }
        return app('json')->successful($res["msg"]);

    }


    /*
     * 账单明细
     */
    public function myBill(Request $request){
        $uid = $request->uid();
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