<?php 
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;
/**
 * 反馈管理
 */
class Feedback extends Admin
{
	/**
	 * 反馈列表
	 * @param  $owner 反馈对象   1修车厂   2运营商
	 * @param  $page 当前页数
	 * @return $list 列表 $rows 总页数
	 */
	public function list($owner,$page)
	{
		$pageSize = 10;
		$count = Db::table('co_feed_back')
				 ->where('owner',$owner)
				 ->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('co_feed_back')
				->where('owner',$owner)
				->order('create_time desc')	
		   		->field('id,company,title,create_time,print_status')
		   		->page($page,$pageSize)
		   		->select();
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
		} else {
			$this->result('',0,'暂无数据');
		}
	}

	/**
	 * 汽修厂反馈列表
	 */
	public function shopList()
	{
		$page = input('post.page')? : 1;
		$this->list(1,$page);
	}

	/**
	 * 运营商反馈列表
	 * @return [type] [description]
	 */
	public function agentList()
	{
		$page = input('post.page')? : 1;
		$this->list(2,$page);
	}

	/**
	 * 反馈详情
	 */
	public function feedbackDetail()
	{
		$id = input('post.id');
		$feedback = Db::table('co_feed_back')
					->where('id',$id)
					->field('company,phone,title,content,address,create_time')
					->find();
		if($feedback) {
			
			$this->result($feedback,1,'获取数据成功');
		} else {
			$this->result('',0,'获取数据失败');
		}
	}

	/**
	 * 删除反馈信息
	 */
	public function delFeedback()
	{
		$id = input('post.id');
		$res = Db::table('co_feed_back')
				->where('id',$id)
				->delete();
		if($res) {
			// 日志写入
			$company = Db::table('co_feed_back')->where('id',$id)->value('company');
			$GLOBALS['err'] = $this->ifName().'删除了'.$company.'反馈信息'; 
			$this->estruct();
			$this->result('',1,'删除成功');
		} else {
			$this->result('',0,'删除失败');
		}
	}

	/**
	 * 打印后改变状态
	 */
	public function exStatus()
	{
		$id = input('post.id');
		$res = Db::table('co_feed_back')
				->where('id',$id)
				->setField('print_status', '1');
		if($res){
			$this->result('',1,'已打印');
		} else {
			$this->result('',0,'已打印');
		}
	}
}