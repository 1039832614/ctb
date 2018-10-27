<?php
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;
/**
* 运营商列表
*/
class AgentList extends Admin
{
	

	/**
	 * 关停列表
	 * @return [type] [description]
	 */
	public function stopList()
	{
		$page = input('post.page')?:1;
		$pageSize = 10;
		$count = Db::table('ca_agent')->where('status',7)->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('ca_agent')
				->alias('aa')
				->join('ca_stop_start ss','aa.aid = ss.aid')
				->where(['aa.status'=>7,'ss.status'=>0])
				->field('aa.aid,aa.company,aa.leader,phone,balance,ss.create_time,ss.reason')
				->order('aa.aid desc')->page($page,$pageSize)
				->select();
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
		}else{
			$this->result('',0,'暂无数据');
		}

	}


	
	/**
	 * 运营商关停
	 * @return [type] [description]
	 */
	public function stop()
	{
		//获取运营商id 名称company 负责人 lader 关停理由 reason
		$data = input('post.');
		unset($data['token']);
		// 入库到运营商关停表
		$res = Db::table('ca_stop_start')->insert($data);
		// 修改运营商的状态
		$result = Db::table('ca_agent')->where('aid',$data['aid'])->setField('status',7);
		if($res && $result){
			$this->result('',1,'关停成功');
		}else{
			$this->result('',0,'操作失败');
		}

	}

	/**
	 * 运营商开启
	 * @return [type] [description]
	 */
	public function start()
	{
		//获取运营商id 名称company 负责人 lader 关停理由 reason
		$data = input('post.');
		$data['status'] = 1;
		unset($data['token']);
		// 入库到运营商开启表
		$res = Db::table('ca_stop_start')->insert($data);
		// 修改运营商的状态
		$result = Db::table('ca_agent')->where('aid',$data['aid'])->setField('status',2);
		if($res && $result){
			$this->result('',1,'开启成功');
		}else{
			$this->result('',0,'开启失败');
		}

	}



	/**
	 * 运营商列表
	 * @return [type] [description]
	 */
	public function index()
	{
		// print_r($this->authAction());exit;
		// if(!$this->authAction($this->admin_id,$this->index())){
		// 	$this->result('',0,'暂无权限');
		// }
		$page = input('post.page')? :1;
		$pageSize = 10;
		// $count = Db::table('cs_shop')->where(['aid'=>$aid,'audit_status'=>2])->count();
		$count = Db::table('ca_agent')->where('status',2)->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('ca_agent')
				->where('status',2)
				->field('aid,company,leader,phone,open_shop,regions,balance,sale_card,service_time')
				->order('aid desc')->page($page,$pageSize)
				->select();
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}


	/**
	 * 运营商列表  点击运营商名称显示内容
	 * @return [type] [description]
	 */
	public function company()
	{
		$aid = input('post.aid');
		// 获取运营商详情
		$detail = Db::table('ca_agent')->where('aid',$aid)->field('license,company,leader,phone,province,city,county,address,branch,account,bank_name')->find();
		if($detail){
			$this->result($detail,1,'获取数据成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}


	/**
	 * 运营商列表  点击修车厂数量显示内容
	 * @return [type] [description]
	 */
	public function shopNum()
	{
		$aid = input('post.aid');
		$page = input('post.page')? :1;
		$pageSize = 6;
		$count = Db::table('cs_shop')->where(['aid'=>$aid,'audit_status'=>2])->count();
		// print_r($inc);exit;
		$rows = ceil($count / $pageSize);
		// 获取该运营商已通过审核正常运行的修车厂列表
		$shopList = Db::table('cs_shop')
					->where(['aid'=>$aid,'audit_status'=>2])
					->field('company,leader,phone,create_time,audit_time,id')
					->order('id desc')
					->page($page,$pageSize)
					->select();
		foreach ($shopList as $k => $v) {
			  $inc = Db::table('cs_increase')->where('sid',$v['id'])->count();
			  $shopList[$k]['inc']=$inc;
		}
		$list = $this->ifNew($shopList);
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
		}else{
			$this->result('',0,'暂无数据');
		}

	}


	/**
	 * 运营商列表  点击区域个数显示内容
	 * @return [type] [description]
	 */
	public function regionList()
	{
		// 获取运营商id
		$aid = input('post.aid');
		$list =$this->region($aid);
		if($list){
			$this->result($list,1,'获取列表成功');

		}else{	

			$this->result('',0,'暂未设置供应地区');
		}

	}

	/**
	 * 售卡详情
	 * @return [type] [description]
	 */
	public function card()
	{
		$aid = input('post.aid');
		$page = input('post.page')?:1;
		$pageSize = 6;
		$count = Db::table('u_card uc')
					->join('cs_shop cs','uc.sid = cs.id')
					->where(['cs.aid'=>$aid,'uc.pay_status'=>1])
					->count();
		$rows = ceil($count / $pageSize);
		// 获取该运营商已通过审核正常运行的修车厂列表
		$list = Db::table('u_card uc')
					->join('cs_shop cs','uc.sid = cs.id')
					->where(['cs.aid'=>$aid,'uc.pay_status'=>1])
					->page($page,$pageSize)
					->order('uc.id desc')
					->field('card_number,plate,cs.company,cs.leader,phone,card_type,sale_time,card_price')
					->select();
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}

	/**
	 * 邦保养服务次数详情
	 * @return [type] [description]
	 */
	public function serviceDetail()
	{
		$aid = input('post.aid');
		$page = input('post.page')?:1;
		$pageSize = 6;
		$count = Db::table('cs_income ci')
					->join('cs_shop cs','ci.sid = cs.id')
					->where(['cs.aid'=>$aid])
					->count();
		$rows = ceil($count / $pageSize);
		// 获取该运营商已通过审核正常运行的修车厂列表
		$list = Db::table('cs_income ci')
					->join('cs_shop cs','ci.sid = cs.id')
					->where(['cs.aid'=>$aid])
					->page($page,$pageSize)
					->order('ci.id desc')
					->field('odd_number,cs.company,cs.leader,cs.phone,ci.create_time')
					->select();
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}



	/**
	 * 当月的第一天
	 * @return [type] [description]
	 */
	private function monthFirst()
	{
		return date('Y-m-01 00:00:00',strtotime(date("Y-m-d")));
		
	}

	/**
	 * 当月的最后一天
	 * @return [type] [description]
	 */
	private function monthLast()
	{
		$first = date('Y-m-01 00:00:00',strtotime(date("Y-m-d")));
		return date('Y-m-d 23:59:59', strtotime("$first +1 month -1 day"));
	}



	/**
	 * 判断此修车厂是否是新开
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	private function ifNew($data)
	{
		foreach ($data as $k => $v) {
				$audit_time = date('Y-m-d H:i:s',$v['audit_time']);
			if($audit_time >= $this->monthFirst() && $v['audit_time'] <= $this->monthLast()){
				$data[$k]['if_new'] = "是";
			}else{
				$data[$k]['if_new'] = "否";
			}
		};
		return $data;
	}

	/**
	 * 系统消息详情
	 * @return [type] [description]
	 */
	public function msg()
	{
		$id = input('post.id');
		$message = Db::table('am_msg')
		           ->where('id',$id)
		           ->find();
		$message['sendto'] = explode(",", $message['sendto']);
		return json($message);
	}







}