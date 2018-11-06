<?php 
namespace app\shop\controller;
use app\base\controller\Shop;
use think\Db;
use think\File;
use Msg\Sms;
use Geo\Geo;
use MAP\Map;

/**
* 个人中心
*/
class Main extends Shop
{
	/**
	 * 进程初始化
	 */
	public function initialize()
	{
		parent::initialize();
	}

	/**
	 * 物料预警
	 */
	public function mateTips()
	{
		// 2018.08.28 14:14 张乐召  添加 判断是否有未处理的物料申请订单   如有订单未处理 则不提示物料补充,提示有待处理的物料申请订单
        		$msg = Db::table('cs_apply_materiel')->where('sid',$this->sid)->where('audit_status',0)->count();  // 未处理订单条数
       		 if ($msg == 0){  // 当 订单数为 0
           		 $count = $this->mateCount();
           		 if($count >0){
             		   $this->result('',0,'有物料库存不足，请及时补充');
          		  }else{
              		  $this->result('',1,'物料库存充足');
          		  }
      		  }else{   //  未处理订单不为 0
          		  $this->result('',2,'您有待处理的物料申请订单');
    		    }
	}

	/**
	 * 获取用户
	 */
	public function getUsers()
	{
		$page = input('post.page') ? : 1;
		// 获取每页条数
		$pageSize = 10;
		// 获取分页总条数
		$count =  Db::table('u_card')
					->alias('c')
					->join(['u_user'=>'u'],'c.uid = u.id')
					->field('plate,card_number,remain_times,u.name,u.phone,sale_time')
					->where('sid',$this->sid)
					->where('pay_status',1)
					->count();
		$rows = ceil($count / $pageSize);
		// 获取数据
		$list = Db::table('u_card')
				->alias('c')
				->join(['u_user'=>'u'],'c.uid = u.id')
				->field('c.uid,plate,card_number,remain_times,u.name,u.phone,sale_time')
				->where('sid',$this->sid)
				->where('pay_status',1)
				->order('u.id desc')
				->page($page, $pageSize)
				->select();
		// 判断用户是否是会员
		foreach ($list as $k => $v) {
			$status = Db::table('u_member_table')->where(['uid'=>$v['uid'],'pay_status'=>1])->count();
			if($status > 0){
				$list[$k]['status'] = 1;
			}else{
				$list[$k]['status'] = 0;
			}
		}
		//判断结束
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}

	/**
	 * 获取技师列表
	 */
	public function getTns()
	{
		$page = input('post.page') ? : 1;
		// 获取每页条数
		$pageSize = 10;
		// 获取分页总条数
		$count = Db::table('tn_user')->where('sid',$this->sid)->where('repair',1)->count();
		$rows = ceil($count / $pageSize);
		// 获取数据
		$list = Db::table('tn_user')
				->field('name,phone,server,cert,id')
				->where('sid',$this->sid)
				->where('repair',1)
				->order('id desc')
				->page($page, $pageSize)
				->select();
		// 返回数据给前端
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}

