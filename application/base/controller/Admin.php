<?php
namespace app\base\controller;
use app\base\controller\Base;
use think\Db;
/**
* 
*/
class Admin extends Base
{
	
	/**
     * 初始化
     * @return [type] [description]
     */
    function initialize()
    {   
    	parent::initialize();
        $this->admin_id=$this->ifToken();
        $this->b();
    }


    public function b()
    {
        if(!$this->authAction()){
            $this->result('',0,'暂无权限');
        }
    }

    /**
     * 权限跳转
     * @return [type] [description]
     */
    public function authAction()
    {
        // $role = Db::table('am_role_user')->where('user_id',$this->admin_id)->column('role_id');
        // // 查询多个用户组所拥有的权限并去重(获取用户组所拥有的的权限)
        $list = Db::table('am_auth_user au')
            ->join('am_role_user ru','au.uid = ru.user_id')
            ->join('am_auth_role ar','ru.role_id = ar.rid')
            ->join('am_rule_role rr','ar.rid = rr.role_id')
            ->join('am_auth_auth aa','rr.rule_id = aa.id')
            ->group('rule_id')
            ->where(['user_id'=>$this->admin_id])
            ->column('auth_action');
            // print_r($list);
        //获取当前控制器方法
        $action = request()->action();
        $controller = request()->controller();
       // print_r($controller.'/'.$action);exit;
       
        $suadmin=['Auth/ifuser','Auth/erauth'];
        if(in_array($controller.'/'.$action,$suadmin) || in_array($controller.'/'.$action,$list)){
                $this->$action();
        }else{
             $this->result('',1,'暂无权限！');
        }
    }



    /**
     * 运营商列表  点击区域个数显示内容
     * @return [type] [description]
     */
    public function region($aid)
    {
        // 通过区县id获得市级id
        $county=Db::table('ca_area')->where('aid',$aid)->select('area');
        // 把区县id转换成字符串
        $county = array_str($county,'area');
        // 市级id转换为字符串
        $city=$this->areaList($county);
        // 省级id转换为字符串
        $province=$this->areaList($city);
        // 查询所有省市县的数据
        $list=Db::table('co_china_data')->whereIn('id',$province.','.$city.','.$county)->field('name,pid,id')->select();
        if($list){
            // 把数据换成树状结构
            return get_child($list,$list[0]['pid']);
            

        }else{  
            return '暂未设置地区';
        }

    }

    /**
     * 显示本次配给的地区
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function showRegion($id)
    {
        $county = Db::table('ca_increase')->where('id',$id)->value('area');
        // 市级id转换为字符串
        $city=$this->areaList($county);
        // 省级id转换为字符串
        $province=$this->areaList($city);
        // 查询所有省市县的数据
        $list=Db::table('co_china_data')->whereIn('id',$province.','.$city.','.$county)->field('name,id,pid')->select();
        if($list){
            // 把数据换成树状结构
            return get_child($list,$list[0]['pid']);
            

        }else{  
            return '暂未设置地区';
        }
    }


    /**
     * 把运营商的市县id转换为字符串
     * @return 城市地区id
     */
    private function areaList($city)
    {
        $a = Db::table('co_china_data')->whereIn('id',$city)->select();
        return $a = array_str($a,'pid');
    }



    /**
     * 点击驳回理由显示内容
     * @param  [type] $id     [description]订单id或者修车厂id
     * @param  [type] $table  [description]数据库表
     * @param  [type] $reason [description]输入驳回理由的字段
     * @return [type]         [description]
     */
    public function reason($id,$table,$reason)
    {
        return Db::table($table)->where('id',$id)->value($reason);
    }

}