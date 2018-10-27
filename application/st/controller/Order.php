<?php 
namespace app\st\controller;
use app\base\controller\St;
use think\Db;

/**
 * 预约订单管理
 */
class Order extends St
{
    /*
     * 进程初始化
     */
    public function initialize()
    {
        parent::initialize();
    }

    /*
     * 搜索
     */
    public function firshSearch($key,$value,$page,$ifsigning,$status)
    {
        $pageSize = 8;
        $count = Db::table('st_order')
            ->alias('a')
            ->join('st_commodity c', 'a.cid=c.id','LEFT')
            ->join('st_commodity_detail b','a.specid=b.id','LEFT')
            ->where([
                ['a.sid','=',$this->sid],
                ['a.ifsigning','=',$ifsigning],
                ['a.status','=',$status],
                ['a.'.$key,'like','%'.$value.'%'],
            ])
//            ->whereBetweenTime('a.create_time',strtotime($start_time),strtotime($endtime))
            ->count();
        $rows = ceil($count / $pageSize);
        $list = Db::table('st_order')
            ->alias('a')
            ->join('st_commodity c', 'a.cid=c.id','LEFT')
            ->join('st_commodity_detail b','a.specid=b.id','LEFT')
            ->where([
                ['a.sid','=',$this->sid],
                ['a.ifsigning','=',$ifsigning],
                ['a.status','=',$status],
                ['a.'.$key,'like','%'.$value.'%'],
            ])
//            ->whereBetweenTime('a.create_time',strtotime($start_time),strtotime($endtime))
            ->field('a.id as aid,a.cid,a.name,a.phone,a.carclass,a.cartype,c.name as wname,a.create_time,a.status,a.ordernum,a.number,b.standard,b.standard_detail,a.y_time')
            ->order('a.create_time DESC')
            ->page($page, $pageSize)
            ->select();
        foreach ($list as $key=>$value){
            $list[$key]['create_time'] = date('Y-m-d H:i:s',$list[$key]['create_time']);
        }
        if ($count > 0) {
            $this->result(['list' => $list, 'rows' => $rows], 1, '获取数据成功');
        } else {
            $this->result('', 0, '暂无数据');
        }

    }

    /*
     *搜索  预约人姓名
     */
    public function reName()
    {
        $key = input('post.key');  //搜索类型
        $value = input('post.value');  //搜索内容
        $ifsigning = input('post.ifsigning'); //是否签约 0  未签约   1  已签约
        $status = input('post.status'); //状态
        $page = input('post.page')? : 1;
        return $this->firshSearch($key,$value,$page,$ifsigning,$status);
    }

    /*
    *搜索  预约人手机号
    */
    public function rePhone()
    {
        $key = input('post.key');  //搜索类型
        $value = input('post.value');  //搜索内容
        $ifsigning = input('post.ifsigning'); //是否签约 0  未签约   1  已签约
        $page = input('post.page')? : 1;
        $status = input('post.status'); //状态
        return $this->firshSearch($key,$value,$page,$ifsigning,$status);
    }



    public function secondSearch($key,$value,$page,$ifsigning,$status)
    {
        $pageSize = 8;
        $count = Db::table('st_order')
            ->alias('a')
            ->join('st_commodity c', 'a.cid=c.id','LEFT')
            ->join('st_commodity_detail b','a.specid=b.id','LEFT')
            ->where([
                ['a.sid','=',$this->sid],
                ['a.ifsigning','=',$ifsigning],
                ['a.status','=',$status],
                ['c.'.$key,'like','%'.$value.'%'],
            ])
            ->count();
        $rows = ceil($count / $pageSize);
        $list = Db::table('st_order')
            ->alias('a')
            ->join('st_commodity c', 'a.cid=c.id','LEFT')
            ->join('st_commodity_detail b','a.specid=b.id','LEFT')
            ->where([
                ['a.sid','=',$this->sid],
                ['a.ifsigning','=',$ifsigning],
                ['a.status','=',$status],
                ['c.'.$key,'like','%'.$value.'%'],
            ])
            ->field('a.id as aid,a.cid,a.name,a.phone,a.carclass,a.cartype,c.name as wname,a.create_time,a.status,a.ordernum,a.number,b.standard,b.standard_detail,a.y_time')
            ->order('a.create_time DESC')
            ->page($page, $pageSize)
            ->select();
        foreach ($list as $key=>$value){
            $list[$key]['create_time'] = date('Y-m-d H:i:s',$list[$key]['create_time']);
        }
        if ($count > 0) {
            $this->result(['list' => $list, 'rows' => $rows], 1, '获取数据成功');
        } else {
            $this->result('', 0, '暂无数据');
        }

    }