	/**
	 * 获取技师列表
	 */
	public function getTnsDetail()
	{
		$id = input('post.id');
		$info = Db::table('tn_user')->where('id',$id)->field('name,phone,server,skill,wx_head,head,certify_time')->find();
		//2018.08.27 15:08  张乐召   添加 技师换店记录
        		$info['detail'] = Db::table('tn_exshop')
            		->alias('a')
            		->join('cs_shop b','b.id = a.sid','LEFT')
            		->where('a.uid',$id)
            		->where('a.status',1)
            		->field('b.usname,a.reason,a.create_time,a.audit_time')
            		->select();
		// 返回数据给前端
		if($info){
			$this->result($info,1,'获取成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}

	/**
	 * 认证技师
	 */
	public function auditTns()
	{
		$id = input('post.id');
		$cert = input('post.cert');
		$tag = ($cert == 0) ? 1 : 0;
		$res = Db::table('tn_user')->where('id',$id)->setField(['cert'=>$tag,'certify_time'=>time()]);//认证技师
		if($cert == 0) {
			//认证
			$re = Db::table('cs_shop')
				->where('id',$this->sid)
				->setInc('tech_num',1);
		} else {
			//取消认证
			$re = Db::table('cs_shop')
					->where('id',$this->sid)
					->setDec('tech_num',1);
		}
		if($res !== false){
			if($tag==1){
				$this->result('',1,'认证成功');
			}
			if($tag==0){
				$this->result('',1,'已取消认证');
			}
		}else{
			$this->result('',0,'认证失败');
		}	
	}

	/**
	 * 获取账户信息
	 */
	public function getAccount()
	{
		// 在页面返回手机号
		$phone = Db::table('cs_shop')->where('id',$this->sid)->value('phone');
		// 检测用户是否存在
		$count = Db::table('cs_shop_set')->where('sid',$this->sid)->count();
		// 如果存在则修改用户信息
		if($count > 0){
			$data = Db::table('cs_shop_set')->field('bank,account_name,branch,account')->where('sid',$this->sid)->find();
			$data['phone'] = $phone;
			$this->result($data,1,'success');
		}else{
			$this->result(['phone'=>$phone],0,'该用户为新用户');
		}
	}

	/**
	 * 修改银行账户
	 */
	public function setAccount()
	{
		// 检测用户是否完善信息
		$count = Db::table('cs_shop_set')->where('sid',$this->sid)->count();
		// 获取数据
		$data = input('post.');
		// 获取用户手机号码
		$mobile = $this->getMobile();
		// 验证码短信验证码
		$check = $this->sms->compare($mobile,$data['code']);
		
		if($check !== false){
			if($count > 0){
				$res = 	Db::table('cs_shop_set')
					->where('sid',$this->sid)
					->update(['branch'=>$data['branch'],'bank'=>$data['bank'],'account_name'=>$data['account_name'],'account'=>$data['account']]);
			}else{
				$data['sid'] = $this->sid;
				$res = 	Db::table('cs_shop_set')->strict(false)->insert($data);
			}
			if($res && $res !== false){
				$this->result('',1,'修改成功');
			}else{
				$this->result('',0,'修改失败');
			}
		}else{
			$this->result('',0,'手机验证码无效或已过期');
		}
	}


	/**
	 * 获取基本信息
	 */
	public function getInfo()
	{
		// 检测用户详情是否存在
		$count = Db::table('cs_shop_set')->where('sid',$this->sid)->count();
		// 如果存在则返回该用户信息
		if($count > 0){
			$data = Db::table('cs_shop')
					->alias('c')
					->join(['cs_shop_set'=>'s'],'s.sid = c.id')
					->field('license,photo,major,company,about,leader,province,city,county,address,serphone,lat,lng')
					->where('c.id',$this->sid)
					->find();
			$data['major'] = explode(',', $data['major']);
			$this->result($data,1,'获取数据成功');
		}else{
			// 否则返回空信息
			$data = Db::table('cs_shop')->where('id',$this->sid)->field('company,leader')->find();
			$this->result($data,0,'该用户为新用户');
		}
	}

	/**
	 * 修改基本信息
	 */
	public function setInfo()
	{
		// 获取提交过来的信息
		$data = input('post.');
		//将提交过来的主修数组 分割为字符串
		$data['major'] = implode(',', $data['major']);
		//实例化
		$map = new Map();
		//空格无害化处理
		$data['address'] = str_replace(' ', '', $data['address']);
		//拼接地址
		$adre = $data['province'].$data['city'].$data['county'].$data['address'];
		//后台获取经纬度
		$data['lat'] = $map->maps($adre)['lat'];
		$data['lng'] = $map->maps($adre)['lng'];
		// 接受图片json处理
		$data['photo'] = json_encode($data['photo']);
		// 根据坐标获得hash值
		$geo = new Geo();
		$data['hash_val'] = $geo->encode_hash($data['lat'],$data['lng']);
		// 检测countyid是否更新
		if($data['county_id'] == ''){
			unset($data['county_id']);
		} 
		// 删除token
		unset($data['token']);
		// 检测用户详情是否存在
		$count = Db::table('cs_shop_set')
					->where('sid',$this->sid)
					->count();
		// 如果存在则修改用户信息
		if($count > 0){
			//获取维修厂当前状态
			$audit_status = Db::table('cs_shop')
							->where('id',$this->sid)
							->value('audit_status');
			if($audit_status < 1){
				//将状态改为已完善信息
				Db::table('cs_shop')
					->where('id',$this->sid)
					->setField('audit_status',1);
			}
			//更新维修厂店铺信息
			$save = Db::table('cs_shop_set')
						->where('sid',$this->sid)
						->update($data);
			if($save !== false){
				$this->setAgent($this->sid);
				$this->result('',1,'保存成功');
			}else{
				$this->result('',0,'保存失败');
			}
		}else{
			// 添加新的信息
			$data['sid'] = $this->sid;
			$save = Db::table('cs_shop_set')->insert($data);
			//修改完善状态
			Db::table('cs_shop')
				->where('id',$this->sid)
				->setField('audit_status',1);
			if($save){
				$this->setAgent($this->sid);
				$this->result('',1,'保存成功,请重新登录');
			}else{
				$this->result('',0,'保存失败');
			}
		}
	}
	/**
	 * 完善店铺信息 维修厂名称，负责人，主修，省市县，详细地址，服务电话，店铺简介，店铺照片，营业执照
	 * 新版本维修厂使用
	 */
	public function setShopInfo()
	{
		$data = input('post.');
		//实例化验证
		$validate = validate('Info');
		if($validate->check($data)){
			//将提交过来的主修数组 分割为字符串
			$data['major'] = implode(',', $data['major']);
			//实例化
			$map = new Map();
			//空格无害化处理
			$data['address'] = str_replace(' ', '', $data['address']);
			//拼接地址
			$adre = $data['province'].$data['city'].$data['county'].$data['address'];
			//后台获取经纬度
			$data['lat'] = $map->maps($adre)['lat'];
			$data['lng'] = $map->maps($adre)['lng'];
			// 接受图片json处理
			$data['photo'] = json_encode($data['photo']);
			// 根据坐标获得hash值
			$geo = new Geo();
			$data['hash_val'] = $geo->encode_hash($data['lat'],$data['lng']);
			unset($data['token']);
			//构建更新cs_shop表的数组
			$arr = [
				'company' => $data['company'],
				'leader'  => $data['leader']
			];
			unset($data['company']);
			unset($data['leader']);
			Db::startTrans();
			//获取维修厂当前状态
			$audit_status = Db::table('cs_shop')
							->where('id',$this->sid)
							->value('audit_status');
			if($audit_status < 1){
				//将状态改为已完善信息
				$re = Db::table('cs_shop')
					  ->where('id',$this->sid)
					  ->setField('audit_status',1);
			} else {
				$re = 1;
			}
			//更新维修厂店铺信息
			$save = Db::table('cs_shop_set')
						->where('sid',$this->sid)
						->update($data);
			$save_m = Db::table('cs_shop')
					->where('id',$this->sid)
					->update($arr);
			if($audit_status == 0) {
					$msg = '您的资料已提交审核';
				} else {
					$msg = '修改成功';
				}
			if($re!==false && $save!==false && $save_m!==false){
				Db::commit();
				$this->setAgent($this->sid);
				$this->result('',1,$msg);
			}else{
				Db::rollback();
				$this->result('',0,'保存失败');
			}
		} else {
			$this->result('',0,$validate->getError());
		}
	}

	/**
	 * 获取统计数字
	 */
	public function getCounts()
	{
		// 获取排名
		$rank = Db::table('cs_shop')->where('id','<',$this->sid)->count();
		// 获取本月数据
		$month = date('Y-m');
		// 获取上级运营商手机号
		$phone = Db::table('cs_shop')
					->alias('s')
					->join('ca_agent a','s.aid = a.aid')
					->where('id',$this->sid)
					->value('a.phone');
		$card_count = Db::table('u_card')
						->where(['sid'=>$this->sid,'pay_status'=>1])
						->where('sale_time','like',$month.'%')
						->count();
		// 本月售卡
		$card_month = Db::table('cs_shop')
						->where('id',$this->sid)
						->value('card_month');
		$cards = 30 - $card_month; 
		$this->result(['rank'=>$rank,'bangs'=>$card_month,'cards'=>$cards,'phone'=>$phone],1,'获取成功');
	}

	
	/**
	 * 上传图片
	 */
	public function uploadImg()
	{
		return upload('file','shop/photo','https://cc.ctbls.com');
	}

	/**
	 * 修改密码
	 */
	public function setPasswd()
	{
		// 获取数据
		$data = input('post.');
		// 获取用户手机号码
		$mobile = $this->getMobile();
		// 验证码短信验证码
		$check = $this->sms->compare($mobile,$data['code']);
		if($check !== false){
			// 获取原密码
			$op = Db::table('cs_shop')->where('id',$this->sid)->value('passwd');
			// 检测原密码
			if(get_encrypt($data['passwd']) == $op){
				// 检测数据提交
				if($data['npasswd'] == $data['spasswd']){
					//此处之前有个小bug，更新的一直是原密码
					$res = Db::table('cs_shop')
							->where('id',$this->sid)
							->setField('passwd',get_encrypt($data['npasswd']));
					if($res !== false){
						$this->result('',1,'修改成功');
					}else{
						$this->result('',0,'修改失败');
					}
				}else{
					$this->result('',0,'两次密码不一致');
				}
				// 进行密码修改
			}else{
				$this->result('',0,'原密码错误');
			}
		}else{
			$this->result('',0,'手机验证码无效或已过期');
		}
	}
	
	/**
	 * 取消合作
	 */
	public function abolish()
	{
		// 获取取消合作理由
		$reason = input('post.reason');
		// 获取代理商
		$info = Db::table('cs_shop')->where('id',$this->sid)->field('aid,company,leader')->find();
		if(strlen(trim($reason)) > 2){
			// 构建数据
			$data = [
				'sid' => $this->sid,
				'aid' => $info['aid'],
				'company' => $info['company'],
				'leader' => $info['leader'],
				'reason' => $reason
			];
			// 检测是否提交过，防止重复提交
			$count = Db::table('cs_apply_cancel')->where('sid',$this->sid)->count();
			if($count > 0){
				$this->result('',0,'已提交过，请勿重复提交');
			}else{
				$res = Db::table('cs_apply_cancel')->insert($data);
				if($res){
					$this->result('',1,'提交成功');
				}else{
					$this->result('',0,'提交失败');
				}
			}
		}else{
			$this->result('',0,'取消理由最少为2个字');
		}
		
	}


	/**
	 * 获取省列表
	 */
	public function getProvince()
	{
		return $this->province();
	}

	/**
	 * 获取市列表
	 */
	public function getCity()
	{
		return $this->city(input('post.pid'));
	}
	
	/**
	 * 获取县列表
	 */
	public function getCounty()
	{
		return $this->county(input('post.cid'));
	}

	/**
	 * 获取银行账户
	 */
	public function getBankCode()
	{
		return $this->bankCode();
	}

	/**
	 * 发送短信验证码
	 */
	public function pcode()
	{
		$mobile = $this->getMobile();
		$code = $this->apiVerify();
		$content = "您的短信验证码是【{$code}】。您正在通过手机号重置登录密码，如非本人操作，请忽略该短信。";
		$res = $this->sms->send_code($mobile,$content,$code);
		if($res == "提交成功"){
			$this->result('',1,'发送成功');
		} else {
			$this->result('',0,'由于短信平台限制，您一天只能接受五次验证码');
		}
	}

	/**
	 * 更改账号验证码
	 */
	public function ecode()
	{
		$mobile = $this->getMobile();
		$code = $this->apiVerify();
		$content = "您的短信验证码是【{$code}】。您正在通过手机号修改银行账户号，如非本人操作，请忽略该短信。";
		$list = $this->sms->send_code($mobile,$content,$code);
		$this->result('',1,'验证码已发送');
	}
	/**
	 * 修改手机号发送短信验证码
	 * 新版本维修厂使用
	 * @return [type] [description]
	 */
	public function alterCode(){
		$mobile = $this->getMobile();
		$code = $this->apiVerify();
		$countent = "您正在修改手机号，验证码【{$code}】，请在5分钟内按页面提示提交验证码，切勿将验证码泄露于其他人。";
		$res = $this->sms->send_code($mobile,$countent,$code);
		if($res == "提交成功"){
			$this->result('',1,'发送成功');
		} else {
			$this->result('',0,'由于短信平台限制，您一天只能接受五次验证码');
		}
	}

	/**
	 * 修改手机号
	 * 新版本使用
	 * @return [type] [description]
	 */
	public function alterPhone()
	{
		$data = input('post.');
		$mobile = $this->getMobile();
		$validate = validate('Phone');
		if($validate->check($data)){
			//检测手机验证码是否正确
			$check = $this->sms->compare($mobile,$data['code']);
			// $check = true;
			if($check !== false) {
				// 进行修改手机号的操作
				$count = Db::table('cs_shop')
							->where('phone',$data['r_phone'])
							->count();
				if($count == 0) {
					$res = Db::table('cs_shop')
							->where('id',$this->sid)
							->setField('phone',$data['r_phone']);
					if($res !== false) {
						$this->result('',1,'修改成功');
					} else {
						$this->result('',0,'修改失败');
					}
				} else {
					$this->result('',0,'该手机号已被使用');
				}
			} else {
				$this->result('',0,'手机验证码无效或已过期');
			}
		} else {
			$this->result('',0,$validate->getError());
		}
	}
	/**
	 * 获取维修厂公司名称
	 * @return [type] [description]
	 */
	public function getName(){
		return Db::table('cs_shop')->where('id',$this->sid)->value('company');
	}
}