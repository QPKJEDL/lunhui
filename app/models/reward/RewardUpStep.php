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

/*
 * 悬赏 任务步骤表
 */
Class RewardUpStep extends BaseModel{
    /**
     * 数据表主键
     * @var string
     */
    protected $pk = 'id';

    /**
     * 模型名称
     * @var string
     */
    protected $name = 'task_up_step';

    use ModelTrait;

}