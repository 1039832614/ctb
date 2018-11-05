<?php 
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;

/**
 * 资金支出管理
 */
class FundExpend extends Admin
{
	/**
	 * 技师邦保养奖励支出列表
	 * @return [type] [description]
	 */
	public function workExpendList()
	{
		//技师姓名，所在维修厂，邦保养卡号，服务时间，获取金额
		$page = input('post.page')? : 1;
		$pageSize = 10;
		$count = Db::table('tn_worker_reward')
					->where('type',1)
					->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('tn_worker_reward')
		        ->alias('r')
		        ->join('u_card c','c.id = r.acid')
		        ->join('tn_user w','r.wid = w.id')
		        ->join('cs_shop s','w.sid = s.id')
		        ->where('type',1)
		        ->order('r.create_time desc')
		        ->page($page,$pageSize)
		        ->field('w.name,s.company,c.card_number,r.create_time,r.reward')
		        ->select();
		$amount = 0;//汽修厂提现总金额
		foreach ($list as $key => $value) {
			$amount = $amount + $value['reward'];
		}
		if($count > 0){
            $this->result(['list'=>$list,'amount'=>$amount,'rows'=>$rows],1,'获取列表成功');
        }else{
            $this->result('',0,'暂无数据');
        }    
	}

	/**
	 * 车主转发奖励列表
	 * @return [type] [description]
	 */
	public function userTranspondList()
	{
		$page = input('post.page')? : 1;
		$pageSize = 10;
		$count = Db::table('u_share_income')->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('u_share_income')
		        ->alias('b')
		        ->join('u_user u','b.uid = u.id')
		        ->order('b.create_time desc')
		        ->page($page,$pageSize)
		        ->field('u.name as introducer,u.phone as introducer_phone,buyer,money,b.create_time,b.reward')
		        ->select();
		$amount = 0;//总的车主转发奖励
		foreach ($list as $key => $value) {
			$amount = $amount + $value['reward'];
		}
		if($count > 0){
            $this->result(['list'=>$list,'amount'=>$amount,'rows'=>$rows],1,'获取列表成功');
        }else{
            $this->result('',0,'暂无数据');
        }            
	}

	/**
	 * 汽修厂提现列表
	 * @return [type] [description]
	 */
	public function shopExpendList()
	{
		// 汽修厂名字，电话，负责人，提现金额，提现时间
		$page = input('post.page')? : 1;
		$pageSize = 10;
		$count = Db::table('cs_apply_cash')->where('audit_status',1)->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('cs_apply_cash')
		        ->alias('c')
		        ->join('cs_shop s','c.sid = s.id')
		        ->order('c.create_time desc')
		        ->where('c.audit_status',1)
		        ->page($page,$pageSize)
		        ->field('s.company,s.phone,s.leader,c.audit_person,money,c.audit_time,c.account')
		        ->select();
		$amount = 0;//汽修厂提现总金额
		foreach ($list as $key => $value) {
			$amount = $amount + $value['money'];
		}
		if($count > 0){
            $this->result(['list'=>$list,'amount'=>$amount,'rows'=>$rows],1,'获取列表成功');
        }else{
            $this->result('',0,'暂无数据');
        }              	    
	}

	/**
	 * 运营商提现列表
	 * @return [type] [description]
	 */
	public function agentExpendList()
	{
		$page = input('post.page')? : 1;
		$pageSize = 10;
		$count = Db::table('ca_apply_cash')->where('audit_status',1)->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('ca_apply_cash')
		        ->alias('c')
		        ->join('ca_agent a','c.aid = a.aid')
		        ->order('c.create_time desc')
		        ->where('c.audit_status',1)
		        ->page($page,$pageSize)
		        ->field('a.company,a.phone,a.leader,money,c.audit_time,c.account')
		        ->select();
		$amount = 0;//运营商提现总金额
		foreach ($list as $key => $value) {
			$amount = $amount + $value['money'];
		}
		if($count > 0){
            $this->result(['list'=>$list,'amount'=>$amount,'rows'=>$rows],1,'获取列表成功');
        }else{
            $this->result('',0,'暂无数据');
        }         
	}
}