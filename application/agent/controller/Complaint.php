<?php
namespace app\agent\controller;
use app\base\controller\Agent;
use think\Db;
/**
* 投诉管理
*/
class Complaint extends Agent
{
	
	/**
	 * 投诉管理首页列表
	 * @return [type] [description]
	 */
	public function index()
	{
		$page = input('post.page') ? : 1;
		$pageSize = 10;
		$count = Db::table('u_complain')->where('aid',$this->aid)->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('u_complain uc')
				->join('cs_shop ss','uc.sid = ss.id')
				->join('u_user uu','uc.uid = uu.id')
				->where('uc.aid',$this->aid)
				->field('uc.id,company,name,uu.phone,content,uc.create_time')
				->order('id desc')->page($page,$pageSize)->select();
		if($count > 0){                   
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}


	/**
	 * 投诉管理详情
	 * @return [type] [description]
	 */
	public function detail()
	{
		$id=input('post.id');
		return Db::table('u_complain uc')
			->join('cs_shop ss','uc.sid = ss.id')
			->join('u_user uu','uc.uid = uu.id')
			->where('uc.id',$id)
			->field('company,name,uu.phone,uc.create_time,content')
			->select();
	}


}