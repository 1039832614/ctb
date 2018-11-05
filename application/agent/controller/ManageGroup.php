<?php
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;
/**
* 用户组
*/
class ManageGroup extends Admin
{
	/**
	 * 用户组列表
	 * @return [type] [description]
	 */
	public function groupList()
	{
		$page = input('post.page')? :1;
		$pageSize = 10;
		$count = Db::table('am_auth_role')->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('am_auth_role')
				->page($page,$pageSize)
				->select();
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取用户组成功');
		}else{
			$this->result('',0,'暂无数据');
		}

	}

	/**
	 * 用户组添加
	 */
	public function addGroup()
	{
		$data = input('post.');
		$count = Db::table('am_auth_role')->where('rname',$data['rname'])->count();
		if(!empty($data['rname']) && $count <= 0){
			$res = Db::table('am_auth_role')->strict(false)->insert($data);
			if($res){
				$this->result('',1,'添加用户组成功!');
			}else{
				$this->result('',0,'添加用户组失败!');
			}
		}else{
			$this->result('',0,'用户组名不能为空或用户组已存在!');
		}
	}

	/**
	 * 给用户组分配权限页面
	 * @return [type] [description]
	 */
	public function authGroup()
	{
		// 查询权限表
		$rid = input('post.rid');
		// 根据用户组ID  查看之前是否有已分配的权限
		$arr = Db::table('am_rule_role')->where('role_id',$rid)->column('rule_id');
		$data = Db::table('am_auth_auth')->select();
		if($data){
			$this->result(['data'=>get_child($data),'check'=>$arr],1,'获取权限信息成功');
		}else{
			$this->result('',0,'获取权限信息失败');
		}
		
	}

	/**
	 * 给用户组添加权限
	 */
	public function addAuth()
	{
		// 获取角色id  获取所选择的的权限id
		$data = input('post.');
		foreach ($data['rule_id'] as $k => $v) {
			$arr[] = ['rule_id'=>$v,'role_id'=>$data['role_id']];
		}
		Db::startTrans();
		// 先删除原来的用户组权限
		Db::table('am_rule_role')->where('role_id',$data['role_id'])->delete();
		// 插入选择好的用户组权限
		$res = Db::table('am_rule_role')->insertAll($arr);
		if($res){
			Db::commit();
			$this->result('',1,'用户组添加权限成功');
		}else{
			Db::rollback();
			$this->result('',0,'用户组添加权限失败');
		}
	}

	/**
	 * 用户组修改页面
	 * @return [type] [description]
	 */
	public function modifyIndex()
	{
		$rid = input('post.rid');
		$rname = Db::table('am_auth_role')->where('rid',$rid)->value('rname');
		if($rname){
			$this->result($rname,1,'获取信息成功');
		}else{
			$this->result('',0,'获取信息失败');
		}
	}

	/**
	 * 修改用户组操作
	 * @return [type] [description]
	 */
	public function modifyPost()
	{
		$data = input('post.');
		$res = Db::table('am_auth_role')->where('rid',$data['rid'])->setField('rname',$data['rname']);
		if($res !== false){
			$this->result('',1,'修改用户组成功');
		}else{
			$this->result('',0,'修改用户组失败');
		}
	}

	/**
	 * 用户组删除
	 * @return [type] [description]
	 */
	public function groupDel()
	{
		$rid = input('post.rid');
		Db::startTrans();
		$res = Db::table('am_auth_role')->where('rid',$rid)->delete();
		if($res){
			//查看用户组和权限是否有数据
			$rule_role = Db::table('am_rule_role')->where('role_id',$rid)->count();
			if($rule_role <= 0){
				// 如果用户组权限关联表没有数据，直接删除成功。
				Db::commit();
				$this->result('',1,'删除用户组成功');
			}else{
				// 如果用户组权限关联表有数据，则进行删除。
				$result = Db::table('am_rule_role')->where('role_id',$rid)->delete();
				if($result){
					Db::commit();
					$this->result('',1,'删除用户组成功');
				}else{
					Db::rollback();
					$this->result('',0,'删除用户组失败');
				}
			}
		}else{
			Db::rollback();
			$this->result('',0,'删除用户组失败');
		}
	}
}