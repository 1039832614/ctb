<?php
namespace app\agent\controller;
use app\base\controller\Agent;
use think\Db;
/**
* 修车厂取消合作
*/
class ShopCancel extends Agent
{
	/**
	 * 运营商直接取消和修车厂的合作
	 * @return [type] [description]
	 */
	public function agentShop()
	{
		Db::startTrans();
		$data = input('post.');
		// 修改修车厂取消合作表
		// $time = Db::table('cs_apply_cancel')->where('id',$data['id'])->update(['audit_status'=>1,'audit_time'=>time()]);
		// if($time !== false){
			if($this->shopNum($data['sid'],$this->aid) !== false){	
				// 让用户换店
				// 修改修车厂状态为取消合作  4
				$audit_status = Db::table('cs_shop')
								->where('id',$data['sid'])
								->setField('audit_status',4);
				if($audit_status !== false){
					// 增加运营商授信库存
					if($this->agentMateriel($data['sid'],$this->aid) == true){
						if($this->clearRation($data['sid']) == true){
							// 给运营商发送短信
							$res = $this->oilSms($data['sid'],$data['company'],$data['reason']);
                            if($res == '提交成功'  || $res == '该修车厂没有用户'){
                            	Db::commit();
								$this->result('',0,'操作成功,请在通过列表查看需要油品详情');
                            }else{
                            	Db::rollback();
								$this->result('',0,$res);
                            }
						}else{
							Db::rollback();
							$this->result('',0,'清空修车厂库存失败');
						}
						
					}else{
						Db::rollback();
						$this->result('',0,$this->agentMateriel($data['sid'],$this->aid));
					}

				}else{
					Db::rollback();
					$this->result('',0,'修改状态失败');
				}
			}else{
				Db::rollback();
				$this->result('',0,'增加开通店铺失败');
			}
		// }else{
		// 	Db::rollback();
		// 	$this->result('',0,'修改取消合作状态失败');
		// }	
	}

	/**
	 * 已审核列表
	 * @return [type] [description]
	 */
	public function auditIndex()
	{
		$page = input('post.page');
		return $this->auditStatus(1,$page);
	}

	/**
	 * 修车厂取消合作未审核列表
	 * @return [type] [description]
	 */
	public function index()
	{
		$page = input('post.page');
		return $this->auditStatus(0,$page);
	}

	/**
	 * 修车厂取消合作申请列表
	 * @return [type] [description]
	 */
	public function auditStatus($status,$page)
	{
		$pageSize = 10;
		$count = Db::table('cs_apply_cancel')
					->where([
						'aid'=>$this->aid,
						'audit_status'=>$status
					])->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('cs_apply_cancel cac')
				->join('cs_shop cs','cs.id=cac.sid')
				->where(['cac.aid'=>$this->aid,'cac.audit_status'=>$status])
				->field('cac.id,cac.sid,cac.company,cac.leader,phone,service_num,cac.reason,cac.create_time,cac.audit_time')
				->order('id desc')
				->page($page,$pageSize)
				->select();
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}

