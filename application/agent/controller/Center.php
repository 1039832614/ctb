<?php 
namespace app\agent\controller;
use app\base\controller\Agent;
use think\File;
use think\Db;

/**
* 个人中心
*/
class Center extends Agent{
	
	function initialize(){
		parent::initialize();
		$this->agent='ca_agent';
		$this->area='ca_area';
		$this->agent_set='ca_agent_set';
		$this->china='co_china_data';
	}
	/**
	 * 未审核列表
	 * @return [type] [description]
	 */
	public function notList()
	{
		$page = input('post.page')? : 1;
		$this->ration($page,0);
	}
	/**
	 * 配给成功列表
	 * @return [type] [description]
	 */
	public function ratAdopt()
	{
		$page = input('post.page')? : 1;
		$this->ration($page,1);
	}

	/**
	 * 配给驳回列表
	 * @return [type] [description]
	 */
	public function rejList()
	{
		$page = input('post.page')? : 1;
		$this->ration($page,2);
	}

	/**
	 * 点击区域个数显示区域
	 * @return [type] [description]
	 */
	public function cenRegion()
	{
		// 获取本次地区申请id
		$id = input('post.id');
		$county = Db::table('ca_increase')
					->where('id',$id)
					->value('area');
		// 获取省市县名称id
        $list = $this->county($county);          
        if($list){
        	$this->result($list,1,'获取地区列表成功');
        }else{
        	$this->result('',0,'暂未设置地区');
        }
	}

	/**
	 * 驳回列表修改页面信息
	 * @return [type] [description]
	 */
	public function updIndex()
	{	
		// 获取修改地区订单id
		$id = input('post.id');
		$data = Db::table('ca_increase')
				->where('id',$id)
				->field('id,aid,voucher,price,area')
				->find();
		if($data){
			//根据所选县id得出所选县名称
			$list = $this->county($data['area']);
			if($list){
				$this->result(['data'=>$data,'list'=>$list],1,'获取成功');
			}else{
				$this->result('',0,'获取数据失败');
			}
		}else{
			$this->result('',0,'暂无数据');
		}
	}

	/**
	 * 地区修改
	 * @return [type] [description]
	 */
	public function updRegion()
	{
		// 获取设置地区订单id、重新选择的区县、转账金额、转账凭证
		$data = input('post.');
		if($data){
			$arr = [
				'area' => implode(',',$data['county']),
				'regions' => $data['price']/35000,
				'price' =>$data['price'],
				'audit_status' => 0,
				'voucher' =>$data['voucher'],
			];
			//填写总金额，上传支付凭证
			$res = Db::table('ca_increase')
					->where([
						'id'=>$data['id'],
						'aid'=>$this->aid
					])
					->update($arr);
			//判断这个运营商有没有配给
			$count = Db::table('ca_ration')
						->where('aid',$this->aid)
						->count();
			if($count == 0) {
				//首次开通
				$ar = Db::table('ca_agent')
						->where('aid',$this->aid)
						->setField('status',4);
			}
			if($res !== false){
				Db::commit();
				$this->result('',1,'修改成功,请等待总后台审核');
			}else{
				Db::rollback();
				$this->result('',0,'设置失败');
			}
		}else{
			Db::rollback();
			$this->result('',0,'请检查您填写的数据');
		}
	}

	/**
	 * 配给列表操作
	 * @param  [type] $status [description]
	 * @return [type]         [description]
	 */
	private function ration($page,$status)
	{	
		$pageSize = 10;
		$count = Db::table('ca_increase')
					->where([
						'audit_status'=>$status,
						'aid'=>$this->aid,
						'pay_status' => 1
					])
					->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('ca_increase')
				->where([
					'audit_status' => $status,
					'aid' => $this->aid,
					'pay_status' => 1
				])
				->order('id desc')
				->page($page,$pageSize)
				->select();
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}
	/**
	 * 个人中心首页
	 * @return 运营商信息，运营商id
	 */
	public function index(){
			// 页面数据
			$list = $this->alist($this->aid);
			if($list){
				$this->result($list,1,'获取页面数据成功');

			}else{
				$this->result('',0,'获取页面数据失败');
			}
		
	}
	/**
	 * 设置运营商供应地区及上传支付凭证以及营业执照
	 * 0904xjm
	 * @return [type]
	 */
	public function setArea(){
		// 选择的区县、转账金额、转账凭证
		$data = input('post.');
		// print_r($data);exit;
		if($data){
			Db::startTrans();
            $count = Db::table('ca_ration')
            			->where('aid',$this->aid)
            			->count();
			$data['area'] = implode(',',$data['county']);
			$data['regions'] = $data['price']/35000;
			$data['aid'] = $this->aid;
			if($count == 0){
				//如果是第一次则不收额外的系统使用费
				$data['pay_status'] = 1;
				//如果是第一次则上传营业执照并更新状态值
				$st = Db::table('ca_agent')
                    ->where('aid',$this->aid)
                    ->update([
                        'license' => $data['license'],
                        'status'  => 4
                    ]);
			} else {
				//否则将收取额外的系统使用费
				$data['pay_status'] = 0;
 			}
			//填写总金额，上传支付凭证,营业执照
			$res = Db::table('ca_increase')
					->strict(false)
					->insert($data);
			if($res!==false){
				Db::commit();
				$this->result('',1,'设置成功,请等待总后台审核');
			}else{
				Db::rollback();
				$this->result('',0,'设置失败');
			}
		}else{
			Db::rollback();
			$this->result('',0,'地区不能为空');
		}
	}

