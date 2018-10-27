<?php
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;
/**
* 市级代理物料申请（预警补充）
*/
class SupplyMateriel extends Admin
{
	public function a()
	{
		$a = 'https://api.weixin.qq.com/sns/userinfo?access_token=".$access_token."&openid=".$openid."&lang=zh_CN';
		echo $a;
	}
	
	/**
	 * 申请列表
	 * @return [type] [description]
	 */
	public function apply()
	{
		$page = input('post.page') ?:1;
		$list = $this->list($page,0);
		if($list) $this->result($list,1,'获取物料申请列表成功');
		$this->result(0,0,'暂无数据');
	}

	/**
	 * 通过列表
	 * @return [type] [description]
	 */
	public function adoptList()
	{
		$page = input('post.page') ?:1;
		$pageSize = 10;
		$count = Db::table('cg_company')->where('status = 1 or status = 4')->count();
		$rows = ceil($count / $pageSize);
		$data = Db::table('cg_company')
			->alias('cam')
			->join(['cg_supply'=>'as'],'cam.gid = as.gid')
			->where('cam.status = 1 or cam.status = 4')
			->page($page,$pageSize)
			->field('cam.id,company,phone,leader,cam.create_time,cam.to_time,print,Origin')
			->select();
		$list = ['list'=>$data,'rows'=>$rows];
		if($count > 0) $this->result($list,1,'获取物料通过列表成功');
		$this->result(0,0,'暂无数据');
	}

	/**
	 * 驳回列表
	 * @return [type] [description]
	 */
	public function rejectList()
	{
		$page = input('post.page') ?:1;
		$list = $this->list($page,2);
		if($list) $this->result($list,1,'获取物料驳回列表成功');
		$this->result(0,0,'暂无数据');
	}


	/**
	 * 获取物料详情
	 * @param  [type] $id [订单id]
	 * @return [type]     [description]
	 */
	public function detail($id)
	{
		$list = $this->adoptDetail($id);
		// print_r($list);exit;
		foreach ($list as $k => $v) {
			# code...
		}
		if($list) $this->result($list,1,'获取物料详情成功');
		$this->result(0,0,'暂无数据');
	}

	/**
	 * [物料申请通过]
	 * @param  [type] $id [订单id]
	 * @return [type]     [description]
	 */
	public function adopt($id)
	{
		$uname = Db::table('am_auth_user')->where('uid',$this->admin_id)->value('uname');
		// 修改订单状态 审核时间
		$res = Db::table('cg_company')->where('id',$id)->update(['status'=>1,'to_time'=>time(),'Auditor'=>$uname]);
		// 增加市级代理的库存
		// 获取物料详情
		$detail = $this->adoptDetail($id);
		// print_r($detail['details']);exit;
		// 物料入库 写定时文件三天之后自动入库
		// 给市级代理发送短信
		$content = '您申请的货物已发出，请注意查收。';
		$send = $this->sms->send_code($detail['phone'],$content);
		if($res !== false) $this->result('',1,'通过成功');
		// // 打印的内容
		// $list = $this->adoptDetail($id);
		// // $handle = printer_open("ZDesigner 105SL 203DPI");
		// $handle = printer_open();
		// printer_set_option($handle,PRINTER_MODE,"doc");
		// printer_write($handle,$this->convertStr($list));
		// printer_close($handle);
	}

	/**
	 * 物料申请驳回
	 * @return [type] [description]
	 */
	public function reject()
	{
		$uname = Db::table('am_auth_user')->where('uid',$this->admin_id)->value('uname');
		// 获取物料订单id
		$id = input('post.id');
		// 获取驳回理由
		$reason = input('post.reason');
		$phone = input('post.phone');
		$create_time = input('post.create_time');
		// 修改该订单状态  及修改驳回理由
		$res = Db::table('cg_company')->where('id',$id)->update(['status'=>2,'Origin'=>$reason,'to_time'=>time(),'Auditor'=>$uname]);
		$content = "您于【".$create_time."】申请的物料,因【".$reason."】被驳回，请完成修订后重新提交。";
		$send = $this->sms->send_code($phone,$content);
		if($res !== false) $this->result('',1,'驳回成功');
	}
	

	/**
	 * 物料申请列表
	 * @return [type] [description]
	 */
	private function list($page,$status)
	{
		$pageSize = 10;
		$count = Db::table('cg_company')->where('status',$status)->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('cg_company')
			->alias('cam')
			->join(['cg_supply'=>'as'],'cam.gid = as.gid')
			->where('cam.status',$status)
			->page($page,$pageSize)
			->field('cam.id,company,phone,leader,cam.create_time,cam.to_time,print,Origin')
			->select();
		return ['list'=>$list,'rows'=>$rows];
	}

	/**
	 * 申请物料的详情
	 * @param  [type] $id [物料申请订单id]
	 * @return [type]     [description]
	 */
	private function adoptDetail($id)
	{
		return Db::table('cg_company')
				->alias('cc')
				->join('cg_supply cs','cc.gid = cs.gid')
				->where('cc.id',$id)
				->json(['details'])
				->field('cc.id,company,leader,phone,province,city,county,address,details,cc.create_time,cc.to_time,cc.gid')
				->find();
	}
}