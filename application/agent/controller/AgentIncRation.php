<?php
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;
use think\facade\Log;
use Epay\Epay;
/**
* 测试运营商提高配给
*/
class AgentIncRation extends Admin
{
	public function date()
	{
		$date = input('post.date');
		echo date('Y-m-d',$date);
	}
		/**
		 * 等待审核列表
		 * @return [type] [description]
		 */
		public function waitAudit()
		{
			
			$page = input('post.page')? :1;
			$this->index(0,$page);
		}

		/**
		 * 已审核列表
		 */
		public function Audit()
		{
			$page = input('post.page')? :1;
			$this->index(1,$page);
		}


		/**
		 * 驳回列表
		 * @return [type] [description]
		 */
		public function reject()
		{
			$page = input('post.page')? :1;
			$this->index(2,$page);
		}



		/**
		 * 提高配给详情
		 * @param  [type] $id  [description]提高配给的订单唯一id
		 * @param  [type] $aid [description]运营商id
		 * @return [type]      [description]
		 */
		public function detail()
		{
			Log::record('错误信息');
			Log::save();
			$id = input('post.id');
			$aid = input('post.aid');
			$detail = Db::table('ca_increase ci')
					->join('ca_agent ca','ci.aid = ca.aid')
					// ->leftJoin('cg_supply su','ca.gid = su.gid')
					->where(['ci.aid'=>$aid,'id'=>$id])
					->field('ci.id,ci.aid,ci.voucher,ca.phone,ci.regions,ci.price,ci.area')
					->find();
			// print_r($detail);exit;
			// 查看运营商所选区域有无市级代理
			$city = Db::table('co_china_data')->whereIn('id',$detail['area'])->value('pid');
			// print_r($city);exit;
			$count = Db::table('cg_area')->whereIn('area',$city)->value('gid');
			if($count > 0){
				$company = Db::table('cg_supply')->where('gid',$count)->value('company');
				$detail['company'] = $company;
			}
			//判断运营商是否已经配给过，第一次配给设定维修厂分成比例及延迟罚款工时费
			$agent_set = Db::table('ca_agent_set')->where('aid',$aid)->field('delay_fine,profit,shop_hours')->find();
			if(!empty($agent_set)){
				$detail['delay_fine'] = $agent_set['delay_fine'];
				$detail['profit'] = $agent_set['profit'];
				$detail['shop_hours'] = $agent_set['shop_hours'];
			}else{
				$detail['delay_fine'] = '';
				$detail['profit'] ='';
				$detail['shop_hours'] = '';
			}
			if($detail){
				$this->result($detail,1,'获取数据成功');
			}else{
				$this->result('',0,'获取数据失败');
			}
		}


		/**
		 * 物料配给  点击区域个数显示
		 * @return [type] [description]
		 */
		public function detailArea()
		{
			// 获取数据id
			$aid = input('post.id');
			$list =$this->showRegion($aid);
			if($list){
				$this->result($list,1,'获取列表成功');
			}else{	
				$this->result('',0,'暂未设置供应地区');
			}

		}


