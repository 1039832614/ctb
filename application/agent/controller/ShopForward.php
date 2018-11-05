<?php
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;
use Pay\Epay;
/**
* 修车厂提现
*/
class ShopForward extends Admin
{
	
	/**
	 * 提现申请列表
	 * @return [type] [description]
	 */
	public function waitList()
	{
		$page = input('post.page')? : 1;
		$this->index($page,0);
	}



	/**
	 * 提现驳回列表
	 * @return [type] [description]
	 */
	public function rejectList()
	{
		$page = input('post.page')? : 1;
		$this->index($page,2);
	}



	/**
	 * 提现通过列表
	 * @return [type] [description]
	 */
	public function adoptList()
	{
		$page = input('post.page')? : 1;
		$this->index($page,1);
	}





	/**
	 * 邦保养收入明细
	 * @return [type] [description]
	 */
	public function bangList()
	{
		$sid  = input('post.sid');
		$page = input('post.page')? : 1;
		$this->incomeList($page,'cs_income',$sid,'total','create_time');
	}



	// /**
	//  * 兑换收入明细
	//  * @return [type] [description]
	//  */
	// public function chargeList()
	// {
	// 	$sid  = input('post.sid');
	// 	$page = input('post.page')? : 1;
	// 	$this->incomeList($page,'cs_ex_income',$sid,'ex_charge','ex_time');
	// }

