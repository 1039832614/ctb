<?php
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;
/**
* 运营商取消合作
*/
class AgentCancel extends Admin
{
	/**
	 * 取消合作申请列表
	 * @return [type] [description]
	 */
	public function waitList()
	{
		$page = input('post.page')? :1;
		$this->index($page,0);
	}



	/**
	 * 取消合作通过列表
	 * @return [type] [description]
	 */
	public function adoptList()
	{
		$page = input('post.page')? :1;
		$this->index($page,1);
	}

	/**
	 * 取消合作驳回列表
	 * @return [type] [description]
	 */
	public function rejectList()
	{
		$page = input('post.page')? :1;
		$this->index($page,2);
	}


	/**
	 * 取消合作详情
	 * @return [type] [description]
	 */
	public function detail()
	{
		$id = input('post.id');
		$list = Db::table('ca_apply_cancel ac')
				->join('ca_agent ca','ac.aid=ca.aid')
				->where('id',$id)
				->field('id,ac.company,phone,open_shop,balance,ac.audit_time,reason')
				->find();

		if($list){
			$this->result($list,1,'获取详情成功');
		}else{
			$this->result('',0,'获取详情失败');
		}
	}


	/**
	 * 点击取消理由显示内容
	 * @return [type] [description]
	 */
	public function canReason()
	{
		$id = input('post.id');
		$reason = $this->reason($id,'ca_apply_cancel','reason');
		if($reason){
			$this->result($reason,1,'获取理由成功');
		}else{
			$this->result('',0,'获取理由失败');
		}
	}


	/**
	 * 运营商取消合作确认通过
	 * @return [type] [description]
	 */
	public function adopt()
	{
		// 获取取消合作订单id、获取运营商id、获取取消合作理由、获取运营商名称
		$data = input('post.');
		Db::startTrans();
		$status = Db::table('ca_apply_cancel')->where('aid',$data['aid'])->setField('status',1);
		if($status !== false){

			// 修改运营商旗下修车厂状态为取消合作后继续做邦保养,小程序不显示。
			$audit_status = Db::table('cs_shop')->where('aid',$data['aid'])->setField('audit_status',6);
			if($audit_status !== false){

				// 修车厂除去用户的油，剩余油品。把所有修车厂都录入到修车厂取消合作表里,给运营商增加授信库存
				$shop_cancel = $this->shopCancel($data['aid']);
				if($shop_cancel !==false){
					// 获取运营商现有库存，入库到运营商取消合作表
					$agent_ration =Db::table('ca_ration cr')
									->join('co_bang_cate bc','cr.materiel = bc.id')
									->where('aid',$data['aid'])->field('aid,materiel,materiel_stock,open_stock,name')->select();
					$arr['detail']=$agent_ration;
					$result = Db::table('ca_apply_cancel')->json(['detail'])->where('aid',$data['aid'])->update($arr);
					if($result){
						//修改运营商为取消合作。
						$res = Db::table('ca_agent')->where('aid',$data['aid'])->setField('status',6);
						if($res !== false){
							 // 清除运营商的库存
							 $cra = Db::table('ca_ration')->where('aid',$data['aid'])->count();
							 if($cra > 0){
							 	$clear = Db::table('ca_ration')->where('aid',$data['aid'])->delete();
							 }else{
							 	$clear = true;
							 }
							 if($clear){
							 	// // 给修车厂发送短信
							 	$oilSms = $this->oilSms($data['aid'],$data['company'],$data['reason']);
							 	if($oilSms == '提交成功' || $oilSms == '该运营商旗下没有修车厂'){
							 		// 日志写入
				                     $name =    $this->yName($data['aid']);  
				                    $GLOBALS['err'] = $this->ifName().'同意了运营商【'.$name.'】申请的取消合作'; 
						            $this->estruct();
							 		Db::commit();
									$this->result('',1,'取消合作成功');
							 	}else{
							 		Db::rollback();
									$this->result('',0,$oilSms);
							 	}
							 	
							 }else{
							 	Db::rollback();
								$this->result('',0,'取消合作失败');
							 }
						}else{
							Db::rollback();
							$this->result('',0,'修改运营商为取消合作成功');
						}
					}else{
						Db::rollback();
						$this->result('',0,'运营商物料详情失败');
					}
				}else{
					Db::rollback();
					$this->result('',0,$shop_cancel);
				}

				
			}else{
				Db::rollback();
				$this->result('',0,'更改修车厂取消合作失败');
			}
		}else{
			Db::rollback();
			$this->result('',0,'更改修车厂取消合作失败');
		}
		
	}




	public function shopCancel($aid)
	{
		// 获取修车厂信息
		$shop = Db::table('cs_shop')->where('aid',$aid)->field('id,aid,company,leader,reason')->select();
		foreach ($shop as $k => $v) {
			$arr=[
				'sid'=>$v['id'],//修车厂id
				'aid'=>$v['aid'],//运营商id
				'company'=>$v['company'],//修车厂名称
				'leader'=>$v['leader'],//修车厂负责人
				'reason'=>'运营商取消合作。',//修车厂取消合作原因
			];
			$res = Db::table('cs_apply_cancel')->insertGetId($arr);
			$r[]=$res;
		}
		if(isset($r)){
			//运营商旗下有修车厂
			// 获取每个修车厂的库存
			$shop_ration = $this->surRation($shop);
			foreach ($shop_ration as $k => $v) {
				$where=['aid'=>$aid,'materiel'=>$v['materiel']];
				// 运营商减少补货库存（给授信库增加）
				$materiel_stock=$v['ration']-$v['stock'];
				// 增加授信库
				$open = Db::table('ca_ration')->where($where)->inc('open_stock',$v['stock']+$materiel_stock)->update();
				// 减少补给库数量补充给授信库
				$result = Db::table('ca_ration')->where($where)->dec('materiel_stock',$materiel_stock)->update();
			}
			if($result !== false){
				return true;

			}else{
				return '增加运营商授信库失败';
			}

		}else{
			// 运营商旗下没有修车厂
			return true;
		}
	}


