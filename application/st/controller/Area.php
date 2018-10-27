<?php 
namespace app\st\controller;
use app\base\controller\St;
use think\Db;

/**
 * 预约订单管理
 */
class Area extends St
{
    /*
     * 进程初始化
     */
    public function initialize()
    {
        parent::initialize();
    }

    public function areas()
    {
        return $this->province();
    }
    public function areass()
    {
        $pid = input('post.pid');
        return $this->city($pid);
    }
    public function areasss()
    {
        $cid = input('post.cid');
        return $this->county($cid);
    }
}