	/**
     * 获取县级城市名称
     * @return  未选中的城市名称及已选中的城市名称
     */
    public function selCounty(){
        $city=input('post.id');
        $county=$this->city($city); //未被选中城市
        $selCounty=Db::table('ca_area')->select();//已被选中的城市
        if(!empty($county)){
        	$data = ['county'=>$county,'selCounty'=>$selCounty];
        	$this->result($data,1,'获取列表成功');
        }else{
        	$this->result('',0,'获取列表失败');
        }
    }
	
	/**
	 * 上传营业执照
	 * @return 成功或失败
	 */
	public function license(){
		$license = input('post.license');
		$res = Db::table($this->agent)
				->where('aid',$this->aid)
				->update(['license'=>$license]);
		if($res){
			$this->result('',1,'上传营业执照成功');
		}else{
			$this->result('',0,'上传营业执照失败');
		}
	}
	/**
	 * 上传支付凭证
	 * @return 成功或失败
	 */
	public function usecost(){
		$usecost = input('post.usecost');
		$res = Db::table($this->agent)
				->where('aid',$this->aid)
				->update(['usecost'=>$usecost]);
		if($res){
			$this->result('',1,'上传支付凭证成功');
		}else{
			$this->result('',0,'上传支付凭证失败');
		}
	}
	/**
	 * 查看运营商所供应地区
	 * @return 返回地区名称
	 */
	public function area(){
		// 通过区县id获得市级id
		$county = Db::table($this->area)
					->where('aid',$this->aid)
					->select();
		$county = array_str($county,'area');
		$city = $this->areaList($county);
		$province = $this->areaList($city);
		$list = Db::table($this->china)
				->whereIn('id',$province.','.$city.','.$county)
				->select();
		if($list){
			$list = get_child($list,$list[0]['pid']);
			$this->result($list,1,'获取列表成功');
		}else{	
			$this->result('',0,'暂未设置供应地区');
		}
	}
	/**
	 * 修改账户信息
	 * @return 修改成功或失败
	 */
	public function editAccount(){
		$data = input('post.');
		$validate = validate('Account');
		if($validate->check($data)){
			if($this->sms->compare($data['phone'],$data['code'])){
				$arr = [
					'phone'=>$data['phone'],
					'account'=>$data['account'],
					'bank_name'=>$data['bank_name'],
					'branch'=>$data['branch'],
				];
				$res=Db::table($this->agent)->where('aid',$this->aid)->update($arr);
				if($res){
					$this->result('',1,'修改账户成功');
				}else{
					$this->result('',0,'修改账户失败');
				}
			}else{
				$this->result('',0,'手机验证码错误');
			}
		}else{
			$this->result('',0,$validate->getError());
		}
	}
	/**
	 * 发送修改账户信息的验证码
	 * @return [type] [description]
	 */
	public function accountCode()
	{
		$phone=input('post.phone');
		// 生成四位验证码
        $code=$this->apiVerify();
		$content="您的短信验证码是【".$code."】。您正在通过手机号修改银行账户号，如非本人操作，请忽略该短信。";
		return $this->smsVerify($phone,$content,$code);
	}
	/**
	 * 修改密码
	 * @return [type] [description]
	 */
	public function modifyPass()
	{
		$data=input('post.');
		$validate=validate('ModifyPass');
		if($validate->check($data)){
			// 判断原密码是否输入正确
			if($this->pass($data['pass'],$this->aid) == false){
				$this->result('',0,'请输入正确的原密码');
			}
			// 判断手机验证码是否正确
			if($this->sms->compare($data['phone'],$data['code'])){
				if($this->xPass(get_encrypt($data['npass']),$this->aid)){
					$this->result('',1,'修改密码成功');
				}else{
					$this->result('',0,'修改密码失败');
				}
			}else{
				$this->result('',0,'手机验证码错误');
			}
		}else{
			$this->result('',0,$validate->getError());
		}
	}
	/**
	 * 检查运营商是否有上传营业执照，和设置供应地区
	 * @return [type] [description]
	 */
	public function selArea()
	{
		// 判断用户有没有上传营业执照
		$lice = Db::table('ca_agent')
					->where('aid',$this->aid)
					->value('license');
		$usecost = Db::table('ca_agent')
					->where('aid',$this->aid)
					->value('usecost');
		if(empty($lice) && empty($usecost)){
			$this->result('',2,'您还未上传营业执照，请您到个人中心上传。');
		}
		$status = Db::table('ca_agent')
					->where('aid',$this->aid)
					->value('status');
		if($status == 2 ){
			// 查看运营商是否设置地区
			$count = Db::table('ca_increase')->where('aid',$this->aid)->count();
			if($count > 0){
				$this->result('',1,'已设置地区');
			}else{
				$this->result('',0,'您还未设置地区，请您到个人中心->供应地区->设置您的地区。');
			}
		}else if($status == 1){
			$this->result('',1,'您已上传营业执照。');
		}
	}

