<?php
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;
use Pay\Epay;
/**
* 市级代理资金提现
*/
class SupplyForward extends Admin
{
	/**
	 * 资金提现申请列表
	 * @return [type]       [description]
	 */
	public function forApply()
	{
		$page = input('post.page')?:1;
		$list = $this->index($page,0);
		if($list) $this->result($list,1,'获取申请列表成功');
		$this->result('',0,'暂无数据');
	}


	/**
	 * 资金提现通过列表
	 * @return [type]       [description]
	 */
	public function forAdopt()
	{
		$page = input('post.page')?:1;
		$list = $this->index($page,1);
		if($list) $this->result($list,1,'获取通过列表成功');
		$this->result('',0,'暂无数据');
	}


	/**
	 * 资金提现驳回列表
	 * @return [type]       [description]
	 */
	public function forReject()
	{
		$page = input('post.page')?:1;
		$list = $this->index($page,2);
		if($list) $this->result($list,1,'获取驳回列表成功');
		$this->result('',0,'暂无数据');
	}


	/**
	 * 收入明细   物料收入
	 * @param  [type] $gid [description]
	 * @return [type]      [description]
	 */
	public function matPrice($gid,$page)
	{
		$pageSize = 10;
		$count = Db::table('cg_apply_materiel')->where(['gid'=>$gid,'audit_status'=>1])->count();
		$rows = ceil($count / $pageSize);
		// 物料收入
		$list = Db::table('cg_apply_materiel')
				->alias('cam')
				->where(['gid'=>$gid,'audit_status'=>1])
				->page($page,$pageSize)
				->order('id desc')
				->field('price,audit_time')
				->select();
		if($count > 0) $this->result(['list'=>$list,'rows'=>$rows],1,'获取物料收入成功');
		$this->result('',0,'暂无数据');
	}


	/**
	 * 收入明细   售卡收入
	 * @return [type] [description]
	 */
	public function cardPrice($gid,$page)
	{
		$pageSize = 10;
		$count = Db::table('cg_income')->where('gid',$gid)->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('cg_income')
				->where('gid',$gid)
				->page($page,$pageSize)
				->order('id desc')
				->select();
		if($count > 0) $this->result(['list'=>$list,'rows'=>$rows],1,'获取邦保养收入成功');
		$this->result('',0,'暂无数据');
	}

	/**
	 * 提现明细
	 * @return [type] [description]
	 */
	public function putForward($gid,$page)
	{
		$pageSize = 5;
		$count = Db::table('cg_put')->where(['gid'=>$gid,'status'=>1])->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('cg_put')
				->where(['gid'=>$gid,'status'=>1])
				->page($page,$pageSize)
				->order('id desc')
				->field('putprice,Trial_time,sur_amount')
				->select();
		if($count > 0) $this->result(['list'=>$list,'rows'=>$rows],1,'获取提现明细列表成功');
		$this->result('',0,'暂无数据');

	}

	/**
	 * 提现审核列表
	 * @return [type] [description]
	 * $phone  市级代理电话
	 * $gid    市级id
	 */
	public function auditList($phone,$id)
	{
		// 获取本次提现审核的时间和金额
		$now = Db::table('cg_put')->where(['id'=>$id,'status'=>0])->field('id,gid,putprice,sur_amount,create_time')->find();
		// 上次提现
		$old = Db::table('cg_put')
				->where(['gid'=>$now['gid'],'status'=>1])
				->order('Trial_time desc')->limit(1)
				->find();
				
		$list = [ 
			'id'=>$id,
			'gid'=>$now['gid'],
			'old_money'=>$old['putprice'],
			'old_time'=>$old['create_time'],
			'now_money'=>$now['putprice'],
			'now_amount'=>$now['sur_amount'],
			'phone'=>$phone,
		];

		if($list){
			$this->result($list,1,'获取列表成功');
		}else{
			$this->result('',0,'暂无数据');
		}


	}

