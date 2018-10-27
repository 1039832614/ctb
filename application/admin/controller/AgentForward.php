<?php
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;
use Msg\Sms;
use Pay\Epay;
/**
* 运营商提现
*/
class AgentForward extends Admin
{
	/**
	 * 运营商提现等待审核列表
	 * @return [type] [description]
	 */
	public function waitList()
	{
		$page = input('post.page');
		$this->index($page,0);
	}



	/**
	 * 运营商提现已审核列表
	 * @return [type] [description]
	 */
	public function auditList()
	{
		$page = input('post.page');
		$this->index($page,1);
	}



	/**
	 * 运营商提现驳回列表
	 * @return [type] [description]
	 */
	public function rejList()
	{
		$page = input('post.page');
		$this->index($page,2);
	}


	/**
	 * 收入明细列表
	 * @return [type] [description]
	 */
	public function incomeList()
	{
		$aid  = input('post.aid');
		$page = input('post.page')? : 1;
		$pageSize = 10;
		$count = Db::table('ca_income')->where('aid',$aid)->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('ca_income')
				->where('aid',$aid)
				->field('form,amount,create_time')
				->order('id desc')->page($page,$pageSize)
				->select();
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}


	/**
	 * 提现明细
	 * @return [type] [description]
	 */
	public function forwardList()
	{
		$aid = input('post.aid');
		$page = input('post.page')? :1;
		$pageSize = 10;
        $count = Db::table('ca_apply_cash')->where(['aid'=>$aid,'audit_status'=>1])->count();

        $rows = ceil($count / $pageSize);

        $list = Db::table('ca_apply_cash')
                ->where(['aid'=>$aid,'audit_status'=>1])
                ->order('id desc')->page($page,$pageSize)
                ->field('audit_time,money,sur_amount')
                ->select();

        if($count > 0){
            $this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
        }else{
            $this->result('',0,'暂无数据');
        }   
	}


	/**
	 * 提现审核列表
	 * @return [type] [description]
	 */
	public function audit()
	{
		// 获取运营商电话
		$phone = input('post.phone');
		// 获得运营商id
		$aid = input('post.aid');
		// 获取本次提现审核的时间和金额
		$now = Db::table('ca_apply_cash')->where(['aid'=>$aid,'audit_status'=>0])->field('id,aid,money,sur_amount,create_time')->find();

		$old = Db::table('ca_apply_cash')
				->where(['aid'=>$aid,'audit_status'=>1])
				->order('audit_time desc')->limit(1)
				->find();
				
		$list = [
			'id'=>$now['id'],
			'aid'=>$now['aid'],
			'old_money'=>$old['money'],
			'old_time'=>$old['create_time'],
			'now_money'=>$now['money'],
			'now_amount'=>$now['sur_amount'],
			'phone'=>$phone,
			'create_time'=>$now['create_time']
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
	public function adopt()
	{
		$id = input('post.id');
		$mobile = input('post.phone');
		// 获取提现信息
		$order = Db::table('ca_apply_cash')->field('aid,odd_number,account,account_name,bank_code,money,create_time')->where('id',$id)->find();
		// 获取审核人姓名
		$audit_person = Db::table('am_auth_user')->where('uid',$this->admin_id)->value('uname');
		// 收取手续费1.5/1000  cjx2018-10-8
        $cmms = ($order['money']*15/10000 < 1 ) ? 1 : round($order['money']*15/10000,2);
        $cash = $order['money'] - $cmms;
        // 进行提现操作
        $epay = new Epay();
        $res = $epay->toBank($order['odd_number'],$order['account'],$order['account_name'],$order['bank_code'],$cash*100,$order['account_name'].'测试提现');
        // 提现成功后操作
        if($res['return_code']=='SUCCESS' && $res['result_code']=='SUCCESS'){
        	// 更新数据
        	$arr = [
				'audit_time' => time(),
				'audit_person' => $audit_person,
				'audit_status' => 1,
				'wx_cmms' => $cmms,
	            'cmms_amt' => $res['cmms_amt']/100
			];
			$save = Db::table('ca_apply_cash')->where('id',$id)->update($arr);
			if($save !== false){
				// 处理短信参数
				$time = $order['create_time'];
				$money = $order['money'];
	        	// 发送短信给运营商
	        	$content = "您于【{$time}】提交的【{$money}】元的提现申请，通过审核，24小时内托管银行支付到账（节假日顺延）！";
	        	$send = $this->sms->send_code($mobile,$content);
	        	 // 日志写入
				$GLOBALS['err'] = $this->ifName().'通过了运营商为【'.$this->yName($order['aid']).'】的提现申请'; 
		        $this->estruct();

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
		// 获取运营商电话，驳回理由，提现申请时间，订单id，运营商本次提现金额,运营商id
		$data = input('post.');
		if(empty($data['reason'])){
			$this->result('',0,'驳回理由不能为空');
		}
		Db::startTrans();
		// 获取管理员名称
		$audit_person = Db::table('am_auth_user')->where('uid',$this->admin_id)->value('uname');
		$arr =[
			'audit_status'=>2,
			'audit_time'=>time(),
			'audit_person'=>$audit_person,
			'reason'=>$data['reason'],
		];
		$res = Db::table('ca_apply_cash')->where('id',$data['id'])->update($arr);
		if($res !== false){
			// 驳回成功把运营商的余额增加
			$result = Db::table('ca_agent')->where('aid',$data['aid'])->inc('balance',$data['money'])->update();

			if($result !== false){

				// 给运营商发送短信
				$tx = '提现';
				$content = "您于【".$data['create_time']."】的【".$tx."】申请，因【".$data['reason']."】被驳回，请完成修订后重新提交。";
				// print_r($content);exit;
				$sms = $this->smsVerify($data['phone'],$content);
				if($sms == '提交成功'){
					// 日志写入
					$aid = DB::table('ca_apply_cash')->where('id',$data['id'])->value('aid');
					$GLOBALS['err'] = $this->ifName().'驳回了运营商【'.$this->yName($aid).'】的提现申请'; 
			        $this->estruct();
					Db::commit();
					$this->result('',1,'驳回成功,已给运营商发送短信');
				}else{
					Db::rollback();
					$this->result('',0,$sms);
				}
			}else{
				Db::rollback();
				$this->result('',0,'给运营商余额增加失败');
			}
			
		}else{
			Db::rollback();
			$this->result('',0,'修改状态成功');
		}

	}



	/**
	 * 点击驳回理由显示内容
	 * @return [type] [description]
	 */
	public function forReason()
	{
		$id = input('post.id');
		$reason = $this->reason($id,'ca_apply_cash','reason');
		if($reason){
			$this->result($reason,1,'获取理由成功');
		}else{
			$this->result('',0,'获取理由失败');
		}
	}


	/**
	 * 获取提现列表
	 * @param  [type] $page   [description]
	 * @param  [type] $status [description]
	 * @return [type]         [description]
	 */
	private function index($page,$status)
	{
		$pageSize = 10;
		$count = Db::table('ca_apply_cash')->where('audit_status',$status)->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('ca_apply_cash ac')
				->join('ca_agent ca','ac.aid = ca.aid')
				->where('ac.audit_status',$status)
				->field('ac.id,ac.aid,company,phone,leader,money,ac.create_time,ac.audit_time')
				->order('id desc')
				->page($page,$pageSize)
				->select();
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
		}else{
			$this->result('',0,'暂无数据');
		}

	}
}