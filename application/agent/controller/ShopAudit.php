<?php
namespace app\agent\controller;
use app\base\controller\Agent;
use think\Db;
/**
* 修车厂审核管理
*/
class ShopAudit extends Agent
{


	/**
	 * 已通过审核可正常运营的修车厂列表
	 * @return [type] [description]
	 */
	public function shopList()
	{
		$page = input('post.page') ? : 1;
		return $this->sList(2,$page);
	}


	/**
	 * 等待审核列表
	 * @return [type] [description]
	 */
	public function index()
	{
		$page = input('post.page') ? : 1;
		return $this->sList(1,$page);
	}

	/**
	 * 列表
	 * @return [type] [description]
	 */
	public function sList($status,$page)
	{
		$where=[
			['aid','=',$this->aid],
			['audit_status','=',$status]
		];
		$count = Db::table('cs_shop')
					->where($where)
					->count();
		$pageSize = 10;
		$rows = ceil($count / $pageSize);
		$list = Db::table('cs_shop')
				->where($where)
				->field('id,company,leader,phone,create_time,service_num')
				->order('id desc')->page($page,$pageSize)
				->select();
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
		}else{

			$this->result('',0,'暂无数据');
		}
	}
	/**
	 * 修车厂详情
	 * @return [type] [description]
	 */
	public function detail()
	{
		$sid=input('post.id');
		$list = Db::table('cs_shop s')
				->join('cs_shop_set ss','s.id=ss.sid')
				->field('usname,company,major,leader,province,city,county,address,phone,service_num,license,photo,aid,sid')
				->where('s.id',$sid)
				->select();
		foreach ($list as $key => $value) {
			$list[$key]['photo'] = json_decode($list[$key]['photo']);
		} 
		return $list;
	}
	/**
	 * 修车厂审核列表确认操作
	 * @return [type] [description]
	 */
	public function adopt()
	{
		$sid=input('post.sid');
		Db::startTrans();
		// 判断运营商的可开通数量
		if(Db::table('ca_agent')->where('aid',$this->aid)->value('shop_nums') <= 0){
			Db::rollback();
			$this->result('',0,'您可开通修理厂数量为 0 ,请您到个人中心->供应地区,设置您所管辖的地区');
		}
		// 运营商授信库存减少一组,可开通修车厂名额减少一个，已开通修车厂数量增加一个
		if($this->credit($this->aid)){
			// 修车厂配给库存增加
			if($this->shopRation($sid)){
				// 更改修理厂审核状态
				if($this->shopStatus($sid)==true && $this->shopInc() == true){
					// 判断该地区是否有服务经理 开始
					//获取运营商供应地区的市级id
					$agent_city = $this->agentSm($this->aid);
					//获取运营商所在区域的省级id
					$province = Db::table('co_china_data')
								->where('id',$agent_city)
								->value('pid');
					//根据运营商的供应地区获取上级服务经理的id 和开发、任务奖励
					$sm_sm = $this->smAdmin($agent_city,1);
					// 判断是否有服务经理  并且 开发奖励开启,开发奖励未开启任务奖励没有任何意义
					if($sm_sm && $sm_sm['exp_raw'] == 1){
						$this->smInc($sid,$this->aid,$sm_sm,$province);
					}
					

					//判断区域内是否有运营总监 xjm 20181019
					$sm_yy = Db::table('sm_area')
							 ->alias('a')
							 ->join('co_china_data d','d.pid = a.area')
							 ->join('sm_user u','u.id = a.sm_id')
							 ->where([
							 	'd.id' => $agent_city,
							 	'a.audit_status' => 1,//审核状态
							 	'is_exits' => 1,//是否总后台直接取消合作
							 	'sm_type' => 2,//运营总监
							 	'exp_raw' => 1
							 ])
							 ->where('sm_mold','<>',2)
							 ->order('id')
							 ->limit(1)
							 ->field('a.sm_id,a.id,u.open_id,a.sm_profit,a.area')
				 			 ->find();
				 	//判断是否有运营总监
					if($sm_yy) {
						$sm_yy_reward = $this->smYReward($sid,$this->aid,$sm_yy['sm_id'],$sm_yy['area']);
					}



					Db::commit();
					$this->result('',1,'操作成功');
				}else{
					Db::rollback();
					$this->result('',0,'操作失败');
				}
			}else{
				Db::rollback();
				$this->result('',0,'维修厂库存修改失败');
			}
		}else{
			Db::rollback();
			$this->result('',0,'运营商开通库存减少失败');
		}
	}
	/**
     * 修改状态
     * @param  [type] $table   要修改的表
     * @param  [type] $sid     要修改的id
     * @param  [type] $status  要修改的状态值
     * @return [type]         [description]
     */
    public function shopStatus($id)
    {
        $res=Db::table('cs_shop')->where('id',$id)->update(['audit_status'=>2,'audit_time'=>time()]);
        if($res!==false){
            return true;
        }
    }
	/**
	 * 修车厂审核驳回操作
	 * @return [type] [description]
	 */
	public function reject()
	{
		$sid=input('post.sid');
		$reason=input('post.reason');
		if($reason){
			$res=$this->status('cs_shop',$sid,3);
			if($res==true){
				$this->result('',1,'驳回成功');
			}else{
				$this->result('',0,'驳回失败');
			}
		}else{
			$this->result('',0,'驳回理由不能为空');
		}
	}
	/**
	 * 给审核通过的修车厂增加配给库存
	 * @param [type] $sid 修车厂id
	 */
	private function shopRation($sid)
	{
		foreach ($this->bangCate() as $k => $v) {
			$arr[]=[
				'sid'=>$sid,
				'materiel'=>$v['id'],
				'ration'=>$v['def_num'],
				'warning'=>ceil($v['def_num']*20/100),
				'stock'=>$v['def_num'],
			];
		}
		$res = Db::table('cs_ration')->insertAll($arr);
		if($res){
			return true;
		}
	}
	//修车厂申请物料修改over时间
	// public function a()
	// {
	// 	$crea = Db::table('cs_apply_materiel')->where('id',5)->value('create_time');
	// 	$crea = strtotime($crea);
	// 	Db::table('cs_apply_materiel')->where('id',5)->setField('over_time',$crea+259200);
	// }
	// 
	/**
	 * 开通维修厂奖励运营商1000元  
	 * @return [type] [description]
	 */
	public function shopInc()
	{
		//查看开发奖励剩余次数
		$devel = Db::table('ca_agent')->where('aid',$this->aid)->value('devel');
		if($devel > 0){
			// 减少开发奖励次数,增加开放奖励金额，增加总余额
			$develNum = Db::table('ca_agent')
						->where('aid',$this->aid)
						->dec('devel',1)
						->inc('awards',1000)
						->inc('balance',1000)
						->update();
			if($develNum !== false){
				return true;
			}	
		}else{
			// 不增加运营商余额
			return true;
		}
	}

	/**
	 * 运营总监获得开发奖励 xjm20181019
	 * @param  [type] $sid   [维修厂id]
	 * @param  [type] $aid   [运营商id]
	 * @param  [type] $sm_id [运营总监id]
	 * @param  [type] $area  [区域id]
	 * @return [type]        [description]
	 */
	public function smYReward($sid,$aid,$sm_id,$area)
	{	
		//开启事务
		DB::startTrans();
		// 查询运营商公司名称
		$yy_company = Db::table('ca_agent')->where('aid',$aid)->value('company');
		//获取维修厂地址及审核时间
		$address = Db::table('cs_shop_set ss')
			->join('cs_shop cs','ss.sid = cs.id')
			->where('ss.sid',$sid)
			->field('province,city,county,audit_time')
			->find();
		//获取金额
		$money = Db::table('am_sm_set')
				 ->where('status',2)
				 ->value('devel_reward');
		$trade_no = build_only_sn();
		//构建入库数据
		$arr = [
			'sm_id' => $sm_id,
			'odd_number' => $trade_no,
			'company' => $yy_company,
			'money' => $money,
			'address' => $address['province'].$address['city'].$address['county'],
			'type' => 2,
			'cid' => 0,
			'person_rank' => 2,//运营总监
			'sid' => $sid,
			'if_finish' => 1,
			'cash_status' => 0
		];
		$res = Db::table('sm_income')
						->strict(false)
						->insert($arr);
		//增加运营总监余额
		$sm_inc = Db::table('sm_user')
						->where('id',$sm_id)
						->setInc('balance',$money);
		if($res !== false && $sm_inc !==false) {
			Db::commit();
			return true;
		} else {
			Db::rollback();
			return false;
		}
	}
	/** 
	 * 增加服务经理收入
	 * @return [type] [description]
	 *$sid 维修厂id
	 *$aid 运营商id
	 */
	public function smInc($sid,$aid,$sm_sm,$province)
	{
		// 该地区不存在服务经理 或没有开通开发奖励，此方法进不来
		
		// return $sm_sm;die();
		//查询运营商的公司名称
		$agent_company = Db::table('ca_agent')
							->where('aid',$aid)
							->value('company');
		// return $agent_company;die();
		//获取维修厂地址
		$address = Db::table('cs_shop_set')
					->where([
						'sid' => $sid
					])
					->field('province,city,county')
					->find();
		//有服务经理,判断该服务经理在此区域是否开通了开发奖励
		//查询服务经理开发奖励金额以及人物奖励金额
		$sm_money = Db::table('am_sm_set')
				->where([
					'status' => 1,
				])
				->field('devel_reward,task_reward')
				->find();
		//服务经理的入库订单号
		$sm_odd_number = build_only_sn();
		//构建服务经理开发奖励入库
		$sm_arr = [
			'sm_id' => $sm_sm['sm_id'],
			'odd_number' =>  $sm_odd_number,
			'company' => $agent_company,
			'money' => $sm_money['devel_reward'],
			'address' => $address['province'].$address['city'],
			'type' => 2,
			'sid' => $sid,
			'cash_status' => 0
		];
		//入库服务经理开发奖励信息
		$re = Db::table('sm_income')
				->strict(false)
				->insert($sm_arr);
		$sm_inc = Db::table('sm_user')
					->where('id',$sm_sm['sm_id'])
					->setInc('balance',$sm_money['devel_reward']);
		//判断该服务经理是否开通了任务奖励
		if($sm_sm['task_raw'] == 1) {
			// return 3;die();
			//开通了任务奖励 
			//服务经理的任务奖励入库订单号
			$sm_trade_no = build_only_sn();
			//构建服务经理任务奖励入库数据
			$sm_task_arr = [
				'sm_id' => $sm_sm['sm_id'],
				'odd_number' =>  $sm_trade_no,
				'company' => $agent_company,
				'money' => $sm_money['task_reward'],
				'address' => $address['province'].$address['city'],
				'type' => 2,
				'if_finish' => 0,
				'sid' => $sid,
				'cash_status' => 0
			];
			//入库服务经理任务奖励信息
			$res = Db::table('sm_income')
					->strict(false)
					->insert($sm_task_arr);

		}//判断服务经理是否开通任务奖励结束

		// 根据服务经理id获取运营总监id和是否开启管理奖励 和分佣比例 查询否有加入团队
		// $sm_yy = Db::table('sm_team st')
		// 		 ->join('sm_area sa','st.sm_header_id = sa.sm_id')
		// 		 ->where('st.sm_member_id','like','%'.$sm_sm['sm_id'].'%')
		// 		 ->where([
		// 		 	'sa.audit_status'=>1,
		// 		 	'sa.sm_type'=>2,
		// 		 	'exp_raw'=>1,
		// 		 	'is_exits'=>1,
		// 		 	'area'=>$province
		// 		 ])
		// 		 ->where('sm_mold','<>',2)
		// 		 ->field('sm_id,sm_profit')
		// 		 ->find();
		// return $sm_yy;die();
		// 判断改运营总监所管辖的该地区管理奖励是否开启 开启则给运营总监分佣
		// if(!empty($sm_yy)) {
		// 	// return 4;die();
		// 	//获取服务经理的信息
		// 	$sm_name = $this->getSmInfo($sm_sm['sm_id']);
		// 	//查询运营总监开发奖励金额
		// 	$devel_reward = Db::table('am_sm_set')
		// 				->where([
		// 					'status' => 2
		// 				])
		// 				->value('devel_reward');
		// 	//构建运营总监开发奖励入库数据
		// 	$yy_insert = [
		// 		'sm_id' => $sm_yy['sm_id'],
		// 		'odd_number' => build_only_sn(),
		// 		'company' => $sm_name['name'],
		// 		'money' => $devel_reward,
		// 		'address' => $address['province'].$address['city'],
		// 		'type' => 2,
		// 		'person_rank' => 2,
		// 		'sid' => $sid,
		// 		'uuid' => $sm_sm['sm_id'],
		// 		'cash_status' => 0
		// 	];
		// 	//插入数据
		// 	$result = Db::table('sm_income')
		// 				->strict(false)
		// 				->insert($yy_insert);
		// 	//提高运营总监的可提现金额 //运营总监不可提现直接到银行卡
		// 	$sm_inc = Db::table('sm_user')
		// 				->where('id',$sm_yy['sm_id'])
		// 				->setInc('balance',$devel_reward);
		// 	// 判断任务奖励是否开启
		// 	if(isset($res)){
		// 		if($re && $sm_inc && $result && $sm_inc && $res){
		// 			return true;
		// 		}
		// 	}else{
		// 		if($re && $sm_inc && $result && $sm_inc){
		// 			return true;
		// 		}
		// 	}
			
		// }
		// 判断任务奖励是否开启
		if(isset($res)){
			// 判断服务经理的奖励是否正确入库
			if($re && $sm_inc){
				return true;
			}
		}
		
	}
  	/**
     * 获取服务经理信息
     * @return [type] [description]
     */
   	public function getSmInfo($uid)
   	{
   		$info = Db::table('sm_user')
   				->where('id',$uid)
   				->find();
   		return $info;
   	}
	/**
	 * 查询服务经理/运营总监的id 和是否开启任务奖励以及开发奖励
	 * @return [type] [description]
	 */
	private function smAdmin($area,$sm_type)
	{
		return Db::table('sm_area')
				->alias('a')
				->join('sm_user u','u.id = a.sm_id')
				->where([
					'a.area'         => $area,
					'a.sm_type'      => $sm_type,
					'a.audit_status' => 1,
					'u.joinStatus'   => 1
				])
				->where('a.sm_mold','<>',2)
				->order('a.id')
				->limit(1)
				->field('a.sm_id,a.task_raw,a.exp_raw,u.account,u.bank_code,u.bank_name,a.id as area_id')
				->find();
	}
	/**
	 * 查询服务经理是否有运营商或维修厂的投诉
	 * @return [type] [description]
	 */
	private function smCom($sm_id,$id,$type)
	{
		return Db::table('sm_complaint')
				->where([
					'sm_id' => $sm_id,
					'uid' => $id,
					'status' => 1,
					'type' => $type
				])
				->count();
	}
	/**
	 * 获取运营商的市级id
	 * @param  [type] $aid [description]
	 * @return [type]      [description]
	 */
	private function agentSm($aid)
	{
		//获取运营商所供应地区
		$area = Db::table('ca_area')
				->where('aid',$aid)
				->limit(1)
				->value('area');
		// 获取运营商供应地区的市级id
		$city = Db::table('co_china_data')
				->where('id',$area)
				->value('pid');
		return $city;
	}

	 /**
     * 生成订单号
     */
    private function createOrder(){
        $yCode = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J');
        return $yCode[intval(date('Y')) - 2017] . strtoupper(dechex(date('m'))) . date('d') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
    }
}