	/**
	 * 取消合作驳回
	 * @return [type] [description]
	 */
	public function reject()
	{
		// 获取运营商id  取消合作列表id  驳回理由
		$data = input('post.');
		//修改取消合作表的驳回理由和驳回时间
		if($data['rej_reason']){
			$res = Db::table('ca_apply_cancel')->where('id',$data['id'])->update(['audit_time'=>time(),'rej_reason'=>$data['rej_reason'],'status'=>2]);
			if($res !== false){
				// 日志写入
				$GLOBALS['err'] = $this->ifName().'驳回了运营商为【'.$name.'】的取消合作申请'; 
		        $this->estruct();
				$this->result('',1,'驳回成功');
			}else{
				$this->result('',0,'驳回理由不能为空');
			}
		}else{
			$this->result('',0,'驳回理由不能为空');
		}
	}


	/**
	 * 货品交接
	 * @return [type] [description]
	 */
	public function handOver()
	{
		$aid = input('post.aid');
		$list = Db::table('ca_apply_cancel')
				->where('aid',$aid)
				->json(['detail'])
				->find();
		if($list){
			$this->result($list['detail'],1,'获取交接详情成功');
		}else{
			$this->result('',0,'获取交接详情失败');
		}
	}


	/**
	 * 获取修车厂除去需要做邦保养的油剩余的油是多少;
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	private function surRation($data)
	{
		// 获取每个修车厂的所有用户
		foreach ($data as $k => $v) {
			 $list = Db::table('u_card uc')
					->join('u_user uu','uc.uid = uu.id')
					->join('co_bang_data bd','uc.car_cate_id = bd.cid')
					->where([['uc.sid','=',$v['id']],['uc.remain_times','>',0]])
					->field('sid,phone,name,oil_name,litre,plate,uc.oil,remain_times')
					->select();
			$user_num[] = $list;
		}
		foreach ($user_num as $ke => $v) {
			foreach($v as $key=>$value){
				$arr[] = $value;
			}
		}
		$ration = $this->shopRation($data);
		if(isset($arr)){
			// 获取修车厂现有库存，判断留油之后还剩多少库存
			foreach ($ration as $kk => $vv) {
				foreach($arr as $kkk=>$vvv){
					if($vv['sid'] == $vvv['sid'] && $vv['materiel'] == $vvv['oil']){

						$ration[$kk]['stock'] = $vv['stock']-$vvv['remain_times']*$vvv['litre'];
						// 修改修车厂的库存，为仅做邦保养的库存
						Db::table('cs_ration')->where('sid',$vvv['sid'])->setField('stock',0);
						Db::table('cs_ration')->where(['sid'=>$vvv['sid'],'materiel'=>$vvv['remain_times']])->setField('stock',$vvv['remain_times']*$vvv['litre']);
					}
				}
			}
			
		}else{
			foreach ($ration as $k => $v) {
				Db::table('cs_ration')->where('sid',$v['sid'])->setField('stock',0);
			}
		}
		return $ration;

		
	}




	private function shopRation($data)
	{
		foreach ($data as $k => $v) {
			$list = Db::table('cs_ration')->where('sid',$v['id'])->select();
			$arr[] = $list;
		}
		foreach ($arr as $ke => $va) {
			foreach ($va as $key => $value) {
				$ration[] = $value;
			}
		}
		return $ration;
	}

	/**
	 * 获取运营商取消合作列表
	 * @param  [type] $page   [description]
	 * @param  [type] $status [description]
	 * @return [type]         [description]
	 */
	private function index($page,$status)
	{
		$pageSize = 10;
		$count = Db::table('ca_apply_cancel ac')->where('status',$status)->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('ca_apply_cancel ac')
				->join('ca_agent ca','ac.aid=ca.aid')
				->where('ac.status',$status)
				->order('id desc')
				->field('id,ac.aid,ac.company,ac.leader,phone,ac.create_time,ac.audit_time,ac.reason,rej_reason')
				->select();
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	
}

	/**
	 * 发送短信给修车厂
	 * @param  [type] $sid     [description]
	 * @param  [type] $content [description]
	 * @return [type]          [description]
	 */
	private function oilSms($aid,$company,$reason)
	{	
		// $con=$company.'因'.$reason.'停止邦保养服务,您可以'.$content;
        $con = '【'.$company.'】'.'因,【'.$reason.'】停止邦保养服务，您可以更换到附近其他维修厂。';
		$shop = Db::table('cs_shop')->where('aid',$aid)->field('id,aid,company,leader,reason,phone')->select();
		if($shop){
			foreach ($shop as $k => $v) {
				$res = $this->smsVerify($v['phone'],$con);
			}
			// print_r($re);exit;
			if($res == "提交成功"){
				return  $res;
			}else{
				return $res;
			}	
		}else{
			return '该运营商旗下没有修车厂';
		}
		
	}



	
}