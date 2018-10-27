<?php 
namespace app\Agent\controller;
use app\base\controller\Agent;
use think\Db;
use Ana\ShopAna;

class Rank extends Agent
{
    /**
     * 进程初始化
     * @return [type] [description]
     */
    public function initialize()
    {
//        $this->sid = input('post.sid');
//        $this->ShopAna = new ShopAna;
        parent::initialize();
    }

    /*
     *该运营商下所有维修厂名
     */
    public function shopName()
    {
        $sid = Db::table('cs_shop')->where('aid',$this->aid)->where('audit_status',2)->column('id');
        $name = Db::table('cs_shop')
                ->field('id,company as usname')
                ->where('id','in',$sid)
                ->select();
        if ($name){
            $this->result($name,1,'维修厂名返回成功');
        }else{
            $this->result('',0,'暂无数据');
        }
    }

    /*
   *该运营商下的所有的维修厂的业务排名
   */
    //用户购卡表 u_card   cs_shop  维修厂表
    public function rank()
    {
//        $sid = Db::table('cs_shop')->where('aid',$this->aid)->column('id');
        $sid = input('post.id');    //  维修厂的ID
        // print_r($sid);exit;
        $rank = Db::table('u_card')
            ->alias('a')
            ->join('cs_shop b','a.sid = b.id','LEFT')
            ->where([
                ['a.sid','in',$sid],
                ['a.pay_status','=',1]
            ])
            ->field("DATE_FORMAT(sale_time,'%c') as month,count(a.id) as number")  //月份  售卡数量
            ->where("DATE_FORMAT(sale_time,'%Y') = YEAR(CurDate())")
            ->group('month')
            ->order('number DESC')
            ->select();
         //    echo $sid.'这是一个维修厂id';
         // print_r($rank);exit;
        if (count($rank) != 0){
            $arr = $this->arr($rank);
        }else{
            $arr = [
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0
            ];
        }
        if ($arr){
            $this->result($arr,1,'数据返回成功');
        }else{
            $this->result('',0,'暂无数据');
        }
    }

       public function arr($rank){
        $res = [];
        foreach ($rank as $key => $val) {
            $res[$val['month']] = $val['number'];
        }
        for ($i=1;$i<13;$i++){
            if (!array_key_exists($i,$res)){
                $res[$i] = 0;
            }
        }
        $arr = array();
        ksort($res);
        foreach ($res as $k=>$v){
            $arr[] = $v;
        }
        return $arr;
    }


    /*
     *售卡前三名
     */
    //  u_card  用户购卡表    cs_shop  维修厂表
    public function kind()
    {
        $sid = Db::table('cs_shop')->where('aid',$this->aid)->where('audit_status',2)->column('id');
        $list = Db::table('u_card')
            ->alias('a')
            ->join('cs_shop b','a.sid = b.id','LEFT')
            ->field("count(a.sid) as number,b.company as usname")            //  售卡数量
            ->where('sid','in',$sid)
            ->where('a.pay_status',1)
            ->limit(3)
            ->group('sid')
            ->order('number DESC')
            ->select();
        if ($list){
            $this->result($list,1,'数据返回成功');
        }else{
            $this->result('',0,'暂无数据');
        }
    }