		/**
		 * 配给通过操作
		 * @return [type] [description]
		 */
		public function adopt()
		{	
			// 获取该配给的订单id、运营商id、区域个数,本次押金 获取售卡利润、送货延迟罚款、汽修厂工时费、汽修厂成长基金、汽修厂好评奖励
			$data = input('post.');
			// print_r($data);exit;
			Db::startTrans();
			// 获取运营商提高配给的地区id
			$county = Db::table('ca_increase')->where('id',$data['id'])->value('area');
			// 给运营商添加库存，配给
			if($this->agentRation($data['id'],$data['aid'],$data['regions'],$data['price'])) 
			{

				// 查看提高配给表是否已有数据
				// 服务经理奖励
				$count = Db::table('ca_increase')->where(['aid'=>$data['aid'],'audit_status'=>1])->count();
				// print_r($count);echo $data['aid'];exit;
				if($count <= 0){
					Db::table('ca_agent')->where('aid',$data['aid'])->update(['status'=>2]);
					// 判断是否选择服务经理
					if((int)$data['mid'] !== 0){
						// 如果有服务经理则绑定服务经理
						Db::table('ca_agent')->where('aid',$data['aid'])->setField('sm_id',$data['mid']);
						// 获取运营商地区
						$address = Db::table('co_china_data')->whereIn('id',$county)->column('name');
						$address = implode(',',$address);
						// 查看该服务经理是否有本地区的权限
						// 获取运营商供应地区的市级id
						$city = Db::table('co_china_data')->whereIn('id',$county)->value('pid');
						// 查询该市级id名称
						$province = Db::table('co_china_data')->where('id',$city)->value('name');
						$sm_area = Db::table('sm_area')
								->where(['area'=>$city,'sm_id'=>$data['mid'],'audit_status'=>1,'sm_type'=>1])
								->where('sm_mold','<>',2)
								->field('team_raw')
								->find();
						// 判断该服务经理是否有开发本地区的权限
						if(empty($sm_area)){
							$sm_reward = $this->smReward($data['mid'],$data['aid'],$province);
						}else if($sm_area['team_raw'] == 1){
							$sm_reward = $this->smReward($data['mid'],$data['aid'],$province);
						}
					}
					
					
				}
				

				// 查看运营商附表是否有数据 添加运营商工时费
				$agent_count = Db::table('ca_agent_set')->where(['aid'=>$data['aid']])->count();
				if($agent_count <= 0){
					// 设置售卡利润、送货延迟罚款、汽修厂工时费
					$set_agent = Db::table('ca_agent_set')->strict(false)->insert(['aid'=>$data['aid'],'profit'=>$data['profit'],'delay_fine'=>$data['delay_fine'],'shop_hours'=>$data['shop_hours'],'service_id'=>$data['mid']]);
				}else{
					$set_agent = Db::table('ca_agent_set')->strict(false)->where('aid',$data['aid'])->update(['profit'=>$data['profit'],'delay_fine'=>$data['delay_fine'],'shop_hours'=>$data['shop_hours']]);
				}


				if($set_agent === false){
					Db::rollback();
					$this->result('',0,'审核失败,请联系技术人员');
				}
				// 修改提高配给订单的审核为已审核 和审核时间
				$res = Db::table('ca_increase')->where('id',$data['id'])->setField(['audit_status'=>1,'audit_time'=>time()]);
			
				if($res !== false){
					// 将字符串转换维数组
					$county = explode(',',$county);
					foreach ($county as $k => $v) {
						$area[]=['area'=>$v,'aid'=>$data['aid']];// 获取运营商所选择的区域
					}

					$ress = $this->setSupply($data['id'],$data['aid']);
					
					$res=Db::table('ca_area')->insertAll($area);
					
					if($res){
							// 日志写入
							$aid = DB::table('ca_increase')->where('id',$data['id'])->value('aid');
							$GLOBALS['err'] = $this->ifName().'通过了运营商【'.$this->yName($aid).'】的配给申请'; 
					        $this->estruct();
							Db::commit();
							$this->result('',1,'操作成功操作');	
					}else{
						Db::rollback();
						$this->result('',0,'操作失败');
					}
						
				}else{
					Db::rollback();
					$this->result('',0,'操作失败');
				}
			}else{
				Db::rollback();
				$this->result('',0,'增加运营商库存失败');
			}

		}


		/**
		 * 服务经理列表（下拉列表）
		 */
		public function setList()
		{
			$list = Db::table('sm_user')->where('person_rank',1)->field('id,name')->select();
			if($list){
				$this->result($list,1,'服务经理列表成功');
			}else{
				$this->result('',0,'服务经理列表失败');
			}
		}


