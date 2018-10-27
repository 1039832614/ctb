<?php 
namespace app\shop\controller;
use app\base\controller\Shop;
use think\Db;
use Msg\Sms;
/**
* 资金管理
*/
class Money extends Shop
{
	
	/**
	 * 进程初始化
	 */
	public function initialize()
	{
		parent::initialize();
	}

	/**
	 * 收入列表
	 */
	public function incomeList()
	{
		$page = input('post.page') ? : 1;
		// 获取每页条数
		$pageSize = 1;
		// 获取分页总条数
		$count = Db::table('cs_income')->where('sid',$this->sid)->count();
		$rows = ceil($count / $pageSize);
		// 获取数据
		$list = Db::table('cs_income')
				->alias('i')
				->join(['u_card' => 'c'],'i.cid = c.id')
				->field('i.odd_number,i.filter+i.hour_charge as total,i.grow_up,card_reward,i.create_time,c.plate,c.cate_name')
				->where('i.sid',$this->sid)
				->order('i.id desc')
				->page($page, $pageSize)
				->select();
		// 返回给前端
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}

    /**************************************************/
    // 2018.08.28 10:36 张乐召   添加   收入明细(服务总金额,好评收入,购卡收入,开卡奖励,收入汇总金额)
    /**
     *
     */
    public function total()
    {
        //服务总金额
        $list['service'] = Db::table('cs_income')
            ->where('sid',$this->sid)
            ->sum('total');
        //好评收入
        $list['praise'] = Db::table('u_comment')
            ->where('sid',$this->sid)
            ->where('tn_star',5)
            ->where('shop_star',5)
            ->sum('money');
        //购卡收入
        $list['card'] = Db::table('u_card')
            ->where('sid',$this->sid)
            ->where('pay_status',1)
            ->sum('card_price');
//        //开卡奖励
//        $list['premium'] = Db::table('u_card')
//            ->where('sid',$this->sid)
//            ->where('pay_status',1)
//            ->sum('card_reward');
        //收入汇总金额
//        $list['amount'] = $list['service'] + $list['praise'] + $list['card'] + $list['premium'];
        $list['amount'] = $list['service'] + $list['praise'] + $list['card'];

        if ($list){
            $this->result($list,1,'数据返回成功');
        }else{
            $this->result('',0,'暂无数据');
        }
    }
    /**************************************************/

    /*
     * 2018.09.01 14:04  张乐召   维修厂收入明细
     */
    /**
     * 2018-10-13 cjx 
     */
    // 服务费
    public function serviceFee()
    {
        $page = input('post.page') ? : 1;
        // 获取每页条数
        $pageSize = 8;
        $count = Db::table('cs_income')
            ->alias('a')
            ->join('u_user b','a.uid = b.id','LEFT')
            ->where('sid',$this->sid)
            ->count();
        $rows = ceil($count / $pageSize);
        $list = Db::table('cs_income')
            ->alias('a')
            ->join('u_user b','a.uid = b.id','LEFT')
            ->join('u_card uc','a.cid = uc.id')
            ->join('co_car_cate cc','uc.car_cate_id = cc.id')
            ->field('a.odd_number,a.uid,b.name,a.filter,uc.plate,a.hour_charge,a.create_time,cc.type')
            ->where(['a.sid'=>$this->sid])
            ->order('a.id desc')
            ->page($page,$pageSize)
            ->select();
        // print_r($list);exit;
        foreach ($list as $key=>$value){
        	$list[$key]['card'] = $value['plate'];
        	$list[$key]['car_cate'] = $value['type'];
        }
        if ($count > 0){
            $this->result(['list'=>$list,'rows'=>$rows],1,'数据返回成功');
        }else{
            $this->result('',0,'暂无数据');
        }
    }

     // 好评收入
    public function praiseFee()
    {
        $page = input('post.page') ? : 1;
        // 获取每页条数
        $pageSize = 8;
        $count = Db::table('u_comment')
            ->alias('a')
            ->join('u_user b','a.uid = b.id','LEFT')
            ->join('cs_income ci','a.bid = ci.id','LEFT')
            ->join('u_card uc','ci.cid = uc.id','LEFT')
            ->join('co_car_cate cc','uc.car_cate_id = cc.id','LEFT')
            ->where('a.tn_star',5)
            ->where('a.shop_star',5)
            ->where('a.sid',$this->sid)
            ->count();
        $rows = ceil($count / $pageSize);
        $list = Db::table('u_comment')
            ->alias('a')
            ->join('u_user b','a.uid = b.id','LEFT')
            ->join('cs_income ci','a.bid = ci.id','LEFT')
            ->join('u_card uc','ci.cid = uc.id','LEFT')
            ->join('co_car_cate cc','uc.car_cate_id = cc.id','LEFT')
            ->field('a.money,a.create_time,a.uid,b.name,uc.plate,cc.type')
            ->where('a.sid',$this->sid)
            ->where('a.tn_star',5)
            ->where('a.shop_star',5)
            ->order('a.id desc')
            ->page($page,$pageSize)
            ->select();
        foreach ($list as $key=>$value){
            $list[$key]['card'] = $value['plate'];
            $list[$key]['car_cate'] = $value['type'];
        }
        if ($count > 0){
            $this->result(['list'=>$list,'rows'=>$rows],1,'数据返回成功');
        }else{
            $this->result('',0,'暂无数据');
        }
    }

