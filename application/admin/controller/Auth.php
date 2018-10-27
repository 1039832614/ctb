<?php
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;
/**
* 权限管理
*/
class Auth extends Admin
{
	/**
	 * 权限列表
	 * @return [type] [description]
	 */
	public function authList()
	{
		$list = Db::table('am_auth_auth')
				->order('id desc')
				->field('name,action,id,pid')->where('status',1)->select();
		$list = get_level($list);
		if($list){
			$this->result($list,1,'获取列表成功');
		}else{
			$this->result('',0,'暂无数据');
		}
		
	}



	/**
     * 判断角色
     * @return [type] [description]
     */
    public function ifUser()
    {   
        $role = Db::table('am_role_user')->where('user_id',$this->admin_id)->column('role_id');
        if(in_array(1,$role)){
        	// 超级管理员获取的权限
        	$list = Db::table('am_auth_user au')
                ->join('am_role_user ru','au.uid = ru.user_id')
                ->join('am_auth_role ar','ru.role_id = ar.rid')
                ->join('am_rule_role rr','ar.rid = rr.role_id')
                ->join('am_auth_auth aa','rr.rule_id = aa.id')
                ->group('rule_id')
                // ->where('aa.status',1)
                ->field('aa.id,name,action,icon,aa.pid')
                ->select();
        }else{
        	// 查询多个用户组所拥有的权限并去重(获取用户组所拥有的的权限)
        	$list = Db::table('am_auth_user au')
                ->join('am_role_user ru','au.uid = ru.user_id')
                ->join('am_auth_role ar','ru.role_id = ar.rid')
                ->join('am_rule_role rr','ar.rid = rr.role_id')
                ->join('am_auth_auth aa','rr.rule_id = aa.id')
                ->group('rule_id')
                ->where(['user_id'=>$this->admin_id])
                ->field('aa.id,name,action,icon,aa.pid')
                ->select();
        }
        $list = get_child($list);
        // print_r($list);exit;
        if($list){
        	$this->result($list,1,'获取权限成功！');
        }else{
        	$this->result('',0,'获取权限失败！');
        }

    }


    // 获取二级管理
    public function erAuth()
    {
    	// 
    	$id = input('post.id');
    	// 查询本用户的用户
    	$role = Db::table('am_role_user')->where('user_id',$this->admin_id)->column('role_id');
    	// 权限的二级权限
    	$rule_id = Db::table('am_auth_auth')->where('pid',$id)->column('id');
    	// 获取权限的三级权限
    	$san = Db::table('am_auth_auth')->whereIn('pid',$rule_id)->column('id');
    	$arr = array_merge($rule_id,$san);
    	// print_r($arr);exit;
    	// 根据二级权限查看用户组和用户权限关系表查看用户拥有哪些权限
    	$list = Db::table('am_auth_user au')
                ->join('am_role_user ru','au.uid = ru.user_id')
                ->join('am_auth_role ar','ru.role_id = ar.rid')
                ->join('am_rule_role rr','ar.rid = rr.role_id')
                ->join('am_auth_auth aa','rr.rule_id = aa.id')
                ->group('rule_id')
                ->field('aa.id,name,action,icon,aa.pid')
                ->whereIn('rr.rule_id',$arr)
                ->whereIn('rr.role_id',$role)
                ->where('status',1)
                ->select();
                // print_r($list);exit;
    	$list = get_childs($list,$list[0]['pid']);
    	if($list){
        	$this->result($list,1,'获取权限成功！');
        }else{
        	$this->result('',0,'暂无数据！');
        }
    }


	/**
	 * 一级权限
	 * @return [type] [description]
	 */
	public function authSel()
	{
		$list = Db::table('am_auth_auth')->where('pid',0)->select();
		if($list){
			$this->result($list,1,'获取权限列表成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}

	/**
	 * 二级及多级权限
	 * @return [type] [description]
	 */
	public function authTwo()
	{
		$id = input('post.id');
		$list = Db::table('am_auth_auth')->where('pid',$id)->select();
		if($list){
			$this->result($list,1,'获取权限列表成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}




	/**
	 * 添加权限
	 * @return [type] [description]
	 */
	public function authAdd()
	{
		// 权限名称，权限方法，权限父级id
		$data = input('post.');
		$validate = validate('Auth');
		if($validate->check($data)){	
			$res = Db::table('am_auth_auth')->strict(false)->insert($data);
			if($res){
				$this->result('',1,'添加权限成功');
			}else{
				$this->result('',0,'添加权限失败');
			}
		}else{
			$this->result('',0,$validate->getError());
		}

	}

	/**
	 * 权限修改页面
	 * @return [type] [description]
	 */
	public function saveIndex()
	{
		// 获取权限数据id
		$id = input('post.id');
		// 查询
		$data = Db::table('am_auth_auth')->where('id',$id)->find();
		if($data){
			$this->result($data,1,'获取权限数据成功');
		}else{
			$this->result('',1,'获取权限数据失败');
		}
	}


	/**
	 * 修改权限
	 * @return [type] [description]
	 */
	public function authModify()
	{
		// 权限名称，权限方法，权限父级id
		$data = input('post.');
		$validate = validate('Auth');
		if($validate->check($data)){	
			$res = Db::table('am_auth_auth')->where('id',$data['id'])->strict(false)->update($data);
			if($res !== false){
				$this->result('',1,'修改权限成功');
			}else{
				$this->result('',0,'修改权限失败');
			}
		}else{
			$this->result('',0,$validate->getError());
		}

	}

	/**
	 * 删除权限
	 * @return [type] [description]
	 */
	public function authDel()
	{
		// 获取数据id     
		$id = input('post.id');
		// 查询pid等于该id的数据  总条数大于0怎说明下面又子级权限
		$count = Db::table('am_auth_auth')->where('pid',$id)->count();
		Db::startTrans();
		if($count > 0 ){
			// 删除pid 等于该id的数据
			$res = Db::table('am_auth_auth')->where('id='.$id.' OR pid='.$id)->delete();
		}else{
			$res = Db::table('am_auth_auth')->where('id',$id)->delete();
		}
		if($res){
			Db::commit();
			$this->result('',1,'删除成功');
		}else{
			Db::rollback();
			$this->result('',1,'删除成功');
		}
		
	}
}