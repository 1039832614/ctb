<?php 
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;
/**
* 延迟罚款
*/
class DelayedFines extends Admin
{
	/**
	 * 延迟罚款列表
	 * @return [type] [description]
	 */
	public function index()
	{	
		$page = input('post.page')?:1;
		$pageSize = 10;
		$count = Db::table('ca_agent')->where('fines_num','>',0)->count();
		$rows = ceil($count / $pageSize);
		// 显示已上传营业执照、配给表里未审核的数据
		$list = Db::table('ca_agent')
				->field('aid,company,leader,phone,fines,fines_num')
				->order('aid desc')
				->where('fines_num','>',0)
				->page($page,$pageSize)
				->select();
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}

	/**
	 * 罚款详情
	 * @return [type] [description]
	 */
	public function finesDetail()
	{
		$aid = input('post.aid');
		// print_r($aid);exit;
		$page = input('post.page')?:1;
		$pageSize = 6;
		$count = Db::table('ca_delayed_fines')->where('aid',$aid)->count();
		$rows = ceil($count / $pageSize);
		// 显示已上传营业执照、配给表里未审核的数据
		$list = Db::table('ca_delayed_fines')
				->where('aid',$aid)
				->order('id desc')
				->page($page,$pageSize)
				->field('fine,create_time')
				->select();
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}
}