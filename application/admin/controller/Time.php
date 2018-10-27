<?php
namespace app\admin\controller;
use app\base\controller\Base;
use think\Db;
/**
* 定时脚本
*/
class Time extends Base
{ 

	public function test()
	{
		$arr = [
			'title'=>'测试',
			'content'=>'测试',
			'sendto'=>4,
			'create_person'=>'测试',
		];
		Db::table('am_msg')->insert($arr);
	}

	// /**
	//  * 用户购卡未支付的数据清除
	//  * @return [type] [description]
	//  */
	// public function payCard()
	// {
	// 	Db::table('u_card')->where('pay_status',0)->delete();
	// }


	// //修车厂24小时未审核转账
	// public function TranAcc()
	// {
	// 	// 获取提现申请表未审核数据
	// 	$data = Db::table('cs_apply_cash')->field('sid,odd_number,account,account_name,bank_code,money,create_time')->where('audit_status',0)->select();
	// 	foreach ($data as $k => $v) {
	// 		// 收取手续费1.5/1000
	//         $cmms = ($v['money']*15/10000 < 1 ) ? 1 : $v['money']*15/10000;
	//         $cash = $v['money'] - $cmms;
	//         // 进行提现操作
	//         $epay = new Epay();
	//         $res = $epay->toBank($v['odd_number'],$v['account'],$v['account_name'],$v['bank_code'],$v['money'],$v['account_name'].'测试提现');
	//         if($res['return_code']=='SUCCESS' && $res['result_code']=='SUCCESS'){
	//         	// 更新数据
	//         	$arr = [
	// 				'audit_time' => time(),
	// 				'audit_status' => 1,
	// 				'wx_cmms' => $cmms,
	// 	            'cmms_amt' => $res['cmms_amt']/100
	// 			];
	// 			$save = Db::table('cs_apply_cash')->where('id',$id)->update($arr);
	// 			if($save !== false){
	// 				// 处理短信参数
	// 				$time = $order['create_time'];
	// 				$money = $order['money'];
	// 	        	// 发送短信给运营商
	// 	        	$content = "您于【{$time}】提交的【{$money}】元的提现申请，通过审核，24小时内托管银行支付到账（节假日顺延）！";
	// 	        	$send = $this->sms->send_code($mobile,$content);
	// 				$this->result('',1,'处理成功');
	// 			}else{
	// 				// 进行异常处理
	// 				$errData = ['apply_id'=>$id,'apply_cate'=>1];
	// 				Db::table('am_apply_cash_error')->insert($errData);
	// 				$this->result('',0,'打款成功，处理异常，请联系技术部');
	// 			}
	//         }else{
	//         	// 返回错误信息
 //        		$this->result('',0,$res['err_code_des']);
	//         }
	// 	}
		

	// }


	// public function agentAcc()
	// {
	// 	// 获取提现申请表未审核数据
	// 	$data = Db::table('ca_apply_cash')->field('sid,odd_number,account,account_name,bank_code,money,create_time')->where('audit_status',0)->select();
	// 	foreach ($data as $k => $v) {
	// 		// 收取手续费1.5/1000
	//         $cmms = ($v['money']*15/10000 < 1 ) ? 1 : $v['money']*15/10000;
	//         $cash = $v['money'] - $cmms;
	//         // 进行提现操作
	//         $epay = new Epay();
	//         $res = $epay->toBank($v['odd_number'],$v['account'],$v['account_name'],$v['bank_code'],$v['money'],$v['account_name'].'测试提现');
	//         if($res['return_code']=='SUCCESS' && $res['result_code']=='SUCCESS'){
	//         	// 更新数据
	//         	$arr = [
	// 				'audit_time' => time(),
	// 				'audit_status' => 1,
	// 				'wx_cmms' => $cmms,
	// 	            'cmms_amt' => $res['cmms_amt']/100
	// 			];
	// 			$save = Db::table('ca_apply_cash')->where('id',$id)->update($arr);
	// 			if($save !== false){
	// 				// 处理短信参数
	// 				$time = $order['create_time'];
	// 				$money = $order['money'];
	// 	        	// 发送短信给运营商
	// 	        	$content = "您于【{$time}】提交的【{$money}】元的提现申请，通过审核，24小时内托管银行支付到账（节假日顺延）！";
	// 	        	$send = $this->sms->send_code($mobile,$content);
	// 				$this->result('',1,'处理成功');
	// 			}else{
	// 				// 进行异常处理
	// 				$errData = ['apply_id'=>$id,'apply_cate'=>1];
	// 				Db::table('am_apply_cash_error')->insert($errData);
	// 				$this->result('',0,'打款成功，处理异常，请联系技术部');
	// 			}
	//         }else{
	//         	// 返回错误信息
 //        		$this->result('',0,$res['err_code_des']);
	//         }
	// 	}
	// }


}