    //  售卡奖励
    public function sellCard()
    {
        $page = input('post.page') ? : 1;
        // 获取每页条数
        $pageSize = 8;
        $count = Db::table('u_card')
            ->alias('a')
            ->join('u_user b','a.uid = b.id','LEFT')
            ->where('a.sid',$this->sid)
            ->count();
        $rows = ceil($count / $pageSize);
        $list = Db::table('u_card')
            ->alias('a')
            ->join('u_user b','a.uid = b.id','LEFT')
            ->field('a.card_number,b.name,a.plate,a.card_reward,a.sale_time')
            ->where('a.sid',$this->sid)
            ->order('a.id desc')
            ->page($page,$pageSize)
            ->select();
        if ($count > 0){
            $this->result(['list'=>$list,'rows'=>$rows],1,'数据返回成功');
        }else{
            $this->result('',0,'暂无数据');
        }
    }
    /***************************************************/



    /**
	 * 提现申请信息
	 */
	public function draw()
	{
		$info = Db::table('cs_shop')
				->alias('sp')
				->join(['cs_shop_set'=>'st'],'st.sid = sp.id')
				->join(['co_bank_code'=>'b'],'st.bank = b.code')
				->field('phone,balance,b.name as bank,account,account_name')
				->where('sp.id',$this->sid)
				->find();
		if($info){
			$this->result($info,1,'获取数据成功');
		}else{
			$this->result('',0,'获取数据失败');
		}
	}

	/**
	 * 提现申请操作
	 */
	public function handle()
	{
		$data = input('post.');
		// 进行短信验证码验证
		$check = $this->sms->compare($data['mobile'],$data['code']);
		// 获取账户信息
		$info = Db::table('cs_shop_set')->field('bank,account,account_name')->where('sid',$this->sid)->find();
		if($check !== false){
			// 开启事务
			Db::startTrans();
            $time = Db::table('cs_apply_cash')
                ->where([      
                    'sid' => $this->sid
                ])
                ->order('id DESC')
                ->limit(1)
                ->value("UNIX_TIMESTAMP(create_time)");
            if (time() < $time + 518400){
                $this->result('',0,'两次提现时间间隔最少7天');
            }
			// 检测提现金额是否超限
			$max = Db::table('cs_shop')->where('id',$this->sid)->value('balance');
			// 构建提现数据
			$arr = [
				'odd_number' => build_order_sn(),
				'sid'		=> $this->sid,
				'bank_code'	=>	$info['bank'],
				'account_name'	=>	$info['account_name'],
				'account'	=>	$info['account'],
				'money'	=>	$data['money'],
				'sur_amount' => $max - $data['money']
			];
			if($max < $data['money']){
				$this->result('',0,'超过最大提现额度');
			}
			// 实例化验证
			$validate = validate('Draw');
			// 进行数据验证
			if($validate->check($arr)){
				// 写入提现记录
				$draw_log = Db::table('cs_apply_cash')->strict(false)->insert($arr);
				// 账户余额减少
				$account_dec = Db::table('cs_shop')->where('id',$this->sid)->setDec('balance',$data['money']);
				// 进行事务提交
				if($draw_log && $account_dec) {
					Db::commit();
					$this->result('',1,'提交成功，请等待审核');
				}else{
					Db::rollback();
					$this->result('',0,'提交失败，请重新提交');
				}
			}else{
				$this->result('',0,$validate->getError());
			}
		}else{
			$this->result('',0,'手机验证码无效或已过期');
		}
		
	}

	/**
	 * 提现明细
	 */
	public function drawList()
	{
		$page = input('post.page') ? : 1;
		// 获取每页条数
		$pageSize = 10;
		// 获取分页总条数
		$count = Db::table('cs_apply_cash')->where('sid',$this->sid)->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('cs_apply_cash')
				->field('account,money,create_time,audit_status,audit_time,reason')
				->where('sid',$this->sid)
				->order('id desc')
				->page($page, $pageSize)
				->select();
		//8月13日孙烨兰修改
		if(!empty($list)){
			foreach ($list as $key => $value) {
				if(!empty($list[$key]['audit_time'])){
					$list[$key]['audit_time'] =date('Y-m-d H:i:s', $list[$key]['audit_time']);
				}	
			}
			// 返回给前端
			if($count > 0){
				$this->result(['list'=>$list,'rows'=>$rows],1,'获取成功');
			}
		}else{
			$this->result('',0,'暂无数据');
		}
		
		
	}

	/**
	 * 发送短信验证码
	 */
	public function vcode()
	{
		$mobile = $this->getMobile();
		$code = $this->apiVerify();
		$content = "您本次提现的验证码是【{$code}】，请不要泄露个任何人。";
		$res = $this->sms->send_code($mobile,$content,$code);
		$this->result('',1,'验证码已发送');
	}
}