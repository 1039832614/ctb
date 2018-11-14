<?php
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;
/**
* 保单审核
*/
class PolicyAudit extends admin
{
	
	/**
	 * 等待审核列表
	 * @return [type] [description]
	 */
	public function auditStatus()
	{
		$page = input('post.page')?:1;
		$this->list($page,0);
	}

	/**
	 * 审核通过列表
	 * @return [type] [description]
	 */
	public function adoptList()
	{
		$page = input('post.page')?:1;
		$this->list($page,1);
	}


	/**
	 * 保单过期列表
	 * @return [type] [description]
	 */
	public function overList()
	{
		$page = input('post.page')?:1;
		$this->list($page,2);
	}


	/**
	 * 保单驳回列表
	 * @return [type] [description]
	 */
	public function rejList()
	{
		$page = input('post.page')?:1;
		$this->list($page,3);
	}


	/**
	 * 审核通过
	 * @return [type] [description]
	 */
	public function adopt()
	{
		//获取保单id  用户id
		$id = input('post.pid');
		$uid = input('post.uid');
		if(empty($id) || empty($uid)) $this->result('',0,'缺少保单id参数');

		$content = "您于【".date('Y-m-d')."】上传的保单，已通过审核。详情请进入会员专属小程序查看。";
		$this->auditPost($id,$uid,$content,1);

	}

	/**
	 * 审核驳回
	 * @return [type] [description]
	 */
	public function reject()
	{
		//获取保单id  用户id
		$id = input('post.pid');
		$uid = input('post.uid');
		$reason = input('post.reason');
		if(empty($id) || empty($uid) || empty($reason)) $this->result('',0,'缺少重要参数');
		$content = "您于【".date('Y-m-d')."】上传的保单，因【".$reason."】被驳回。详情请进入车服管家小程序查看。";
		$this->auditPost($id,$uid,$content,3,$reason);
	}


	/**
	 * 列表详情
	 * @return [type] [description]
	 */
	public function detail()
	{
		//获取保单id
		$id = input('post.pid');
		if(empty($id)) $this->result('',0,'缺少重要参数,请看接口文档！');

		$list = Db::table('cb_policy_sheet ps')
				->join('cb_user bu','ps.u_id = bu.u_id')
				->join('co_car_cate cc','bu.car_cate_id = cc.id')
				->where('ps.pid',$id)
				->field('bu.u_id,bu.name,bu.phone,cc.type,bu.eq_num,ps.company,ps.total,ps.create_time,ps.pid,ps.start_time,ps.end_time,ps.name_price,ps.pc_img,ps.policy_num')
				->json(['pc_img'])
				->find();
		$list['name_price'] = json_decode($list['name_price'],true);
		if($list){
			$this->result($list,1,'获取成功');
		}else{
			$this->result('',0,'获取失败');
		}


	}


	/**
	 * 审核
	 * @return [type] [description]
	 */
	private function auditPost($id,$uid,$content,$status,$reason = '')
	{	
		Db::startTrans();
		//获取管理员名称
		$person = Db::table('am_auth_user')->where('uid',$this->admin_id)->value('uname');
		$data = [
			'status'=>$status,
			'audit_person'=>$person,
			'audit_time'=>date('Y-m-d H:i:s'),
			'reason'=>$reason,
		];
		$res = Db::table('cb_policy_sheet')->where('pid',$id)->update($data);
		if($res !== false){
			// 获取用户手机号发送短信
			$phone = Db::table('cb_user')->where('u_id',$uid)->field('phone,name')->find();
			// $this->smsVerify($phone['phone'],$content);
			//写入日志
			if(empty($reason)){
				$GLOBALS['err'] = $this->ifName().'通过了【'.$phone['name'].'】的保单申请';
				$this->estruct();
			}else{
				$GLOBALS['err'] = $this->ifName().'因【'.$reason.'】驳回了【'.$phone['name'].'】的保单申请';
				$this->estruct();
			}
			Db::commit();
			$this->result('',1,'成功');
		}else{
			Db::rollback();
			$this->result('',0,'失败');
		}
	}







	private function list($page,$status)
	{
		$pageSize = 10;
		$count = Db::table('cb_policy_sheet')->where('status',$status)->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('cb_policy_sheet ps')
				->join('cb_user bu','ps.u_id = bu.u_id')
				->join('co_car_cate cc','bu.car_cate_id = cc.id')
				->where('ps.status',$status)
				->page($page,$pageSize)
				->field('bu.u_id,bu.name,bu.phone,cc.type,bu.eq_num,bu.plate,ps.company,ps.total,ps.create_time,ps.pid,ps.reason,ps.policy_num,ps.audit_time')
				->order('id desc')
				->select();
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
		}else{
			$this->result('',0,'暂无数据');
		}

	}
}