    /*
     *业务排名详情
     */
    //  cs_shop  维修厂表     u_card  用户购卡表    cs_income   邦保养记录表   u_comment  用户评价服务表
    public function rankDetail()
    {
        $page = input('post.page')? : 1;//获取当前页数
        $pageSize = 10;//每页几条数据数据
        $sid = Db::table('cs_shop')->where('aid',$this->aid)->where('audit_status',2)->column('id');
        $data['detail'] = array();
        $data['ndetail'] = array();
        foreach ($sid as $key=>$value){
            $counts = Db::table('u_card')->field('id')->where('sid',$sid[$key])->count();
            if ($counts != 0){
                $data['detail'] = Db::table('cs_shop')
                    ->alias('a')
                    ->join('u_card b','a.id = b.sid','LEFT')
                    ->where([
                        ['a.id','in',$sid],
                        ['b.pay_status','=',1]
                    ])
                    ->field('a.id,a.company as usname,count(b.id) as number,sum(b.card_price) as price,a.service_num')
                    ->group('a.id')
                    ->order('number DESC')
                    ->select();
                foreach ($data['detail'] as $key=>$value){
                    //   好评次数
                    $data['detail'][$key]['comment_num'] = Db::table('u_comment')
                                                        ->where('sid',$data['detail'][$key]['id'])
                                                        ->where('tn_star',5)
                                                        ->where('shop_star',5)
                                                        ->count('id');
                    //  消耗物料
                    $data['detail'][$key]['litres'] = Db::table('cs_income')
                                                        ->where('sid',$data['detail'][$key]['id'])
                                                        ->sum('litre');
                    // 复购次数
                    $data['detail'][$key]['repetition_num'] = Db::table('u_card')
                                                        ->where('sid',$data['detail'][$key]['id'])
                                                        ->where('pay_status',1)
                                                        ->value("(count(uid) - count(distinct uid)) as number");
                }
            }else{
                $name = Db::table('cs_shop')->field('company')->where('id',$sid[$key])->find();
                $data['ndetail'][] = [
                    'id'=>$sid[$key],
                    'usname'=>$name['company'],
                    'number'=>0,
                    'price'=>0,
                    'service_num'=>0,
                    'comment_num'=>0,
                    'litres'=>0,
                    'repetition_num'=>0,
                ];
            }
        }
        $list = array_merge($data['detail'],$data['ndetail']);
        $i = 1;
        foreach ($list as $key=>$value){
            $list[$key]['mid'] = $i;
            $i++;
        }
        $rows = ceil(count($list)/$pageSize);
        $lists = array_slice($list,($page-1)*$pageSize,$pageSize);
        if ($lists){
            $this->result(['list'=>$lists,'rows'=>$rows],1,'数据返回成功');
        }else{
            $this->result('',0,'暂无数据');
        }
    }


    /**********************资金管理******************************/
    /*
     *资金收入
     */
    public function money()
    {
        $sid = Db::table('cs_shop')->where('aid',$this->aid)->column('id');
        //提现总额
        $withdraw['withdraw'] = Db::table('ca_apply_cash')
            ->where('aid','in',$this->aid)
            ->where('audit_status',1)
            ->sum('money');
        $list[] = array('name'=>'提现','value'=>(int)$withdraw['withdraw']);
        //运营商余额
        $sur_amount['sur_amount'] = Db::table('ca_agent')
            ->where('aid',$this->aid)
            ->value('balance');
        $list[] = array('name'=>'余额','value'=>(int)$sur_amount['sur_amount']);
        //运营商支出总额
        $disbursement['disbursement'] = 0;
        $list[] = array('name'=>'支出','value'=>(int)$disbursement['disbursement']);
        //运营商开发奖励
        $order['premium'] = Db::table('ca_agent')->where('aid',$this->aid)->value('awards');
        //交易分成
//        $data = Db::table('u_card')->where('sid','in',$sid)->sum('card_price');    //该运营商下所有的购卡总额
//        $profit = Db::table('ca_agent_set')->field('profit')->where('aid',$this->aid)->find();  //百分比利润
//        $order['business'] = $data*($profit['profit']/100);
        $order['business'] = Db::table('ca_income')->where('aid',$this->aid)->sum('amount');
        $order['income'] = $order['premium'] + $order['business'];
        $shou[] = array('name'=>'收入','value'=>$order['income']);;
        if ($list){
            $this->result(['list'=>$list,'data'=>$order,'revenue'=>$shou],1,'数据返回成功');
        }else{
            $this->result('',0,'暂无数据');
        }


    }

    /*
     *提现详情
     */
    public function withdrawDetail()
    {
        $pageSize = 8;
        $page = input('post.page')? : 1;
        $count = Db::table('ca_apply_cash')
            ->where('aid','in',$this->aid)
            ->where('audit_status',1)
            ->count();
        $rows = ceil($count / $pageSize);
        $list = Db::table('ca_apply_cash')
            ->where('aid','in',$this->aid)
            ->where('audit_status',1)
            ->field('money,create_time')
            ->page($page,$pageSize)
            ->select();
        if ($count > 0 ){
            $this->result(['list'=>$list,'rows'=>$rows],1,'提现详情数据返回成功');
        }else{
            $this->result('',0,'暂无数据');
        }

    }
}