	/**
	 * 提现申请通过
	 * @param  string $value [description]
	 * @return [type]        [description]
	 */
	public function adopt($id,$phone)
	{
		// 获取提现信息
		$order = Db::table('cg_put')->field('gid,number,account,account_name,bank_code,putprice,create_time')->where('id',$id)->find();
		// 获取审核人姓名
		$audit_person = Db::table('am_auth_user')->where('uid',$this->admin_id)->value('uname');
		// 收取手续费1.5/1000
        $cmms = ($order['putprice']*15/10000 < 1 ) ? 1 : round($order['putprice']*15/10000,2);
        // $cmms = ($order['money']*15/10000 < 1 ) ? 1 : round($order['money']*15/10000,2);
        $cash = $order['putprice'] - $cmms;
        // 进行提现操作
        $epay = new Epay();
        $res = $epay->toBank($order['number'],$order['account'],$order['account_name'],$order['bank_code'],$cash*100,$order['account_name'].'测试提现');
        // 提现成功后操作
        if($res['return_code']=='SUCCESS' && $res['result_code']=='SUCCESS'){
        	// 更新数据
        	$arr = [
				'Trial_time' => time(),
				'Auditor' => $audit_person,
				'status' => 1,
				'wx_cmms' => $cmms,
	            'cmms_amt' => $res['cmms_amt']/100
			];
			$save = Db::table('cg_put')->where('id',$id)->update($arr);
			if($save !== false){
				// 处理短信参数
				$time = $order['create_time'];
				$money = $order['putprice'];
	        	// 发送短信给运营商
	        	$content = "您于【{$time}】提交的【{$money}】元的提现申请，通过审核，24小时内托管银行支付到账（节假日顺延）！";
	        	$send = $this->sms->send_code($phone,$content);
				$this->result('',1,'处理成功');
			}else{
				// 进行异常处理
				$errData = ['apply_id'=>$id,'apply_cate'=>1,'audit_person'=>$audit_person];
				Db::table('am_apply_cash_error')->insert($errData);
				$this->result('',0,'打款成功，处理异常，请联系技术部');
			}
        }else{
        	// 返回错误信息
        	$this->result('',0,$res['err_code_des']);
        }
	}


	/**
	 * 提现申请驳回操作
	 * @return [type] [description]
	 */
	public function reject()
	{
		// 获取市级代理电话，驳回理由,订单id
		$data = input('post.');
		if(empty($data['reason'])){
			$this->result('',0,'驳回理由不能为空');
		}
		Db::startTrans();
		// 获取管理员名称
		$audit_person = Db::table('am_auth_user')->where('uid',$this->admin_id)->value('uname');
		$arr =[
			'status'=>2,
			'Trial_time'=>time(),
			'Auditor'=>$audit_person,
			'reject'=>$data['reason'],
		];
		$res = Db::table('cg_put')->where('id',$data['id'])->update($arr);
		if($res !== false){
			// 查询市级代理本次提现金额 及市级代理id
			$put = Db::table('cg_put')->where('id',$data['id'])->field('putprice,gid,create_time')->find();
			// 驳回成功把运营商的余额增加
			$result = Db::table('cg_supply')->where('gid',$put['gid'])->setInc('balance',$put['putprice']);

			if($result !== false){

				// 给运营商发送短信
				$tx = '提现';
				$content = "您于【".$put['create_time']."】的【".$tx."】申请，因【".$data['reason']."】被驳回，请完成修订后重新提交。";
				// print_r($content);exit;
				$sms = $this->smsVerify($data['phone'],$content);
				if($sms == '提交成功'){
					// 日志写入
					// $aid = DB::table('ca_apply_cash')->where('id',$data['id'])->value('aid');
					// $GLOBALS['err'] = $this->ifName().'驳回了运营商【'.$this->yName($aid).'】的提现申请'; 
			  //       $this->estruct();
					Db::commit();
					$this->result('',1,'驳回成功,已给市级代理发送短信');
				}else{
					Db::rollback();
					$this->result('',0,$sms);
				}
			}else{
				Db::rollback();
				$this->result('',0,'给市级代理余额增加失败');
			}
			
		}else{
			Db::rollback();
			$this->result('',0,'修改状态成功');
		}

	}

	/**
	 * 获取提现驳回理由
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function rejReason($id)
	{
		// 获取驳回理由
		$reason = Db::table('cg_put')->where('id',$id)->value('reject');
		if($reason) $this->result($reason,1,'获取驳回理由成功');
		$this->result('',0,'暂无数据');
	}

	


	/**
	 * [资金提现列表]
	 * @param  [type] $page   [当前页]
	 * @param  [type] $status [状态值]
	 * @return [type]         [description]
	 */
	private function index($page,$status)
	{
		$pageSize = 10;
		$count = Db::table('cg_put')->where('status',$status)->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('cg_put')
				->alias('cp')
				->join('cg_supply cs','cp.gid = cs.gid')
				->where('cp.status',$status)
				->page($page,$pageSize)
				->field('company,leader,phone,putprice,cp.create_time,Trial_time,cp.gid,cp.id,cp.reject')
				->order('cp.id desc')
				->select();
		if($count > 0) return ['list'=>$list,'rows'=>$rows];
	}
}