    /*
   *搜索  预约产品名称
   */
    public function rePname()
    {
        $key = input('post.key');  //搜索类型
        $value = input('post.value');  //搜索内容
        $ifsigning = input('post.ifsigning'); //是否签约 0  未签约   1  已签约
        $status = input('post.status');  //搜索状态
        $page = input('post.page')? : 1;
        return $this->secondSearch($key,$value,$page,$ifsigning,$status);
    }



    /*
     *搜索预约时间段
     *
     */
    public function thirdSearch($key,$arr,$page,$ifsigning,$status)
    {
        $pageSize = 8;
        $count = Db::table('st_order')
            ->alias('a')
            ->join('st_commodity c', 'a.cid=c.id','LEFT')
            ->join('st_commodity_detail b','a.specid=b.id','LEFT')
            ->where('a.sid',$this->sid)
            ->where('a.ifsigning',$ifsigning)
            ->where('a.status',$status)
            ->where('a.'.$key,'between',[$arr['sarttime'],$arr['endtime']])
            ->count();
        $rows = ceil($count / $pageSize);
        $list = Db::table('st_order')
            ->alias('a')
            ->join('st_commodity c', 'a.cid=c.id','LEFT')
            ->join('st_commodity_detail b','a.specid=b.id','LEFT')
            ->where('a.sid',$this->sid)
            ->where('a.ifsigning',$ifsigning)
            ->where('a.status',$status)
            ->where('a.'.$key,'between',[$arr['sarttime'],$arr['endtime']])
            ->field('a.id as aid,a.cid,a.name,a.phone,a.carclass,a.cartype,c.name as wname,a.create_time,a.status,a.ordernum,a.number,b.standard,b.standard_detail,a.y_time')
            ->order('a.create_time DESC')
            ->page($page, $pageSize)
            ->select();
        foreach ($list as $key=>$value){
            $list[$key]['create_time'] = date('Y-m-d H:i:s',$list[$key]['create_time']);
        }
        if ($count > 0) {
            $this->result(['list' => $list, 'rows' => $rows], 1, '获取数据成功');
        } else {
            $this->result('', 0, '暂无数据');
        }

    }
    /*
   *搜索  预约时间段
   */
    public function reTime()
    {
        $key = input('post.key');  //搜索类型
        $starttime = input('post.starttime');  //搜索开始时间
        $endtime = input('post.endtime');  //搜索结束时间
        $status = input('post.status');  //搜索状态
        if ($status != 0){
            $arr = [
                'sarttime'=>strtotime($starttime),
                'endtime'=>strtotime($endtime)
            ];
        }else{
            $arr = [
                'sarttime'=>$starttime,
                'endtime'=>$endtime
            ];
        }
        $ifsigning = input('post.ifsigning'); //是否签约 0  未签约   1  已签约
        $page = input('post.page')? : 1;
        return $this->thirdSearch($key,$arr,$page,$ifsigning,$status);
    }


