<?php
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;
/**
* 运营商审核列表
*/
class AgentAuditList extends Admin
{

	/**
	 * 运营商未审核列表
	 * @return [type] [description]
	 */
	public function index()
	{
		$page = input('post.page')? : 1;
		$pageSize = 10;
		// 运营商状态为1的时候显示在审核列表，即上传了营业执照之后
		$count = Db::table('ca_agent')->where('status',1)->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('ca_agent')
				->where('status',1)
				->page($page,$pageSize)
				->field('aid,company,leader,phone,create_time')
				->select();
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
		}else{
			$this->result('',0,'暂无数据');
		}
		
	}

	 /* 审核列表详情
	 * @return [type] [description]
	 */
	public function detail()
	{
		$aid =input('post.aid');
		if(!empty($aid)){

			$list = Db::table('ca_agent ca')
					->leftJoin('cg_supply su','ca.gid = su.gid')
					->where(['aid'=>$aid,'ca.status'=>1])
					->field('aid,ca.license,su.company')
					->find();
			if($list){
				$this->result($list,1,'获取数据成功');
			}else{
				$this->result('',0,'暂无数据');
			}

		}else{
			$this->result('',0,'没有运营商信息');
		}
		
	}



	/**
	 * 审核列表通过操作
	 * @return [type] [description]
	 */
	public function  adopt()
	{
		//获取售卡利润、送货延迟罚款、汽修厂工时费、汽修厂成长基金、汽修厂好评奖励
		$data = input('post.');
		// print_r($data['card_profit']);exit;
		$validate = validate('SetAgent');
		if($validate->check($data)){
			Db::startTrans();
			// 设置售卡利润、送货延迟罚款、汽修厂工时费、汽修厂成长基金、汽修厂好评奖励
			$res = Db::table('ca_agent_set')->strict(false)->insert($data);
			$result = $this->gong($data['aid'],$data['card_profit']);
			if($res && $result == true){
				// 修改运营商的状态为已通过审核状态,修改审核时间
				$result = Db::table('ca_agent')->where('aid',$data['aid'])->update(['audit_time'=>time(),'status'=>2]);
				if($result !== false){
                    
					Db::commit();
					
					// 日志写入
					$name = Db::table('ca_agent')->where('aid',$data['aid'])->value('company');
                    $GLOBALS['err'] = $this->ifName().'通过了运营商名称为【'.$name.'】的审核'; 
		            $this->estruct();

					$this->result('',1,'操作成功');
				
				}else{
					Db::rollback();
					$this->result('',0,'操作失败');
				}
				
			}else{
				Db::rollback();
				$this->result('',0,'设置失败');
			}
		}else{
			$this->result('',0,$validate->getError());
		}

	}



	/**
	 * 运营商审核列表详情驳回操作
	 * @return [type] [description]
	 */
	public function reject()
	{
		$aid = input('post.aid');
		$reason = input('post.reason');
		Db::startTrans();
		if(!empty($reason)){
			// 删除运营商注册信息
			$res = Db::table('ca_agent')->where('aid',$aid)->delete();
			if($res){
				// 获取运营商电话
				$phone = Db::table('ca_agent')->where('aid',$aid)->value('phone');
				// 编辑短信发送内容
				$content = '您的系统开通因【'.$reason.'】被驳回,请完成修订后重新提交。';
				// 给运营商发送短信告诉他您的开通被驳回
				$sms = $this->smsVerify($phone,$content);
				if($sms == '请求成功'){
					Db::commit();
					$this->result('',1,'驳回成功');
				}else{
					Db::commit();
					$this->result('',0,$this->smsVerify($phone,$content));
				}

			}else{
				Db::rollback();
				$this->result('',0,'清除用户信息失败');
			}
		}else{
			Db::rollback();
			$this->result('',0,'驳回理由不能为空');
		}
		
		
		
	}


	/**
	 * 修改运营商状态
	 * @param [type] $status [description]
	 */
	public function setStatus($status,$aid)
	{
		$result = Db::table('ca_agent')->where('aid',$aid)->setField('status',$status);
		if($result !== false){
			return true;
		}
	}


	/**
	 * 查询该运营商是否有供应商
	 * @return [type] [description]
	 */
	private function gong($aid,$card_profit)
	{
		// 查询供应商字段
		$gid = Db::table('ca_agent')->where('aid',$aid)->value('gid');
		// 判断供应商id   大于0则有供应商    等于0 则没有供应商
		if($gid > 0){
			// $gid >0 把总后台设置的售卡百分比填写到对应的供应商表
			$res = Db::table('cg_supply_set')->where('gid',$gid)->setField('card_profit',$card_profit);
			if($res !== false){
				return true;
			}else{
				return false;
			}
		}else{
			return true;
		}
	}
	
}