	/**
	 * 修车厂取消合作运营商同意，运营商选择给修车厂留油或者让用户换店
	 * @return [type] [description]
	 */
	public function adopt()
	{
		// 获取修车厂名称，取消合作理由，状态类型 1留油到修车厂  2用户换店 修车厂id  取消合作订单id
		$data = input('post.');
		Db::startTrans();
		if(empty($data['reason'])){
			Db::rollback();
			$this->result('',0,'取消合作理由不能为空');
		}
		// 修改修车厂取消合作表
		$time = Db::table('cs_apply_cancel')
				->where('id',$data['id'])
				->update([
					'audit_status'=>1,
					'audit_time'=>time()
				]);
		if($time !== false){
			if($this->shopNum($data['sid'],$this->aid) !== false){
				// 判断是更换店铺还是留油，更换店铺给用户发送短信，留油不用发送短信直接结算
				if($data['num'] == 1){
					 //修改修车厂状态为取消合作继续做邦保养状态
					 $audit_status = Db::table('cs_shop')
					 					->where('id',$data['sid'])
					 					->setField('audit_status',6);
					 if($audit_status !== false){
			 			// 获取修车厂做完邦保养剩余的库存
					 	// 补充运营商库存
					 	$inc = $this->userOil($data['sid'],$this->aid,$data['id']);
					 	if($inc == true){
					 		Db::commit();
					 		$this->result('',1,'取消合作成功,详细的物料交接，请查看：取消合作->取消通过->详情');
					 	}else{
					 		Db::rollback();
					 		$this->result('',0,$inc);
					 	}
					 }else{
					 	Db::rollback();
					 	$this->result('',0,'修改状态失败');
					 }
				}else{
					// 让用户换店
					// 修改修车厂状态为取消合作  4
					$audit_status = Db::table('cs_shop')
										->where('id',$data['sid'])
										->setField('audit_status',4);
					
					if($audit_status !== false){
						// 增加运营商授信库存
						if($this->agentMateriel($data['sid'],$this->aid) == true){
							if($this->clearRation($data['sid']) == true){
								// 给运营商发送短信
								$res = $this->oilSms($data['sid'],$data['company'],$data['reason']);
                                if($res == '提交成功'  || $res == '该修车厂没有用户'){
                                	Db::commit();
									$this->result('',0,'操作成功,请在通过列表查看需要油品详情');
                                }else{
                                	Db::rollback();
									$this->result('',0,$res);
                                }
							}else{
								Db::rollback();
								$this->result('',0,'清空修车厂库存失败');
							}
						}else{
							Db::rollback();
							$this->result('',0,$this->agentMateriel($data['sid'],$this->aid));
						}
					}else{
						Db::rollback();
						$this->result('',0,'修改状态失败');
					}
				}
			}else{
				Db::rollback();
				$this->result('',0,'增加修车厂开店数量失败');
			}
		}else{
			Db::rollback();
			$this->result('',0,'修改取消状态失败');
		}
	}

	/**
	 * 继续做邦保养，获取修车厂把做邦保养的油留在店里还剩多少库存
	 * @param  [type] $sid [description]修车厂id
	 * @param  [type] $aid [description]运营商id
	 * @param  [type] $id  [description]取消合作订单id
	 * @return [type]      [description]
	 */
	public function userOil($sid,$aid,$id)
	{
		$ration = $this->shopRation($sid);
		$user = $this->userCar($sid);
		foreach ($ration as $k => $v) {
			foreach ($user as $key => $value) {
				if($value['oil'] == $v['materiel']){
					// 需要交接的物料=现有物料-用户做邦保养需要的物料
					$ration[$k]['stock'] = $v['stock']-$value['remain_times']*$value['litre'];
				}
				
			}
		}
		// 获取物料名称和物料数量
		foreach ($ration as $ke => $valu) {
			$data[$ke]=['name'=>$valu['name'],'stock'=>$valu['stock']];
		}
		$arr= ['detail'=>$data];
		// 把运营商需要回收的物料已json形式修改到修理厂取消合作表
		$shop_cancel = Db::table('cs_apply_cancel')->json(['detail'])->where('sid',$sid)->update($arr);

		if($shop_cancel !== false){

			foreach ($ration as $kk => $va) {
				// where 条件   运营商id  物料种类id
				$where=['aid'=>$aid,'materiel'=>$va['materiel']];
				// 获取修车厂配给相差数量
				$materiel_stock = $va['ration']-$va['stock'];
				// 增加授信库
				$res = Db::table('ca_ration')->where($where)->inc('open_stock',$va['stock']+$materiel_stock)->update();
				// 减少补给库数量补充给授信库
				$result = Db::table('ca_ration')->where($where)->dec('materiel_stock',$materiel_stock)->update();
				
			}
			if($result !== false){
					
					$r = Db::table('cs_ration')->where('sid',$sid)->setField('stock',0);
					// 判断是否有用户
					if(empty($user)){
						return true;
					}
					// 获取用户需要做邦保养用油
					foreach ($user as $kkk => $vvv) {
						// 把
						$re = Db::table('cs_ration')
								->where([
									'sid'=>$sid,
									'materiel'=>$vvv['oil']
								])
								->setField('stock',$vvv['remain_times']*$vvv['litre']);
					}
					if($re !== false){
						return true;
					}else{
						return '修改修车厂库存失败';
					}
			}else{
				return '减少运营商补给库失败';
			}
		}else{
			return '物料交接单错误';
		}
		

	}


	/**
	 * 审核通过列表详情
	 * @return [type] [description] 交接油品。  用户信息
	 */
	public function detail()
	{	
		$sid = input('post.sid');
		$ration = Db::table('cs_apply_cancel')
					->where('sid',$sid)
					->json(['detail'])
					->find();
		$user_oil = $this->userCar($sid);
		return $data = ['ration'=>$ration['detail'],'user_oil'=>$user_oil];
	}



