<?php
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;
/**
* 总后台登录
*/
class SystemSetup extends Admin
{

	/**
	 * 轮播图上传
	 * @return 轮播图上传后的路径地址
	 */
	public function uploadPic()
	{
		return upload('image','bby','http://cc.ctbls.com/');
	}

	public function oilList()
	{
		$page = input('post.page') ? :1;
		$pageSize = 4;
		// 查询油品信息
		$count = Db::table('co_bang_cate_about')->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('co_bang_cate_about')
				->field('id,name,cover')
				->order('id desc')
				->page($page,$pageSize)
				->select();
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
		}else{
			$this->result('',0,'暂无数据');
		}

	}


	/**
	 * 设置油品下拉框选择油品名称
	 * @param string $value [description]
	 */
	public function oilName()
	{
		$data = Db::table('co_bang_cate')->field('id,name')->where([['id','<>',1],['id','<>','6']])->select();
		if($data){
			$this->result($data,'1','获取数据成功');
		}else{
			$this->result('',0,'暂无数据');
		}	
	}

	/**
	 * 设置油品数据
	 */
	public function setOil()
	{
		// 油名称、油id、油图片、油简介、油价钱
		$data = input('post.');
		$validate = validate('SetOil');
		if($validate->check($data)){
			$res = Db::table('co_bang_cate_about')->strict(false)->insert($data);
			if($res){
				// 日志写入
				$GLOBALS['err'] = $this->ifName().'新增了'.$data['name'].'油品数据'; 
				$this->estruct();
				$this->result('',1,'设置成功');
			}else{
				$this->result('',0,'设置失败');
			}
		}else{
			$this->result('',0,$validate->getError());
		}

	}


	/**
	 * 修改油品页面
	 */
	public function setIndex()
	{
		$id = input('post.id');

		$list = Db::table('co_bang_cate_about')->where('id',$id)->find();
		if($list){
			$this->result($list,1,'获取数据成功');
		}else{
			$this->result('',0,'获取数据失败');
		}

	}


	/**
	 * 修改油品操作
	 */
	public function setOper()
	{
		//自增id、油id、油图片、油简介、油价钱
		$data = input('post.');
		$validate = validate('SetOper');
		if($validate->check($data)){
			$res = Db::table('co_bang_cate_about')->strict(false)->where('id',$data['id'])->update($data);
			if($res !== false){
				// 日志写入
				$GLOBALS['err'] = $this->ifName().'修改了'.$data['name'].'油品数据'; 
				$this->estruct();	
				$this->result('',1,'修改成功');
			}else{
				$this->result('',0,'修改失败');
			}
		}else{
			$this->result('',0,$validate->getError());
		}

	}

	/**
	 * 删除油品简介
	 * @return [type] [description]
	 */
	public function delOil()
	{
		$id = input('post.id');
		$res = Db::table('co_bang_cate_about')->where('id',$id)->delete();
		if($res){
			// 日志写入
			$name = Db::table('co_bang_cate_about')->where('id',$id)->value('name');
			$GLOBALS['err'] = $this->ifName().'删除了'.$name.'油品数据'; 
			$this->estruct();
			$this->result('',1,'删除成功');
		}else{
			$this->result('',0,'删除失败');
		}
	}

	/**
	 * 查看油品详情
	 * @return [type] [description]
	 */
	public function detail()
	{
		$id = input('post.id');
		$detail = Db::table('co_bang_cate_about')->where('id',$id)->find();
		if($detail){
			$this->result($detail,1,'获取详情成功');
		}else{
			$this->result('',0,'获取详情失败');
		}
	}



}