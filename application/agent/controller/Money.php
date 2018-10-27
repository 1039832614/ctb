<?php 
namespace app\Agent\controller;
use app\base\controller\Agent;
use think\Db;
use Ana\ShopAna;

class Money extends Agent
{
    /**
     * 进程初始化
     * @return [type] [description]
     */
    public function initialize()
    {
//        $this->sid = input('post.sid');
//        $this->ShopAna = new ShopAna;
//        parent::initialize();
    }

    // 资金管理

    /*
     *资金收入
     */
    public function money()
    {
        $sid = Db::table('cs_shop')->where('aid',$this->aid)->column('id');
        //提现总额
        $list['withdraw'] = Db::table('ca_apply_cash')
                        ->where('aid','in',$this->aid)
                        ->where('audit_status',1)
                        ->sum('money');
        //运营商余额
        $list['sur_amount'] = Db::table('ca_apply_cash')
                        ->where('aid','in',$this->aid)
                        ->where('audit_status',1)
                        ->order('audit_time DESC')
                        ->limit(1)
                        ->value('sur_amount');
        //运营商支出总额
        $list['disbursement'] = 0;
        //运营商开发奖励
        $list['premium'] = Db::table('ca_agent')->where('aid',$this->aid)->value('awards');
        //交易分成
        $data = Db::table('u_card')->where('sid','in',$sid)->sum('card_price');    //该运营商下所有的购卡总额
        $profit = Db::table('ca_agent_set')->field('profit')->where('aid',$this->aid)->find();  //百分比利润
        $list['business'] = $data*($profit['profit']/100);
        if ($list){
            $this->result($list,1,'数据返回成功');
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