	/**
	 * 减少运营商补货库存
	 * @param  [type] $aid [description]
	 * @return [type]      [description]
	 */
	private function agentMateriel($sid,$aid)
	{
		$data=$this->shopRation($sid);
		foreach ($data as $k => $v) {
			$where=['aid'=>$aid,'materiel'=>$v['materiel']];
			// 运营商减少补货库存（给授信库增加）
			$materiel_stock=$v['ration']-$v['stock'];
			// 增加授信库
			$open = Db::table('ca_ration')
						->where($where)
						->inc('open_stock',$v['stock']+$materiel_stock)
						->update();
			// 减少补给库数量补充给授信库
			$res = Db::table('ca_ration')
					->where($where)
					->dec('materiel_stock',$materiel_stock)
					->update();
		}

		if($res !== false){
			// 把运营商和修车厂需要交接的物料插入到修车厂取消合作表
			foreach ($data as $key => $value) {
				$data[$key]=['name'=>$value['name'],'stock'=>$value['stock']];
			}
			$arr=['detail'=>$data];
			$res = Db::table('cs_apply_cancel')
					->json(['detail'])
					->where('sid',$sid)
					->update($arr);
			if($res !== false){
				return true;
			}	
		}else{
			return '减少补给库失败';
		}

	}


	/**
	 * 获取修车厂剩余库存量
	 * @param  [type] $sid [修车厂id]
	 * @return [type]      [description]
	 */
	private function shopRation($sid)
	{
		return Db::table('cs_ration cr')
				->join('co_bang_cate bc','cr.materiel = bc.id')
				->where('sid',$sid)
				->field('sid,materiel,ration,stock,name')
				->select();
	}

	/**
	 * 发送短信给用户
	 * @param  [type] $sid     [description]
	 * @param  [type] $content [description]
	 * @return [type]          [description]
	 */
	public function oilSms($sid,$company,$reason)
	{	
		// $con=$company.'因'.$reason.'停止邦保养服务,您可以'.$content;
        $con = '【'.$company.'】'.'因,【'.$reason.'】停止邦保养服务，您可以更换到附近其他维修厂。';
		$user_data=$this->userCar($sid);
		if($user_data){
			foreach ($user_data as $k => $v) {
				$res = $this->smsVerify($v['phone'],$con);
			}
			if($res == "提交成功"){
				return  $res;
			}else{
				return $res;
			}	
		}else{
			return '该修车厂没有用户';
		}
	}

	/**
	 *获取修车厂用户电话、车辆用油升数、车牌号
	 * @param  [type] $sid [修车厂id]
	 * @return [type]      [description]
	 */
	private function userCar($sid)
	{
		return Db::table('u_card uc')
				->join('u_user uu','uc.uid = uu.id')
				->join('co_bang_data bd','uc.car_cate_id = bd.cid')
				->where([['uc.sid','=',$sid],['uc.remain_times','>',0]])
				->field('phone,name,oil_name,litre,plate,uc.oil,remain_times')
				->select();
	
	}

	/**
	 * 清空修车厂的库存
	 * @return [type] [description]
	 */
	private function clearRation($sid)
	{
		$arr = Db::table('cs_ration')
				->where('sid',$sid)
				->field('ration,warning')
				->find();
		if(!empty($arr)){
			$res=Db::table('cs_ration')->where('sid',$sid)->delete();
			if($res){
				return true;
			}
		}else{
			return true;
		}
	}
	/**
	 * 给运营商增加开通修理厂数量
	 * @param  [type] $sid [description] 修理厂id
	 * @param  [type] $aid [description] 运营商id
	 * @return [type]      [description]
	 */
	private function shopNum($sid,$aid)
	{
		// 根据修车厂配给计算运营商增加的可开通修理厂数量
		// 获取修车厂的配给数量
		$arr = Db::table('cs_ration')
				->where('sid',$sid)
				->field('ration,warning')
				->find();
		if(empty($arr)){
			return true;
		};
		$num = $arr['ration']/72;
		$res = Db::table('ca_agent')
				->where('aid',$aid)
				->inc('shop_nums',$num)
				->dec('open_shop',$num)
				->update();
		if($res !== false){
			return true;
		}	
	}
}