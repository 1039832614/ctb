<?php
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;
/**
 * 礼品兑换
 */
class GiftExchange extends Admin
{
	/**
	 * 列表
	 * @param  [type] $page  页数
	 * @param  [type] $gcate 礼品兑换的类别
	 * @return [type] $list 带有分页的信息列表
	 */
	public function index($page,$gcate)
	{
		$pageSize = 10;
		$count = Db::table('cs_gift')->where('gcate',$gcate)->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('cs_gift')
		 		->alias('g')
		 		->join('cs_shop s','g.sid = s.id')
		 		->join('u_card c','g.cid = c.id')
		 		->join('u_user u','c.uid = u.id')
		        ->where('gcate',$gcate)
		        ->where('g.status',2)
		        ->order('g.ex_time')
		        ->page($page,$pageSize)
		        ->field('s.company,u.phone,u.name,g.excode,ex_time')
		        ->select();
		if($count > 0){
            $this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
        }else{
            $this->result('',0,'暂无数据');
        }        
	}

	/**
	 * 兑换大礼包列表
	 * @return [type] [description]
	 */
	public function giftPackList(){
		$page = input('post.page')? : 1;
		$this->index($page,1);
	}

	/**
	 * 兑换OBD列表
	 */
	public function OBDList(){
		$page = input('post.page')? : 1;
		$this->index($page,2);
	}

	/**
	 * 兑换服务列表
	 * @return [type] [description]
	 */
	public function serviceList(){
		$page = input('post.page')? : 1;
		$pageSize = 10;
		$count = Db::table('cs_gift')->where('gcate',3)->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('cs_gift')
		 		->alias('g')
		 		->join('cs_shop s','g.sid = s.id')
		 		->join('u_card c','g.cid = c.id')
		 		->join('u_user u','c.uid = u.id')
		        ->where('gcate',3)
		        ->where('g.status',2)
		        ->order('g.ex_time')
		        ->page($page,$pageSize)
		        ->field('s.company,u.phone,u.name,g.excode,ex_time')
		        ->select();
		foreach ($list as $key => $value) 
		{
        		$list[$key]['subsidy'] = 100;//添加补助金额
	    }

		if($count > 0){
            $this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
        }else{
            $this->result('',0,'暂无数据');
        }        
	}
}