    /*
     *预约订单列表
     */
    public function orderList($page,$status,$sid,$ifsigning)
    {
        $pageSize = 8;
        $count = Db::table('st_order')
            ->alias('a')
            ->join('st_commodity c', 'a.cid=c.id', 'LEFT')
            ->join('st_commodity_detail b','a.specid=b.id','LEFT')
            ->where(['a.sid'=>$sid,'a.status'=>$status,'a.ifsigning'=>$ifsigning])
            ->count();
        $rows = ceil($count / $pageSize);
        $list = Db::table('st_order')
            ->alias('a')
            ->join('st_commodity c', 'a.cid=c.id' , 'LEFT')
            ->join('st_commodity_detail b','a.specid=b.id','LEFT')
            ->where(['a.sid'=>$sid,'a.status'=>$status,'a.ifsigning'=>$ifsigning])
            ->field('a.id as aid,a.cid,a.name,a.phone,a.carclass,a.cartype,c.name as wname,a.create_time,a.status,a.ordernum,a.number,b.standard,b.standard_detail,a.y_time')
            ->order('a.create_time DESC')
            ->page($page, $pageSize)
            ->select();
        foreach ($list as $key=>$value){
            $list[$key]['create_time'] = date('Y-m-d H:i:s',$list[$key]['create_time']);
        }
        if ($count > 0) {
            $this->result(['list' => $list, 'rows' => $rows], 1, '获取数据成功');
        } else {
            $this->result('', 0, '暂无数据');
        }
    }

    /*
     *进行中
     */
    public function underway()
    {
        $status = 0;
        $ifsigning = input('post.ifsigning'); //是否签约 0  未签约   1  已签约
        $page = input('post.page')? : 1;
        return $this->orderList($page,$status,$this->sid,$ifsigning);
    }

    /*
     *已完成列表(未评论/已评论)
     */
    public function finishList()
    {
        $status = [1,3];
        $ifsigning = input('post.ifsigning'); //是否签约 0  未签约   1  已签约
        $page = input('post.page')? : 1;
        return $this->orderList($page,$status,$this->sid,$ifsigning);
    }

    /*
     *预约超时订单列表
     */
    public function timeoutList()
    {
        $status = 2;
        $ifsigning = input('post.ifsigning'); //是否签约 0  未签约   1  已签约
        $page = input('post.page')? : 1;
        return $this->orderList($page,$status,$this->sid,$ifsigning);
    }



    /*
     *完成操作
     */
    public function finish()
    {
        $id = input('post.id');   //订单ID
        $info = Db::table('st_order')->field('specid,number')->where('id', $id)->find();
        $ree = Db::table('st_commodity_detail')->where('id', $info['specid'])->setInc('sell_number', $info['number']);  //该产品售出数量加 相对应预约数量
        $ret = Db::table('st_commodity_detail')->where('id', $info['specid'])->setDec('stock_number', $info['number']);  //该产品库存数量减 相对应预约数量
        $res = Db::table('st_order')
            ->where('id', $id)
            ->where('sid',$this->sid)
            ->setField(['status' => 1, 'handing_time' => time()]);
        if ($res && $ree && $ret) {
            $this->result('', 1, '修改订单完成状态成功');
        } else {
            $this->result('', 0, '修改订单完成状态失败');
        }

    }

    /*
     *预约超时操作
     */
    public function timeout()
    {
        $id = input('post.id');   //订单ID
        $res = Db::table('st_order')
            ->where('id',$id)
            ->where('sid',$this->sid)
            ->setField(['status' => 2, 'handing_time' => time()]);
        if ($res) {
            $this->result('', 1, '修改订单超时状态成功');
        } else {
            $this->result('', 0, '修改订单超时状态失败');
        }

    }

    /*
     *查看产品的详情
     */
    public function detail()
    {
        $id = input('post.cid');  //产品的ID
        $list['subject'] =  Db::table('st_commodity')
            ->field('pnum,pic,detail,mold')
            ->where('id',$id)
            ->find();
        $list['detail'] = Db::table('st_commodity_detail')
            ->where('cid',$id)
            ->select();
        $this->result($list,1,'产品详情返回成功');
    }
}