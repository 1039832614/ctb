<?php 
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;

/**
 * 资金管理中的押金
 */
class FundCashDeposit extends Admin
{
	public function index()
	{
		$page = input('post.page')? : 1;
		$pageSize = 10;
		$count = Db::table('ca_agent')->where('status',3)->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('ca_agent')
				->where('status',3)
		        ->order('create_time desc')
		        ->page($page,$pageSize)
		        ->field('company,phone,leader,audit_time,usecost')
		        ->select();
		if($count > 0){
            $this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
        }else{
            $this->result('',0,'暂无数据');
        }        
	}
}