	/**
	 * 根据县区id获取县市省名称
	 * @return [type] [description]
	 */
	public function county($county)
	{
		// 市级id转换为字符串
        $city=$this->areaList($county);
        // 省级id转换为字符串
        $province=$this->areaList($city);
        // 查询所有省市县的数据
        $list=Db::table('co_china_data')->whereIn('id',$province.','.$city.','.$county)->select();
        return get_child($list,$list[0]['pid']);

	}
	/**
	 * 判断原密码是否正确
	 * @param  [type] $npass [修改的原密码]
	 * @param  [type] $aid   [运营商id]
	 * @return [type]        [布尔值]
	 */
	private function pass($pass,$aid)
	{	
		$pwd = Db::table('ca_agent')
				->where('aid',$aid)
				->value('pass');
		if(get_encrypt($pass) !== $pwd){
			return false;
		}else{
			return true;
		}
	}
	/**
	 * 修改密码
	 * @param  [type] $npass [新密码]
	 * @param  [type] $aid   [运营商id]
	 * @return [type]        [布尔值]
	 */
	private function xPass($npass,$aid)
	{
		$res = Db::table('ca_agent')
		 		->where('aid',$aid)
		 		->setField('pass',$npass);
		if($res !== false){
			return true;
		}
	}
	/**
	 * 查询运营商详细信息
	 * @param  运营商id
	 * @return 运营商详细信息
	 */
	private function alist($aid){
		$list = Db::table('ca_agent')
			    ->where('aid',$aid)
			    ->field('status,aid,login,company,account,leader,province,city,county,address,phone,open_shop,license,usecost,shop_nums')
			    ->find();
		return $list;
	}
	/**
	 * 运营商供应地区树形结构
	 * @return 城市地区id
	 */
	private function areaList($city)
	{
		$city = Db::table($this->china)
				->whereIn('id',$city)
				->select();
		return array_str($city,'pid');
	}
	/**
	 * 修改shop表状态为2
	 * @return  布尔值
	 */
	private function shopSta($aid){
		$res = Db::table($this->agent)
				->where('aid',$aid)
				->setField('status',2);
		if($res){
			return true;
		}
	}

	 /**
     * 登录的修改密码短信验证码
     * @var string
     */
    public function cenFor()
    {
        $phone=input('post.phone');
        return $this->forCode($phone);
    }
    /**
	 * 修改手机号发送短信验证码
	 * @return [type] [description]
	 */
	public function alterCode(){
		$mobile = $this->getAgentPhone();
		$code = $this->apiVerify();
		$countent = "您正在修改手机号，验证码【{$code}】，请在5分钟内按页面提示提交验证码，切勿将验证码泄露于其他人。";
		$list = $this->sms->send_code($mobile,$countent,$code);
		$this->result('',1,'验证码已发送');
	}

