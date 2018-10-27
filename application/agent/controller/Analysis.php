<?php 
namespace app\shop\controller;
use app\base\controller\Shop;
use think\Db;

class Analysis extends Shop
{



   
    /**
     * 进程初始化
     */
    public function initialize()
    {
        parent::initialize();
    }

     // SM半合成  SN合成   SN全合成   SN脂类全合成  SN燃气专用   豪华礼包  6

     //  初期授信(期初配给)  物料消耗   物料补充   物料剩余   增加授信  5


    
 
    /*
     *维修厂期初配给
     */
    public function allotment()
    {
        //该维修厂下的数据   cs_ration  修车厂库存表     cs_shop  修车厂表  co_bang_cate  邦保养物料种类
        $pageSize = 8;
        $page = input('post.page')? : 1;
        $count = Db::table('cs_ration')
            ->alias('a')
            ->join('co_bang_cate b','a.materiel = b.id','LEFT')
            ->where('sid','in',$this->sid)
            ->count();
        $rows = ceil($count / $pageSize);
        $list = Db::table('cs_ration')
            ->alias('a')
            ->join('co_bang_cate b','a.materiel = b.id','LEFT')
            ->where('sid','in',$this->sid)
            ->field('b.name,a.ration')
            ->page($page,$pageSize)
            ->select();
        $this->result(['list'=>$list,'rows'=>$rows],1,'');
    }

    /*
     *物料剩余
     */
    public function surplus()
    {
        //该维修厂下的数据   cs_ration  修车厂库存表     cs_shop  修车厂表
        $pageSize = 8;
        $page = input('post.page')? : 1;
        $count = Db::table('cs_ration')
            ->alias('a')
            ->join('co_bang_cate b','a.materiel = b.id','LEFT')
            ->where('sid','in',$this->sid)
            ->count();
        $rows = ceil($count / $pageSize);
        $list = Db::table('cs_ration')
            ->alias('a')
            ->join('co_bang_cate b','a.materiel = b.id','LEFT')
            ->where('sid','in',$this->sid)
            ->field('b.name,a.stock')
            ->page($page,$pageSize)
            ->select();
        $this->result(['list'=>$list,'rows'=>$rows],1,'');
    }

    /*
     *物料补充
     */
    public function embody()
    {
        //该维修厂下的数据   cs_apply_materiel  修车厂申请物料表     cs_shop  修车厂表
        $pageSize = 8;
        $page = input('post.page')? : 1;
        $count = Db::table('cs_apply_materiel')
            ->where('sid','in',$this->sid)
            ->field('apply_sn,detail,create_time')
            ->count();
        $rows = ceil($count / $pageSize);
        $list = Db::table('cs_apply_materiel')
            ->where('sid','in',$this->sid)
            ->field('apply_sn,detail,create_time')
            ->page($page,$pageSize)
            ->select();
       foreach ($list as $key=>$value){
           $list[$key]['detail'] = json_decode($list[$key]['detail'],true);
       }
        $this->result(['list'=>$list,'rows'=>$rows],1,'');
    }

    /*
     *物料消耗
     */
    public function dissipation()
    {
        //该维修厂下的数据   cs_income 邦保养记录表     cs_shop  修车厂表
        $pageSize = 8;
        $page = input('post.page')? : 1;
        $count = Db::table('cs_income')
            ->where('sid','in',$this->sid)
            ->field('odd_number,oil,litre')
            ->count();
        $rows = ceil($count / $pageSize);
        $list = Db::table('cs_income')
            ->where('sid','in',$this->sid)
            ->field('odd_number,oil,litre,create_time')
            ->page($page,$pageSize)
            ->select();
        $this->result(['list'=>$list,'rows'=>$rows],1,'');
    }

    /*
     *增加授信
     */
    public function credit()
    {
        //该维修厂下的数据   cs_increase 提高配给记录表     cs_shop  修车厂表
        $pageSize = 8;
        $page = input('post.page')? : 1;
        $count = Db::table('cs_increase')
            ->where('sid','in',$this->sid)
            ->field('record,create_time')
            ->count();
        $rows = ceil($count / $pageSize);
        $list = Db::table('cs_increase')
            ->where('sid','in',$this->sid)
            ->field('record,create_time')
            ->page($page,$pageSize)
            ->select();
        foreach ($list as $key=>$value){
            $list[$key]['record'] = json_decode($list[$key]['record'],true);
        }
        $this->result(['list'=>$list,'rows'=>$rows],1,'');
    }



    public function bang($number,$sid,$con)
    {
        //初期配给
        $list['rationing'] = Db::table('cs_ration')
                ->alias('a')
                ->join('co_bang_cate b','a.materiel = b.id','LEFT')
                ->where('a.materiel = '.$number)
                ->where('sid','in',$sid)
                ->sum('ration');
        $list['rationing'] = floor($list['rationing']/12);
        //物料剩余
        $list['surplus'] = Db::table('cs_ration')
                ->alias('a')
                ->join('co_bang_cate b','a.materiel = b.id','LEFT')
                ->where('a.materiel = '.$number)
                ->where('sid','in',$sid)
                ->sum('stock');
        $list['surplus'] = floor($list['surplus']/12);
        //物料补充
        $data = Db::table('cs_apply_materiel')
            ->where('sid','in',$sid)
            ->field('detail')
            ->select();
        foreach ($data as $key=>$value){
            $data[$key]['detail'] = json_decode($data[$key]['detail'],true);
        }
        foreach ($data as $key => $value){
            $detail[] = $data[$key]['detail'];
        }
        $num = 0;
        foreach ($detail as $key => $value){
            $arr = $detail[$key];
            foreach ($arr as $key => $value){
                if($arr[$key]['materiel_id'] == $number){
                    $num += $arr[$key]['num'];
                }
            }
        }
        $list['buchong'] = floor($num/12);
        //物料消耗
        $list['dissipation'] = Db::table('cs_income')
                ->where('sid','in',$sid)
                ->where('oil',$con)
                ->sum('litre');
        $list['dissipation'] = floor($list['dissipation']/12);
        //物料增加授信
        $datas = Db::table('cs_increase')
            ->where('sid','in',$sid)
            ->field('record')
            ->select();
        if (count($datas) != 0){
            foreach ($datas as $key=>$value){
                $datas[$key]['record'] = json_decode($datas[$key]['record'],true);
            }
            foreach ($datas as $key => $value){
                $record[] = $datas[$key]['record'];
            }
            $nums = 0;
            foreach ($record as $key => $value){
                $arr = $record[$key];
                foreach ($arr as $key => $value){
                    if($arr[$key]['materiel_id'] == $number){
                        $nums += $arr[$key]['num'];
                    }
                }
            }
            $list['record'] = floor($nums/12);
        }else{
            $list['record'] = 0;
        }
       return $list;
    }
    /*
     *数据总接口
     */
    public function total()
    {
        $list['first'] = $this->bang(2,$this->sid,'SM半合成(邦保养1号)');
        $list['second'] = $this->bang(3,$this->sid,'SN合成(邦保养2号)');
            $list['third'] = $this->bang(4,$this->sid,'SN全合成(邦保养3号)');
        $list['fourth'] = $this->bang(5,$this->sid,'SN脂类全合成(邦保养4号)');
        $list['life'] = $this->bang(7,$this->sid,'“惠生活”豪华大礼包');
        $this->result($list,1,'');
    }
}