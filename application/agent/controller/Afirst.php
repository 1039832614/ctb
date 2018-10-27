<?php 
namespace app\Agent\controller;
use app\base\controller\Agent;
use think\Db;
use Ana\ShopAna;

class Afirst extends Agent
{
    /**
     * 进程初始化
     * @return [type] [description]
     */
    public function initialize()
    {
        parent::initialize();
    }

    //判断该运营商
    public function first()
    {
        $count = Db::table('ca_ration')->where('aid',$this->aid)->count();   // 判断 配给表中是否有第一次配给数据
//        $count = Db::table('ca_increase')->where('aid',$this->aid)->where('audit_status',1)->count();   // 判断 运营商是否已经设置运营商供货地址(已审核通过)
        if ($count > 0){
            $area = Db::table('ca_increase')->where('aid',$this->aid)->value('area');  // 获取 运营商  设置的地区 ID
            $this->cityList($area);
        }else{     // 未设置供应地区
           return  $this->province();        // 返回所有的 省
        }
    }

    //所有的 市级
    public function town()
    {
        $pid = input('post.pid');  //省ID
        return $this->city($pid);
    }
    // 所有的  区,县
    public function district()
    {
        $cid = input('post.cid');   // 市级ID
        return $this->county($cid);
    }


    //根据 运营商设置的 运营地区 查找 该设置地区的 省,市,区县
    public function cityList($area)
    {   
        // 查找 市 的ID
        $county = Db::table('co_china_data')
                    ->where('id','in',$area)
                    ->value('pid');  
         // 查找 省 ID
        $city = Db::table('co_china_data')
                ->where('id',$county)
                ->field('id,name,code,pid')
                ->find(); 
        //  省  名称 
        $province = Db::table('co_china_data')
                    ->where('id',$city['pid'])
                    ->field('id,name,code,pid')
                    ->find();  
        //  返回所有已选择地区
        $selCity = Db::table('ca_area')->select();
        // 未选择地区
        $noCity = $this->city($county);
        $data = ['selCounty'=>$selCity,'county'=>$noCity];
        $this->result(['province'=>$province,'city'=>$city,'county'=>$data],1,'返回成功');   //  返回  省 市  区县

    }



}