	/**
	 * 好评奖励
	 * @return [type] [description]
	 */
	public function praise()
	{
		//获取维修厂id
		$page = input('post.page')? : 1;
		$sid = input('post.sid');
		$pageSize = 6;
		$count = Db::table('u_comment')->where('sid',$sid)->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('u_comment')
				->where(['sid'=>$sid,'tn_star'=>5,'shop_star'=>5])
				->field('money,create_time')
				->page($page,$pageSize)
				->order('id desc')
				->select();
		// 获取用户评论表
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取好评奖励列表成功！');
		}else{
			$this->result('',0,'暂无数据！');
		}
	}

	/**
	 * 店铺奖励基金
	 * @return [type] [description]
	 */
	public function rewardFund()
	{
		$page = input('post.page')? : 1;
		$sid = input('post.sid');
		$pageSize = 6;
		$count = Db::table('cs_income')->where('sid',$sid)->count();
		$rows = ceil($count / $pageSize);
		// 获取成长基金金额及获取时间
		$list = Db::table('cs_income')
				->where('sid',$sid)
				->field('grow_up,create_time')
				->page($page,$pageSize)
				->order('id desc')
				->where('sid',$sid)
				->select();
		// 获取用户评论表
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取成长基金列表成功！');
		}else{
			$this->result('',0,'暂无数据！');
		}
	}

	/**
	 * 提现明细
	 * @return [type] [description]
	 */
	public function forwardList()
	{
		$sid = input('post.sid');
		$page = input('post.page')? :1;
		$pageSize = 10;
        $count = Db::table('cs_apply_cash')->where(['sid'=>$sid,'audit_status'=>1])->count();

        $rows = ceil($count / $pageSize);

        $list = Db::table('cs_apply_cash')
                ->where(['sid'=>$sid,'audit_status'=>1])
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
	public function auditList()
	{
		// 获取修车厂电话
		$phone = input('post.phone');
		// 获得修车厂id
		$sid = input('post.sid');
		// 获取本次提现审核的时间和金额
        $now = Db::table('cs_apply_cash')->where(['sid'=>$sid,'audit_status'=>0])->field('id,sid,money,sur_amount,create_time')->find();
        // 获取上次提现的时间和金额
        $old = Db::table('cs_apply_cash')
                ->where(['sid'=>$sid,'audit_status'=>1])
                ->order('audit_time desc')->limit(1)
                ->find();

        $list = [
            'id'=>$now['id'],
            'sid'=>$now['sid'],
            'old_money' =>$old['money'],
            'old_time'  =>$old['create_time'],
            'now_money' =>$now['money'],
            'now_amount'=>$now['sur_amount'],
            'phone'     =>$phone,
            'create_time'=>$now['create_time']
        ];

        if($list){
            $this->result($list,1,'获取列表成功');
        }else{
            $this->result('',0,'获取列表失败');
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
		$order = Db::table('cs_apply_cash')->field('sid,odd_number,account,account_name,bank_code,money,create_time')->where('id',$id)->find();
		// 获取审核人姓名
		$audit_person = Db::table('am_auth_user')->where('uid',$this->admin_id)->value('uname');
		// 收取手续费1.5/1000 //提现必须为整数不然签名会出现错误  2018-10-8 cjx修改
        // $cmms = ($order['money']*15/10000 < 1 ) ? 1 :$order['money']*15/10000;
        // print_r($cmms);
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

			$save = Db::table('cs_apply_cash')->where('id',$id)->update($arr);
			if($save !== false){
				// 处理短信参数
				$time = $order['create_time'];
				$money = $order['money'];
	        	// 发送短信给运营商
	        	$content = "您于【{$time}】提交的【{$money}】元的提现申请，通过审核，24小时内托管银行支付到账（节假日顺延）！";
	        	$send = $this->sms->send_code($mobile,$content);
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
		// 获取修理厂电话，驳回理由，提现申请时间，订单id，运营商本次提现金额,运营商id
		$data = input('post.');
		if(empty($data['reason'])){
			$this->result('',0,'请输入驳回理由！');
		}
		Db::startTrans();
		// 获取管理员名称
		$audit_person = Db::table('am_auth_user')->where('uid',$this->admin_id)->value('uname');
		$arr = [
			'audit_status'=>2,
			'reason'=>$data['reason'],
			'audit_person'=>$audit_person,
		];
		// 修改数据库状态为已驳回，修改驳回理由，修改处理人
		$res = Db::table('cs_apply_cash')->where('id',$data['id'])->update($arr);
		if($res !== false){

			// 驳回成功把运营商的余额增加
			$result = Db::table('cs_shop')->where('id',$data['sid'])->inc('balance',$data['money'])->update();

			if($result !== false){
				// 给运营商发送短信
				$tx = '提现';
				$content = "您于【".$data['create_time']."】的【".$tx."】申请，因【".$data['reason']."】被驳回，请完成修订后重新提交。";
				// print_r($content);exit;
				$sms = $this->smsVerify($data['phone'],$content);
				if($sms == '提交成功'){
					Db::commit();
					$this->result('',1,'驳回成功,已给修车厂发送短信');
				}else{
					Db::rollback();
					$this->result('',0,$sms);
				}
			}else{
				Db::rollback();
				$this->result('',0,'修车厂余额增加失败');
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
	public function shopReason()
	{
		$id = input('post.id');
		$reason = $this->reason($id,'cs_apply_cash','reason');
		if($reason){
			$this->result($reason,1,'获取理由成功');
		}else{
			$this->result('',0,'获取理由失败');
		}
	}


	/**
	 * 收入明细
	 * @return [type] [description]
	 */
	private function incomeList($page,$table,$sid,$money,$time)
	{
		$pageSize = 6;
		$count = Db::table($table)->where('sid',$sid)->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table($table)
				->where('sid',$sid)
				->field($money.','.$time)
				->order('id desc')->page($page,$pageSize)
				->select();

		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}




	/**
	 * 修车厂提现列表
	 * @param  [type] $page   [description]
	 * @param  [type] $status [description]
	 * @return [type]         [description]
	 */
	private function index($page,$status)
	{	
		$pageSize = 10;
		$count = Db::table('cs_apply_cash')->where('audit_status',$status)->count();
		// print_r($count);exit;
		$rows = ceil($page / $pageSize);
		$list = Db::table('cs_apply_cash ac')
				->join('cs_shop cs','ac.sid = cs.id')
				->where('ac.audit_status',$status)
				->order('ac.id desc')
				->field('ac.sid,ac.id,company,leader,phone,money,ac.create_time,ac.audit_time')
				->page($page,$pageSize)
				->select();
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
		}else{
			$this->result('',0,'暂无数据');
		}

	}
}