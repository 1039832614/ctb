<?php
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;
/**
* 小程序发票管理
*/
class SmallInvoice extends Admin
{
	
	/**
	 * 申请列表
	 * @return [type] [description]
	 */
	public function applyList()
	{
		$page = input('post.page')? : 1;
		$this->index($page,0);
	}

	// 列表
	private function index($page,$status)
	{
		$pageSize = 10;
		$count = Db::table('u_tax')->where('status',$status)->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('u_tax')
				->where('status',$status)
				->order('id desc')
				->page($page,$pageSize)
				->field('id,phone,contacter,total,fee,create_time,audit_time')
				->select();
		if($count > 0){
            $this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
        }else{
            $this->result('',0,'暂无数据');
        }  
	}

	/**
	 * 送出列表
	 * @return [type] [description]
	 */
	public function sendOffList()
	{
		$page = input('post.page')? : 1;
        $this->index($page,1);
	}

	/**
	 * 金额点击弹窗（显示维修厂名称，运营商名称以及卡号）
	 * @return [type] [description]
	 */
	public function cardDetails()
	{	
		$id = input('post.id');
		$cardDetails = Db::table('u_tax')
		        ->alias('t')
		        ->join('u_card c','t.cid = c.id')
		        ->join('cs_shop s','c.sid = s.id')
		        ->join('ca_agent a','s.aid = a.aid')
		        ->field('card_number,s.company as s_company,a.company as a_company')
		        ->where('t.id',$id)
		        ->find();
		 if($cardDetails){
		 	$this->result($cardDetails,1,'获取成功');
		 }else{
		 	$this->result('',0,'获取失败');
		 }
	}

	/**
	 * 发票详情
	 * @return [type] [description]
	 */
	public function applyDetails()
	{
		$id = input('post.id');
		$applyDetails = Db::table('u_tax')
		        ->field('contacter,phone,company,address,tax_number')
		        ->where('id',$id)
		        ->find();
		if($applyDetails){
		 	$this->result($applyDetails,1,'获取成功');
		 }else{
		 	$this->result('',0,'获取失败');
		 }
	}

	/**
	 * 完成送达
	 * @return [type] [description]
	 */
	public function achieve()
	{
		$id = input('post.id');
		$time = time();
		$data = [
			'audit_time' => $time,
			'status'     => 1
		];
		$res = Db::table('u_tax')
		       ->where('id',$id)
		       ->update($data);
		if($res){
			$this->result('',1,'操作成功，已送达');
		} else {
			$this->result('',0,'操作失败，请重试');
		}
	}
}