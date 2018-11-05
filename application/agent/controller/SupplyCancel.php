<?php
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;
/**
* 市级代理取消合作
*/
class SupplyCancel extends Admin
{
	/**
	 * 取消合作申请列表
	 * @return [type] [description]
	 */
	public function cancelApply()
	{
		// 获取当前页
		$page = input('post.page')?:1;
		$list = $this->index($page,0);
		if($list) $this->result($list,1,'获取申请列表成功');
		$this->result('',0,'暂无数据');
	}

	/**
	 * 通过列表
	 * @return [type] [description]
	 */
	public function cancelAdopt()
	{
		// 获取当前页
		$page = input('post.page')?:1;
		$list = $this->index($page,1);
		if($list) $this->result($list,1,'获取通过列表成功');
		$this->result('',0,'暂无数据');
	}

	/**
	 * 驳回列表
	 * @return [type] [description]
	 */
	public function cancelReject()
	{
		// 获取当前页
		$page = input('post.page')?:1;
		$list = $this->index($page,2);
		if($list) $this->result($list,1,'获取驳回列表成功');
		$this->result('',0,'暂无数据');
	}

	/**
	 * 滑动公司名称显示详情
	 * @param  [type] $gid [供应商id]
	 * @return [type] [description]
	 */
	public function company($gid)
	{
		$list = Db::table('cg_supply')->where('gid',$gid)->field('balance')->find();
		$count = Db::table('ca_agent')->where('gid',$gid)->count();
		$list['count'] = $count;
		return $list;
	}

	/**
	 * 获取市级代理取消合作的理由
	 * @param  [type] $id [取消合作订单id]
	 * @return [type]     [description]
	 */
	public function canReason($id)
	{	
		$reason = Db::table('cg_apply_cancel')->where('id',$id)->value('reason');
		if($reason) $this->result($reason,1,'获取取消合作理由成功');
		$this->result('',0,'暂无数据');
	}


	/**
	 * 获取市级代理取消合作的驳回理由
	 * @param  [type] $id [取消合作订单id]
	 * @return [type]     [description]
	 */
	public function regReason($id)
	{	
		$reason = Db::table('cg_apply_cancel')->where('id',$id)->value('rej_reason');
		if($reason) $this->result($reason,1,'获取取消合作理由成功');
		$this->result('',0,'暂无数据');
	}


	/**
	 * 取消合作通过操作
	 * @return [type] [description]
	 */
	public function adopt($id)
	{
		Db::startTrans();
		// 修改取消合作表的状态
		$res = Db::table('cg_apply_cancel')->where('id',$id)->update(['status'=>1,'audit_time'=>time(),'if_ration'=>1,'if_deposit'=>1]);
		// 查询市级代理id
		$gid = Db::table('cg_apply_cancel')->where('id',$id)->value('gid');
		// print_r($res);exit;
		// 修改市级代理的状态
		$supply = Db::table('cg_supply')->where('gid',$gid)->update(['status'=>6]);
		// 判断市级代理有无物料
		$count = Db::table('cg_stock')->where('gid',$gid)->count();
		if($count > 0){
			// 清除市级代理的物料
			$result = Db::table('cg_stock')->where('gid',$gid)->delete();
		}else{
			$result = true;
		} 
		// 判断市级代理有无供应地区
		$ration = Db::table('cg_area')->where('gid',$gid)->count();
		if($ration){
			// 清除地区表
			$ress = Db::table('cg_area')->where('gid',$gid)->delete();
		}else{
			$ress = true;
		}
		if($res !== false && $supply !== false && $result && $ress){
			Db::commit();
			$this->result('',1,'取消合作成功');
		}else{
			Db::rollback();
			$this->result('',0,'取消合作失败');
		}
	}

	/**
	 * 取消合作驳回
	 * @return [type] [description]
	 * $reason 驳回理由
	 * $id    订单id
	 */
	public function reject($reason,$id)
	{	
		// 修改取消合作表
		$res = Db::table('cg_apply_cancel')->where('id',$id)->update(['rej_reason'=>$reason,'status'=>2,'audit_time'=>time()]);
		if($res !== false){
			$this->result('',1,'驳回成功');
		}else{
			$this->result('',0,'驳回失败');
		}
	}


	/**
	 * 取消合作通过查看物品交接
	 * @param  [type] $id [取消合作订单id]
	 * @return [type]     [description]
	 */
	public function handGood($id)
	{
		$list = Db::table('cg_apply_cancel')->where('id',$id)->json(['detail'])->find();
		if($list) $this->result($list['detail'],1,'获取物品交接详情成功');
		$this->result('',0,'暂无数据');
	}


	/**
	 * 取消合作列表
	 * @param  [type] $page   [当前页]
	 * @param  [type] $status [状态]
	 * @return [type]         [description]
	 */
	private function index($page,$status)
	{
		$pageSize = 10;
		$count = Db::table('cg_apply_cancel')->where('status',$status)->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('cg_apply_cancel')
				->where('status',$status)
				->page($page,$pageSize)
				->field('company,leader,phone,create_time,audit_time,id,gid,rej_reason,reason,audit_time')
				->order('id desc')
				->select();
		foreach ($list as $k => $v) {
			$list[$k]['com_detail'] = $this->company($v['gid']);
		}
		if($count > 0) return ['list'=>$list,'rows'=>$rows];
	}
}