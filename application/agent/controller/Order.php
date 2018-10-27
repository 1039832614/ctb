<?php
namespace app\agent\controller;
use app\base\controller\Agent;
use think\Db;
/**
* 预约订单操作
*/
class Order extends Agent
{
	/**
	 * 预约订单
	 * @return [type] [description]
	 */
    public function initialize()
    {
        parent::initialize();
    }



    public function signingorder($page,$status,$list_1)  //签约的列表
    {
        $pageSize = 8;
       $count = Db::table('st_commodity')
           ->alias('a')
           ->join('cs_shop b','a.sid=b.id','LEFT')
           ->field('a.id,a.name,a.mold,a.create_time,b.company')
           ->where('a.sid','in',$list_1)
           ->where('a.status',$status)
           ->where('a.ifsigning',1)
           ->count();
       $rows = ceil($count / $pageSize);
       $list = Db::table('st_commodity')
           ->alias('a')
           ->join('cs_shop b','a.sid=b.id','LEFT')
           ->where('a.sid','in',$list_1)
           ->where('a.status',$status)
           ->where('a.ifsigning',1)
           ->field('a.id,a.name,a.mold,a.create_time,a.status,b.company')
           ->page($page,$pageSize)
           ->select();
       if ($count > 0){
           $this->result(['list'=>$list,'rows'=>$rows],1,'订单列表返回成功');
       }else{
           $this->result('',0,'暂无数据');
       }
    }
    /**
     * 产品未审核列表
     */
    public function swaitList()
    {
        $page = input('post.page')? : 1;
        $list_1 = Db::table('cs_shop')->where('aid',$this->aid)->column('id');  //已签约维修厂中的该运营商下的
        return $this->signingorder($page,0,$list_1);
    }
    /**
     * 产品通过列表
     */
    public function spassList()
    {
        $page = input('post.page')? : 1;
        $list_1 = Db::table('cs_shop')->where('aid',$this->aid)->column('id');  //已签约维修厂中的该运营商下的
        return $this->signingorder($page,1,$list_1);
    }
    /**
     * 产品驳回列表
     */
    public function srejectList()
    {
        $page = input('post.page')? : 1;
        $list_1 = Db::table('cs_shop')->where('aid',$this->aid)->column('id');  //已签约维修厂中的该运营商下的
        return $this->signingorder($page,2,$list_1);
    }








    public function signed($page,$status,$list_2)  //未签约列表
    {
        $pageSize = 8;
        $count = Db::table('st_commodity')
            ->alias('a')
            ->join('st_shop b','a.sid=b.id','LEFT')
            ->field('a.name,a.mold,a.create_time,b.company')
            ->where('a.sid','in',$list_2)
            ->where('a.status',$status)
            ->where('a.ifsigning',0)
            ->count();
        $rows = ceil($count / $pageSize);
        $list = Db::table('st_commodity')
            ->alias('a')
            ->join('st_shop b','a.sid=b.id','LEFT')
            ->field('a.id,a.name,a.mold,a.create_time,a.status,b.company')
            ->where('a.sid','in',$list_2)
            ->where('a.status',$status)
            ->where('a.ifsigning',0)
            ->page($page,$pageSize)
            ->select();
        if ($count > 0){
            $this->result(['list'=>$list,'rows'=>$rows],1,'订单列表返回成功');
        }else{
            $this->result('',0,'暂无数据');
        }
    }

    /**
     * 产品未审核列表
     */
    public function waitList()
    {
        $page = input('post.page')? : 1;
        $list_2 = Db::table('st_shop')->where('aid',$this->aid)->column('id');  //未签约维修厂中的该运行商下的
        return $this->signed($page,0,$list_2);
    }
    /**
     * 产品通过列表
     */
    public function passList()
    {
        $page = input('post.page')? : 1;
        $list_2 = Db::table('st_shop')->where('aid',$this->aid)->column('id');  //未签约维修厂中的该运行商下的
        return $this->signed($page,1,$list_2);
    }
    /**
     * 产品驳回列表
     */
    public function rejectList()
    {
        $page = input('post.page')? : 1;
        $list_2 = Db::table('st_shop')->where('aid',$this->aid)->column('id');  //未签约维修厂中的该运行商下的
        return $this->signed($page,2,$list_2);
    }


    /*
     *产品详情
     */
    public function detail()
    {
        $id = input('post.id');   //产品ID
        $detail['detail'] = Db::table('st_commodity')->field('pic,detail')->where('id',$id)->find();
        $detail['spec'] = Db::table('st_commodity_detail')
            ->field('standard,standard_detail,sell_number,stock_number,market_price,activity_price')
            ->where('cid',$id)
            ->select();
        $this->result($detail,1,'产品详情返回成功');
    }

    /*
     *审核通过
     */
    public function pass()
    {
        $id = input('post.id');  //产品ID
        $res = Db::table('st_commodity')->where('id',$id)->setField('status',1);
        if ($res){
            $this->result('',1,'审核通过成功');
        }else{
            $this->result('',0,'审核通过失败');
        }
    }

    /*
     *审核不通过(驳回)
     */
    public function rejection()
    {
        $id = input('post.id');  //产品ID
        $reason = input('post.reason');  //驳回原因
        $res = Db::table('st_commodity')->where('id',$id)->setField(['status'=>2,'reason'=>$reason]);
        if ($res){
            $this->result('',1,'产品驳回成功');
        }else{
            $this->result('',0,'产品驳回失败');
        }
    }

    //  查看驳回理由
    public function rejectRson()
    {
        $id = input('post.id');
        $list = Db::table('st_commodity')
            ->where('id',$id)
            ->field('reason')
            ->find();
        if ($list){
            $this->result($list,1,'数据返回成功');
        }else{
            $this->result('',0,'暂无数据');
        }
    }

}