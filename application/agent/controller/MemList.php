<?php
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;
/**
* 邦保养会员列表
*/
class MemList extends Admin
{
	
	/**
	 * 邦保养会员列表
	 * @return [type] [description]
	 */
	public function list()
	{
		$page = input('post.page')?:1;
		$pageSize = 10;

		$count = Db::table('u_member_table')->where('pay_status',1)->count();
		$rows = ceil($count / $pageSize);

		$list = Db::table('u_member_table mt')
				->where('pay_status',1)
				->field('name,phone,price,pay_time,create_time,end_time,m_order')
				->page($page,$pageSize)
				->select();
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}


	/**
	 * 会员购卡列表
	 * @return [type] [description]
	 */
	public function memCard()
	{
		$page = input('post.page')?:1;
		$pageSize = 10;

		$count = Db::table('u_member_table mt')
				->join('u_card uc','mt.uid = uc.uid')
				->where(['uc.pay_status'=>1,'mt.pay_status'=>1])
				->where('sale_time >= mt.create_time')
				->where('mt.end_time','>=',date('Y-m-d H:i:s'))
				->count();
		// echo Db::table('u_member_table')->getLastSql();exit;
		$rows = ceil($count / $pageSize);

		$list = Db::table('u_member_table mt')
				->join('u_card uc','mt.uid = uc.uid')
				->where(['uc.pay_status'=>1,'mt.pay_status'=>1])
				->where('sale_time >= mt.create_time')
				->where('mt.end_time','>=',date('Y-m-d H:i:s'))
				->field('name,phone,card_price,cate_name,dis,sale_time,(card_price/0.85) as original_price')
				->page($page,$pageSize)
				->select();


		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}


	/**
	 * 可观察用户预警
	 * @return [type] [description]
	 */
	public function warn()
	{
		$page = input('post.page')?:1;
		$pageSize = 10;
		$count = Db::table('cb_obs_trea')->count();
		$rows = ceil($count / $pageSize);

		$list = Db::table('cb_obs_trea')
				->order('id desc')
				->select();

		if($count > 0){
			$this->result(['rows'=>$rows,'list'=>$list],1,'获取列表成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}

	/**
	 * 点击改变处理状态
	 * @return [type] [description]
	 */
	public function saveWarn()
	{
		$id = input('post.id');
		if(!$id) $this->result('',0,'缺少id参数');
		// 修改状态
		$res = Db::table('cb_obs_trea')->where('id',$id)->setField('status',1);
		if($res !== false){
			$this->result('',1,'处理成功');
		}else{
			$this->result('',0,'处理失败');
		}
	}


	
	
}