		/**
		 * 服务经理奖励
		 * @return [type] [description]
		 * $mid 服务经理id
		 * $company 运营商公司名称
		 */
		public function smReward($mid,$aid,$area)
		{
			// 查询服务经理 银行账号、账户、银行编码
			$order = Db::table('sm_user')->where('id',$mid)->field('id,account,bank_name,bank_code,phone,balance')->find();
			// print_r($order);exit;
			// 获取审核人姓名
			$audit_person = Db::table('am_auth_user')->where('uid',$this->admin_id)->value('uname');
			// 获取团队奖励金额
			$money = Db::table('am_sm_set')->where('status',1)->value('team_reward');
			// 订单编码
			$odd_number = build_order_sn();
			// 收取手续费1.5/1000
       		$cmms = ($money*15/10000 < 1 ) ? 1 : $money*15/10000;
       	 	$cash = $money - $cmms;
	        // 进行提现操作
	        $epay = new Epay();
	        $res = $epay->toBank($odd_number,$order['account'],$order['bank_name'],$order['bank_code'],$cash*100,'服务经理开发奖励');
	        // 提现成功后操作
	        if($res['return_code']=='SUCCESS' && $res['result_code']=='SUCCESS'){
	        	// 更新提现表内容
	        	$arr = [
	        		'odd_number'=>$odd_number,
	        		'sm_id'=>$mid,
	        		'bank_code'=>$order['bank_code'],
	        		'account'=>$order['account'],
	        		'account_name'=>$order['bank_name'],
	        		'money'=>$money,
					'audit_time' => time(),
					'audit_person' => $audit_person,
					'audit_status' => 1,
					'trade_no'=>$res['payment_no'],
					'wx_cmms' => $cmms,
		            'cmms_amt' => $res['cmms_amt']/100,
		            'if_read'=>2,
		            // 'sur_amount'=>$order['balance']
				];
				// print_r($arr);
				$save = Db::table('sm_apply_cash')->insert($arr);
				// 更新收入表内容
				//获取运营商公司名称
				$company = Db::table('ca_agent')->where('aid',$aid)->value('company');
				$odd = build_order_sn();
				$data = [
					'odd_number'=>$odd,
					'sm_id'=>$mid,
					'company'=>$company,
					'money'=>$money,
					'address'=>$area,
					'type'=>1,
				];
				$res = Db::table('sm_income')->insert($data);
				// 增加服务经理的余额
				$balance = Db::table('sm_user')->where('id',$mid)->setInc('balance',$money);
				if($save && $res){
					// 处理短信参数
					$time = time();
					$time = date('Y-m-d',$time);
		        	// 发送短信给服务经理
		        	$content = "您于【".$time."】开发的【".$company."】运营商已通过审核,团队奖励已转至您的银行卡,24小时内托管银行支付到账（节假日顺延）！";
		        	$send = $this->sms->send_code($order['phone'],$content);
		        	 // 日志写入
					$GLOBALS['err'] = $this->ifName().'将服务经理'.$this->mName($mid).'团队'.$company.'的奖励转至银行卡'; 
			        $this->estruct();

					return true;
				}else{
					// 进行异常处理
					$errData = ['apply_id'=>$id,'apply_cate'=>1,'audit_person'=>$audit_person];
					Db::table('am_apply_cash_error')->insert($errData);
					return false;
				}
	        }else{
	        	// 返回错误信息
	        	$this->result('',0,$res['err_code_des']);
	        }

		}


		/**
		 * 配给驳回操作
		 * @return [type] [description]
		 */
		public function rejec()
		{	
			// 获取驳回理由、获取运营商id、获取订单id
			$data = input('post.');
			if(empty($data['reason'])){
				$this->result('',0,'驳回理由不能为空');
			}
			// 修改提高配给表的状态为驳回状态   2
			$result = Db::table('ca_increase')->where('id',$data['id'])->update(['audit_status'=>2,'audit_time'=>time(),'reason'=>$data['reason']]);
			// 查看提高配给表是否已有数据
			$count = Db::table('ca_increase')->where(['aid'=>$data['aid'],'audit_status'=>1])->count();
			if($count <= 0) Db::table('ca_agent')->where('aid',$data['aid'])->update(['status'=>5]);
			// 日志写入
				$aid = DB::table('ca_increase')->where('id',$data['id'])->value('aid');
				$GLOBALS['err'] = $this->ifName().'驳回了运营商【'.$this->yName($aid).'】的配给申请'; 
		        $this->estruct();
			if($result !== false){
				$this->result('',1,'驳回成功');
			}else{
				$this->result('',0,'驳回失败');
			}
		}



		/**
		 * 驳回列表   查看驳回理由
		 * @return [type] [description]
		 */
		public function showRea()
		{
			$id = input('post.id');
			$reason = Db::table('ca_increase')->where('id',$id)->value('reason');
			if($reason){
				$this->result($reason,1,'获取驳回理由成功');
			}else{
				$this->result('',0,'获取驳回理由失败');
			}
		}

		/**
		 * 运营商绑定市级代理 xjm
		 * @param [type] $area [订单id]
		 */
		private function setSupply($id,$aid){
			$county = Db::table('ca_increase')->where('id',$id)->value('area');
			$county = explode(',',$county);
			//获取这个区县所在的市
			$city = Db::table('co_china_data')
					->where('id',$county[0])
					->value('pid');
			$gids = Db::table('cg_area')
					->select();
			$gid = 0;
			foreach($gids as $k => $v){
				if($city == $v['area'])
					// echo 1;exit;
					$gid = $v['gid'];
					continue;
			}
			if($gid > 0){
				Db::table('ca_agent')->where('aid',$aid)->setField('gid',$gid);
				Db::table('cg_supply')->where('gid',$gid)->setInc('agent_nums');
			}
			
		}