	/**
	 * 修改手机号
	 * @return [type] [description]
	 */
	public function alterPhone()
	{
		$data = input('post.');
		$mobile = $this->getAgentPhone();
		$validate = validate('Phone');
		if($validate->check($data)){
			//检测手机验证码是否正确
			$check = $this->sms->compare($mobile,$data['code']);
			// $check = true;
			if($check !== false) {
				// 进行修改手机号的操作
				$count = Db::table('ca_agent')
							->where('phone',$data['r_phone'])
							->count();
				if($count == 0) {
					$res = Db::table('ca_agent')
							->where('aid',$this->aid)
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
	//  * 获取运营商的手机号
	//  * @return [type] [description]
	//  */
	public function getAgentPhone(){
		return Db::table('ca_agent')
					->where('aid',$this->aid)
					->value('phone');
	}
	/**
	 * 判断该运营商是否为他最后一次选择地区支付了系统使用费。
	 * @return [type] [description]
	 */
	public function ifPayIncrease(){
		$status = Db::table('ca_increase')
				->where('aid',$this->aid)
				->order('id desc')
				->limit(1)
				->value('pay_status');
		if($status == 1){
			//已支付额外的系统使用费了
			$this->result('',1,'支付成功，等待总后台审核');
		} else {
			$this->result('',0,'此操作需支付额外的系统使用费,请扫码支付');
		}

	}


    /***************************投诉管理********************************************/
    // 2018.10.02 张乐召 添加

    // 判断该运营商地区是否有 服务经理
    public function ifmansger()
    {
        // 运营商的运营区域ID
        $areaid = Db::table('ca_area')
            ->where('aid',$this->aid)
            ->field('area')
            ->select();
        // 搜索这些区域的市级ID
        if (!empty($areaid)){
            $arr = array();
            foreach ($areaid as $key=>$value){
                $arr[] = Db::table('co_china_data')
                    ->where('id',$areaid[$key]['area'])
                    ->value('pid');
            }
            if (!empty($arr)){
                $city_id = array_unique($arr);
                // 查询 服务经理的地区表是否有该地区
                foreach ($city_id as $k=>$v){
                    $sm_area = Db::table('sm_area')
                        ->where('audit_status',1)
                        ->where('pay_status',1)
                        ->where('sm_type',1)
                        ->where('is_exits',1)
                        ->where('sm_mold','<>',2)
                        ->where('sm_mold','<>',3)
                        ->where('area',$v)
                        ->find();
                }
                if (!empty($sm_area)){
                    // 查询该服务经理的姓名和ID
                    $info = Db::table('sm_user')
                        ->where('id',$sm_area['sm_id'])
                        ->field('id,name')
                        ->find();
                    $this->result($info,1,'该区域有服务经理');
                }else{
                    $this->result('',0,'该区域没有服务经理,暂时无法投诉');
                }
            }
        }
    }

    // 投诉操作
    public function complaint()
    {
        $data = input('post.');
        if (strlen($data['content']) > 150){
            $this->result('',0,'最多可输入50个汉字');
        }
        // 查询该 运营商 的信息
        $info = Db::table('ca_agent')
            ->where('aid',$this->aid)
            ->find();
        // 查询 该 运营商 的省市区ID
        $area = Db::table('ca_area')
            ->where('aid',$this->aid)
            ->field('area')
            ->select();
        if (!empty($area)){
            // 将 地区ID 用逗号隔开
            foreach ($area as $key=>$value){
                $arr[] = $area[$key]['area'];
            }
            $county_id = implode(',',array_unique($arr));
//        var_dump($county_id);exit;
            // 查询 省级ID 和 市级ID
            // 市级ID
            $city_id = Db::table('co_china_data')->where('id',$area[0]['area'])->value('pid');
            // 省级ID
            $pro_id = Db::table('co_china_data')->where('id',$city_id)->value('pid');
        }
        $arr = [
            'sm_id' => $data['sm_id'],
            'uid' => $this->aid,
            'content' => $data['content'],
            'create_time' => date('Y-m-d H:i:s',time()),
            'status' => 1,
            'type' => 1,   // 运营商
            'name' => $info['leader'],
            'company' => $info['company'],
            'phone' => $info['phone'],
            'sm_status' => 0,
            'pro_id' => $pro_id,
            'city_id' => $city_id,
            'county_id' => $county_id,
        ];
        $ret = Db::table('sm_complaint')->strict(false)->insert($arr);
        if ($ret){
            $this->result('',1,'投诉成功');
        }else{
            $this->result('',0,'投诉失败');
        }
    }


    // 撤回 投诉  操作
    public function recall()
    {
        // 投诉信息的ID
        $id = input('post.id');
        $ret = Db::table('sm_complaint')
            ->where('id',$id)
            ->update(['status'=>2,'handle_time'=>time(),'sm_status'=>1]);
        if ($ret){
            $this->result('',1,'撤回投诉成功');
        }else{
            $this->result('',0,'撤回投诉失败');
        }
    }


    // 投诉列表
    public function complaLIist()
    {
        $page = input('post.page') ? : 1;
        $pageSize = 8;
        $count = Db::table('sm_complaint')
            ->where('uid',$this->aid)
            ->where('type',1)
            ->count();
        $rows = ceil($count / $pageSize);
        $list = Db::table('sm_complaint')
            ->where('uid',$this->aid)
            ->where('type',1)
            ->field('id,sm_id,content,create_time,status,sm_status')
            ->order('create_time DESC')
            ->page($page,$pageSize)
            ->select();
        foreach ($list as $key=>$value){
            $list[$key]['manager'] = Db::table('sm_user')
                ->where('id',$list[$key]['sm_id'])
                ->value('name');
            unset($list[$key]['sm_id']);
        }
        if($count > 0){
            $this->result(['list'=>$list,'rows'=>$rows],1,'获取成功');
        }else{
            $this->result('',0,'暂无数据');
        }
    }
}
