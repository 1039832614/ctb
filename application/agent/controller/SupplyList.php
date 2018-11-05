<?php
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;
/**
* 市级代理列表
*/
class SupplyList extends Admin
{
	/**
	 * 市级代理列表
	 * @return [type] [description]
	 */
	public function index()
	{
		$page = input('post.page')?:1;
		$pageSize = 10;
		$count = Db::table('cg_supply')->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('cg_supply')
				->alias('gs')
				->field('company,gs.phone,leader,license,open_shop,gs.regions,sale_card,balance,service_time,audit_time,gs.gid,agent_nums')
				->page($page,$pageSize)
				->order('gs.gid desc')
				->group('gs.gid')
				->select();
		
		foreach ($list as $k => $v) {
			if($v['audit_time']) $list[$k]['audit_time'] = date('Y-m-d',strtotime('+2 year',$v['audit_time']));
			// $cou = Db::table('ca_agent')->where('gid',$v['gid'])->count();
			// $list[$k]['open_shop'] = $cou;
			
		}
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取市级代理列表成功！');
		}else{
			$this->result(0,0,'暂无数据');
		}
	}

	/**
	 * 运营商数量详情
	 * @return [type] [description]
	 */
	public function agentNum()
	{
		// 获取供应商id
		$gid = input('post.gid');
		$page = input('post.page')?:1;
		// 查询运营商
		$pageSize = 6;
		$count = Db::table('ca_agent')->where('gid',$gid)->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('ca_agent ca')
				->leftJoin('ca_agent_set as','as.aid = ca.aid')
				->where('gid',$gid)
				->page($page,$pageSize)
				->field('company,leader,phone,county,as.profit,as.end_time')
				->select();
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取运营商列表成功！');
		}else{
			$this->result(0,0,'暂无数据');
		}	
	}

	

	/**
	 * 区域个数详情
	 * @return [type] [description]
	 * @param  [type] $gid [供应商id]
	 */
	public function region($gid)
	{
		$list = $this->supplyArea($gid);
		if($list) $this->result($list,1,'获取区域详情成功');
		$this->result(0,0,'暂无数据');
	}



	/**
	 * 售卡数量详情
	 * @param  [type] $gid [供应商id]
	 * @return [type]      [description]
	 */
	public function cardNum($gid,$page)
	{	
		// $gid = input('post.gid');
		// $page = input('post.page')?:1;
		$pageSize = 6;
		$count = Db::table('ca_agent')->where('gid',$gid)->where('sale_card','>',0)->count();
		// print_r($count);exit;
		$rows = ceil($count / $pageSize);
		$list = Db::table('ca_agent')
				->alias('ca')
				->join(['cs_shop'=>'cs'],'ca.aid = cs.aid')
				->join(['u_card'=>'uc'],'cs.id = uc.sid')
				->where(['ca.gid'=>$gid,'pay_status'=>1])
				->page($page,$pageSize)
				->field('ca.company,ca.leader,ca.phone,ca.sale_card,sum(card_price) as sum')
				->group('ca.company')
				->select();
		if($count > 0) $this->result(['list'=>$list,'rows'=>$rows],1,'获取卡详情成功');
		$this->result(0,0,'暂无数据');
	}

}