		/**
		 * 运营商提高配给列表
		 * @param  [type] $status [description]
		 * @return [type]         [description]
		 */
		private function index($status,$page)
		{
			// print_r($status);exit;
			$pageSize = 10;
			$count = Db::table('ca_increase')->where(['audit_status'=>$status,'pay_status'=>1])->count();
			$rows = ceil($count / $pageSize);
			// 显示已上传营业执照、配给表里未审核的数据
			$list = Db::table('ca_agent ca')
					->join('ca_increase ci','ca.aid = ci.aid')
					->join('co_system_fee sf','ca.aid = sf.uid')
					->order('id desc')
					->where(['ci.audit_status'=>$status,'sf.pay_type'=>2,'ci.pay_status'=>1])
					->field('ci.id,ci.aid,company,phone,price,ci.regions,leader,ci.create_time,ci.audit_time,sf.total_fee')
					->order('ca.aid desc')
					->page($page,$pageSize)
					->select();
			// Log::record('测试调试错误信息');
			// Log::save();
			// print_r($list);exit;
			if($count > 0){
				$this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
			}else{
				$this->result('',0,'暂无数据');
			}

		}



		/**
		 * 增加运营商的库存
		 * @param  [type] $aid    [description]
		 * @param  [type] $region [description]
		 * @return [type]         [description]
		 */
		private function agentRation($id,$aid,$region,$deposit)
		{
			$count = Db::table('ca_ration')->where('aid',$aid)->count();
			// 判断是第一次配给还是提高配给
			if($count > 0){
				// 大于0 为提高配给   直接修改运营商的库存
				foreach ($this->bangCate() as $k => $v) {
					$res = Db::table('ca_ration')
						->where(['aid'=>$aid,'materiel'=>$v['id']])
						->inc('ration',$v['def_num']*$deposit/7000)
						->inc('materiel_stock',$v['def_num']*$deposit/7000)
						->inc('open_stock',$v['def_num']*$deposit/7000)
						->update();
				}

			}else{

				// 如果小于等于0则表示第一次配给运营商库存插入数据 
				$arr = $this->firstData($aid,$deposit);
				$res = Db::table('ca_ration')->insertAll($arr);
			}
			if($res){
				//把运营商此次的支付金额和可开通修车厂数量增加到运营商表
				$result = Db::table('ca_agent')
						->where('aid',$aid)
						->inc('shop_nums',$deposit/7000)
						->inc('deposit',$deposit)
						->inc('regions',$region)
						->inc('devel',$deposit/7000)
						->update();
				if($result !== false){
					return true;
				}
			}
			

		}




		/**
		 * 第一次配给所用数组
		 * @return [type] [description]
		 */
		private function firstData($aid,$deposit)
		{
			foreach ($this->bangCate() as $k => $v) {
					$arr[]=[
						'aid'=>$aid,
						'materiel'=>$v['id'],
						'ration'=>$v['def_num']*$deposit/7000,
						'warning'=>ceil($v['def_num']*$deposit*20/100/7000),
						'materiel_stock'=>$v['def_num']*$deposit/7000,
						'open_stock'=>$v['def_num']*$deposit/7000,
					];
			}
			return $arr;

		}


		// /**
		//  * 提高配给所用数组
		//  * @return [type] [description]
		//  */
		// private function IncData($deposit)
		// {
		// 	foreach ($this->bangCate() as $k => $v) {
		// 			$arr[]=[
						
		// 				'materiel'=>$v['id'],
		// 				'ration'=>$v['def_num']*$region,
		// 				'materiel_stock'=>$v['def_num']*$region,
		// 				'open_stock'=>$v['def_num']*$region,
		// 			];
		// 	}
		// 	return $arr;

		// }


		/**
		 * 获取运营商所选择的区域
		 * @param  [type] $id  [description]
		 * @param  [type] $aid [description]
		 * @return [type]      [description]
		 */
		private function delArea($id,$aid)
		{	
			// 查询运营商选择配给时的地区
			$area = Db::table('ca_increase')->where('id',$id)->value('area');
			$area = explode(',',$area);
			// 查询该运营商地区表里有没有这次选择的地区，有删除，没有返回true;
			$area_id = Db::table('ca_area')->where('aid',$aid)->column('area');
			// 比较两个数组的值获取相同的值
			$are = array_intersect($area,$area_id);
			return $are;

		}






		



}