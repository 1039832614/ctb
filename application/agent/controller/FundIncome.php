<?php 
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;
/**
 * 资金收入管理
 */
class FundIncome extends Admin
{
	/**
	 * 邦保养卡收入
	 * @return [type] [description]
	 */
	public function cardsList()
	{
		$page = input('post.page')? : 1;
		$pageSize = 10;
		$count = Db::table('u_card')->count();
		$rows = ceil($count / $pageSize);
		$list =  Db::table('u_card')
		         ->alias('c')
		         ->join('u_user u','c.uid = u.id')
		         ->join('cs_shop s','c.sid = s.id')
		         ->order('c.sale_time desc')
		         ->page($page,$pageSize)
		         ->field('s.company,u.name,u.phone,c.sale_time,c.card_price')
		         ->select();
		$amount = 0;//购卡总金额
		foreach ($list as $key => $value) {
			$amount = $amount + $value['card_price'];
		}
		if($count > 0){
            $this->result(['list'=>$list,'amount'=>$amount,'rows'=>$rows],1,'获取列表成功');
        }else{
            $this->result('',0,'暂无数据');
        }           
	}

	/**
	 * 票务费收入
	 * @return [type] [description]
	 */
	public function taxList()
	{
		$page = input('post.page')? : 1;
		$pageSize = 10;
		$count = Db::table('u_tax')->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('u_tax')
		        ->order('create_time desc')
		        ->page($page,$pageSize)
		        ->field('contacter,phone,total,fee,create_time')
		        ->select();
		$amount = 0;//票务费用总金额
		foreach ($list as $key => $value) {
			$amount = $amount + $value['fee'];
		}
		if($count > 0){
            $this->result(['list'=>$list,'amount'=>$amount,'rows'=>$rows],1,'获取列表成功');
        }else{
            $this->result('',0,'暂无数据');
        }     
	}

 //    /**
 //     * 运营商系统使用费收入
 //     * @return [type] [description]
 //     */
	// public function agentList()
	// {
	// 	$page = input('post.page')? : 1;
	// 	$pageSize = 10;
	// 	$count = Db::table('ca_agent')->count();
	// 	$rows = ceil($count / $pageSize);
	// 	$list = Db::table('ca_agent')
	// 	        ->order('create_time desc')
	// 	        ->page($page,$pageSize)
	// 	        ->field('company,phone,leader,create_time,usecost')
	// 	        ->select();
	// 	if($count > 0){
 //            $this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
 //        }else{
 //            $this->result('',0,'暂无数据');
 //        }           
	// }
	
	/**
	 * 运营商系统使用费
	 * @return [type] [description]
	 */
	public function agentList()
	{
		$page = input('post.page')? : 1;
		$pageSize = 10;
		$count = Db::table('co_system_fee')->where('pay_type',2)->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('co_system_fee csf')
				->join('ca_agent ca','csf.uid = ca.aid')
				->where('csf.pay_type',2)
				->field('company,phone,leader,total_fee,ca.create_time')
				->page($page,$pageSize)
				->select();
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功！');
		}else{
			$this->result('',0,'暂无数据！');
		}
	}


	/**
	 * 维修厂系统使用费
	 * @return [type] [description]
	 */
	public function shopList()
	{
		$page = input('post.page')? : 1;
		$pageSize = 10;
		$count = Db::table('co_system_fee')->where('pay_type',1)->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('co_system_fee csf')
				->join('cs_shop cs','csf.uid = cs.id')
				->where('csf.pay_type',1)
				->field('company,phone,leader,total_fee,cs.create_time')
				->page($page,$pageSize)
				->select();
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功！');
		}else{
			$this->result('',0,'暂无数据！');
		}
	}
}