<?php 
namespace app\shop\controller;
use app\base\controller\Shop;
use think\Db;
use Msg\Sms;

/**
 * 汽修厂邦保养操作
 */
class Bang extends Shop
{

	/**
	 * 进程初始化
	 */
	public function initialize()
	{
		parent::initialize();
	}


	/**
	 * 邦保养记录
	 */
	public function log()
	{
		$page = input('post.page') ? : 1;
		// 获取每页条数
		$pageSize = 10;
		// 获取分页总条数
		$count = Db::table('cs_income')
					->where('sid',$this->sid)
					->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('cs_income')
				->alias('i')
				->join(['u_card'=>'c'],'i.cid = c.id')
				->field('odd_number,cate_name,plate,i.create_time,i.id')
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


	/**
	 * 好评奖励
	 * @return [type] [description]
	 */
	public function praise()
	{
		$page = input('post.page') ? : 1;
		// 获取每页条数
		$pageSize = 1;
		// 获取分页总条数
		$count = Db::table('u_comment')
					->where('sid',$this->sid)
					->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('u_comment uc')
				->join('cs_income ci','uc.bid = ci.id')
				->join('u_card ca','ci.cid = ca.id')
				->where('uc.sid',$this->sid)
				->page($page,$pageSize)
				->order('uc.id desc')
				->field('odd_number,cate_name,plate,uc.create_time,uc.money')
				->select();
		// 返回给前端
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}
	
	/**
	 * 获取邦保养详情
	 */
	public function detail()
	{
		$id = input('post.id');
		$info = Db::table('cs_income')
				->alias('i')
				->join(['u_card'=>'c'],'i.cid = c.id')
				->field('odd_number,i.create_time,litre,filter,grow_up,i.hour_charge,total,oil_name,cate_name')
				->where('i.id',$id)
				->find();
		// 返回给前端
		if($info){
			$this->result($info,1,'获取成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}

	/**
	 * 获取邦保养信息
	 */
	public function getInfo()
	{
		// 获取车牌号
		$plate = input('post.plate','','strtoupper');
		// 检测该车辆是否在当前汽修厂
		$count  = 	Db::table('u_card')
					->where('sid',$this->sid)
					->where('plate',$plate)
					->where('pay_status',1)
					->count();
		// 如果该车存在
		if($count > 0){
			// 判断该车是否有邦保养次数
			$remain_times = Db::table('u_card')
							->where('plate',$plate)
							->value('remain_times');
			if($remain_times > 0){
				$info = $this->getCarInfo($plate);
				$check = $this->checkOil($this->sid,$info['oid'],$info['litre']);
				if($check !== false){
					$this->result($info,1,'获取信息成功');
				}else{
					$this->result('',0,'该油品库存不足');
				}
			}else{
				$this->result('',0,$plate.'邦保养次数为0');
			}
		}else{
			$this->result('',0,'该卡无效或不属于该汽修厂');
		}

	}

	/**
	 * 进行邦保养操作
	 */
	public function handle()
	{
		// 获取提交过来的数据
		$data = input('post.');
		// 实例化验证
		$validate = validate('Bang');
		// 如果验证通过则进行邦保养操作
		if($validate->check($data)){
			// 检测手机验证码是否正确
			$check = $this->sms->compare($data['phone'],$data['code']);
			if($check !== false){
				// 检测库存是否充足
				$oilCheck = $this->checkOil($this->sid,$data['oid'],$data['litre']);
				// 如果库存充足，则进行邦保养操作
				if($oilCheck !== false){
					// 获取运营商处设定的金额
					$rd = Db::table('cs_shop')
							->alias('s')
							->join(['ca_agent_set'=>'a'],'s.aid = a.aid')
							->field('shop_fund,shop_hours,s.aid')
							->where('s.id',$this->sid)
							->find();
					// 获取卡的总金额
					$price = Db::table('u_card')->where('id',$data['cid'])->value('card_price');
					// $shop_fund = $price*$rd['shop_fund']/100;//0831 14:47 xjm
					// 构建邦保养记录数据
					$arr = [
						'sid' => $this->sid,
						'odd_number' => build_order_sn(),
						'cid' => $data['cid'],
						'oil' => $data['oil'],
						'uid' => $data['uid'],
						'litre' => $data['litre'],
						'filter' => $data['filter'],
						// 'grow_up' => $shop_fund,//0831 14:47 xjm
						'hour_charge' => $data['hour_charge'],
						'total' => $data['hour_charge']+$data['filter']
					];
					// 可提现收入
					$money = $data['hour_charge']+$data['filter'];
					// 开启事务
					Db::startTrans();
					// 减少用户卡的次数
					$card_dec = Db::table('u_card')
								->where('id',$data['cid'])
								->setDec('remain_times');
					// 汽修厂库存减少
					$ration_dec = Db::table('cs_ration')
									->where('sid',$this->sid)
									->where('materiel',$data['oid'])
									->setDec('stock',$data['litre']);
					// 汽修厂账户余额增加服务次数增加
					$shop_inc = Db::table('cs_shop')
									->where('id',$this->sid)
									->inc('balance',$money)
									->inc('service_num',1)
									->update();
					// 运营商邦保养次数增加
					$service_num = Db::table('ca_agent')
										->where('aid',$rd['aid'])
										->inc('service_time',1)
										->update();

					// 生成邦保养记录
					$bang_log = Db::table('cs_income')
									->strict(false)
									->insert($arr);
					// 事务提交判断
					if($card_dec && $ration_dec  && $shop_inc && $bang_log && $service_num){
						Db::commit();
						$this->result('',1,'本次服务已完成');
					}else{
						Db::rollback();
						$this->result('',0,'提交失败');
					}
				}else{
					$this->result('',0,'该油品库存不足');
				}
			}else{
				$this->result('',0,'手机验证码无效或已过期');
			}
		}else{
			$this->result('',0,$validate->getError());
		}
	}


	/**
	 * 发送短信验证码
	 */
	public function vcode()
	{
		$mobile = input('post.mobile');
		$card_number = input('post.card_number');
		$code = $this->apiVerify();
		$content = "您邦保养卡号为【{$card_number}】参与本次保养的验证码为【{$code}】，请勿泄露给其他人。";
		$res = $this->sms->send_code($mobile,$content,$code);
		if($res == "提交成功"){
			$this->result('',1,'验证码发送中');
		}else{
			$this->result('',0,'验证码发送中');
		}
		
	}

	/**
	 * 检测库存油品是否充足
	 */
	public function checkOil($sid,$oid,$litre)
	{
		// 获取该油品库
		$stock = Db::table('cs_ration')
						->where([
							'materiel' => $oid,
							'sid' => $sid
						])
						->value('stock');
		// 检测该油品库存是否充足
		return ($stock < $litre) ? false : true;
	}

	/**
	 * 获取车辆信息
	 */
	public function getCarInfo($plate)
	{
		return 	Db::table('u_card')
				->alias('c')
				->join(['u_user'=>'u'],'c.uid = u.id')
				->join(['co_bang_data'=>'d'],'c.car_cate_id = d.cid')
				->join(['co_car_cate'=>'car'],'c.car_cate_id = car.id')
				->join(['co_bang_cate'=>'ba'],'c.oil = ba.id')
				->where('plate',$plate)
				->where('c.pay_status',1)
				->field('u.name,u.phone,u.id as uid,d.month,d.km,d.filter,d.litre,car.type,c.card_number,c.remain_times,ba.name as oil,c.oil as oid,c.id as cid,c.plate,hour_charge')
				->find();
	}


	
	/**
	 * 显示该维修厂下邦保养记录中已有的车牌号进行模糊查询
	 * @return [type] [description]
	 */
   	public function query()
   	{
      	$plate = input('post.plate');
        $list = Db::table('u_card')
        	    ->where('plate','like','%'.$plate.'%')
      	        ->where('sid',$this->sid)
         	    ->field('plate')
       	        ->distinct(true)
        	    ->select();
		$arr = array();
     	foreach ($list as $key=>$value){
   	        $arr[] = $value['plate'];
        }
        return $arr;
    }
}