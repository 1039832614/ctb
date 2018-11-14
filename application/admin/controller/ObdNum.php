<?php
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;
/**
* obd设备号总后台录入
*/
class ObdNum extends Admin
{
	/**
	 * 设备列表
	 * @return [type] [description]
	 */
	public function index()
	{
		$page = input('post.page')?:1;
		$pageSize = 10;

		$count = Db::table('cb_eq_num')->count();

		$rows = ceil($count / $pageSize);

		$list = Db::table('cb_eq_num')
				->order('id desc')
				->page($page,$pageSize)
				->select();
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取设备列表成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}


	/**
	 * 添加设备
	 */
	public function addEq()
	{
		$data['eq_num'] = input('post.eq_num');
		if(!$data['eq_num']) $this->result('',0,'缺少设备号参数');

		$res = Db::table('cb_eq_num')->insert($data);

		if($res){

			$this->result('',1,'添加设备成功');
		}else{

			$this->result('',0,'添加设备');
		}
	}


	/**
	 * 删除设备
	 * @return [type] [description]
	 */
	public function delEq()
	{
		$id = input('post.id');

		if(!$id) $this->result('',0,'缺少id参数');

		$res = Db::table('cb_eq_num')->where('id',$id)->delete();
		if($res){
			$this->result('',1,'删除成功');
		}else{
			$this->result('',0,'删除失败');
		}
	}
}