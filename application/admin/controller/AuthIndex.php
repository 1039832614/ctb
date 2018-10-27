<?php
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;
/**
* 返回给前端那些用户组里的用户各自都有什么权限
*/
class AuthIndex extends Admin
{
	/**
	 * 给前端返回每个用户组分别有什么权限
	 * @return [type] [description]
	 */
	public function index()
	{
		$data = Db::table('am_auth_user au')
				->join('am_role_user ru','au.uid = ru.user_id')
				->join('am_auth_role ar','ru.role_id = ar.rid')
				->where('uid',$this->admin_id)
				->value('ar.rid');
		// 判断用户是否在超级管理员组
		if($data == 1){
			// 是超级管理员则全部权限都拥有
			$list = Db::table('am_auth_auth')->select();
		}else{
			// 没在超级管理员组
			$list = Db::table('am_auth_auth aa')
					->join('am_rule_role rr','aa.id = rr.rule_id')
					->join('am_auth_role ar','rr.role_id = ar.rid')
					->join('am_role_user ru','ar.rid = ru.role_id')
					->join('am_auth_user au','ru.user_id = au.uid')
					->where('uid',$this->admin_id)
					->field('action,name,pid,id,rname')
					->select();
		}
		$list = get_level($list);
		if($list){
			$this->result($list,1,'获取用户的权限');
		}else{
			$this->result('',0,'获取用户权限失败')
		}
	}
	
}