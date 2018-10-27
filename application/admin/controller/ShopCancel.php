<?php
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;
/**
* 修车厂取消合作列表
*/
class ShopCancel extends Admin
{
	
	/**
	 * 取消合作列表
	 * @return [type] [description]
	 */
	public function index()
	{
		$page = input('post.page')? : 1;
		$pageSize = 10;
		$count = Db::table('cs_apply_cancel')->where('audit_status',1)->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('cs_apply_cancel ac')
				->join('ca_agent ca','ac.aid = ca.aid')
				->where('ac.audit_status',1)
				->page($page,$pageSize)
				->order('id desc')
				->field('id,ac.company,ca.company as com,phone,ac.leader,ac.audit_time')
				->select();
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}



	/**
	 * 点击取消合作显示取消内容
	 * @return [type] [description]
	 */
	public function scReason()
	{
		$id = input('post.id');
		$reason = $this->reason($id,'cs_apply_cancel','reason');
		if($reason){
			$this->result($reason,1,'获取理由成功');
		}else{
			$this->result('',0,'获取理由失败');
		}
	}
}