<?php 
namespace app\shop\controller;
use app\base\controller\Shop;
use think\Db;

class Qmoney extends Shop
{
    /**
     * 进程初始化
     */
    public function initialize()
    {
        parent::initialize();
    }

    //维修厂的资金数据
    public function money()
    {
        //余额
        $list = Db::table('cs_shop')->field('balance')->where('id',$this->sid)->find();

        //支出
        $list['expenditure'] = Db::table('co_system_fee')->where(['pay_type'=>1,'uid'=>$this->sid])->sum('total_fee');

        //提现
        $list['Withdraw'] = Db::table('cs_apply_cash')->where(['sid'=>$this->sid,'audit_status'=>1])->sum('money');

        //服务收入资金
        $list['service'] = Db::table('cs_income')->where('sid',$this->sid)->sum('total');

        //滤芯补助
        $list['subsidy'] = Db::table('cs_income')->where('sid',$this->sid)->sum('filter');

        //购卡收入资金
        $list['buycard'] = Db::table('u_card')->where(['pay_status'=>1,'sid'=>$this->sid])->sum('card_price');

        //售卡利润
        $list['margin'] = Db::table('u_card')->where(['pay_status'=>1,'sid'=>$this->sid])->sum('card_reward');

        //好评奖励
        $list['premium'] = Db::table('u_comment')->where('sid',$this->sid)->sum('money');

        //总收入
        $list['revenue'] = $list['service'] + $list['subsidy'] +$list['buycard'] + $list['margin'] + $list['premium'];

        $this->result($list,1,'数据返回成功');

    }

}