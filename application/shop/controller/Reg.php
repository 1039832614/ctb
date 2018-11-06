<?php 
namespace app\shop\controller;
use app\base\controller\Shop;
use think\Db;
use Msg\Sms;
use think\Validate;
/**
 * 维修厂注册
 */
class Reg extends Shop
{
	/**
	 * 进程初始化
	 */
	public function initialize()
	{
		$this->sms = new Sms();
		$origin = isset($_SERVER["HTTP_ORIGIN"]) ? $_SERVER["HTTP_ORIGIN"] : '*';
        // header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Headers:x-requested-with'); 
        header('Access-Control-Allow-Origin:'.$origin); 
        header('Access-Control-Allow-Credentials:true');
        header('Access-Control-Allow-Methods:GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers:Origin, No-Cache, X-Requested-With, If-Modified-Since, Pragma, Last-Modified, Cache-Control, Expires, Content-Type, X-E4M-With');    
	}

	/**
	 * 进行注册操作
	 */
	public function reg()
	{
		// 获取提交过来的数据
		$data=input('post.');
		// 实例化验证
		$validate = validate('Reg');
		// 如果验证通过则进行邦保养操作
		if($validate->check($data)){
			// 检测手机验证码是否正确
			$check = $this->sms->compare($data['phone'],$data['code']);
			if($check !== false){
				// 密码加密
				$data['passwd'] = get_encrypt($data['passwd']);
				// 进行入库操作
				$res=Db::table('cs_shop')->strict(false)->insertGetId($data);
				// 返回处理结果
				if($res){
					$this->result(['sid'=>$res],1,'注册成功');
				}else{
					$this->result('',0,'注册成功');
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
		$code = $this->apiVerify();
		$content = "您的验证码是：【{$code}】。请不要把验证码泄露给其他人。";
		$res = $this->sms->send_code($mobile,$content,$code);
		$this->result('',1,$res);
	}
	/**
	 * 注册第一步，输入账号，密码，确认密码，手机号，获取验证码
	 * 新版本维修厂使用
	 * @return [type] [description]
	 */
	public function regOne()
	{
		//获取前端提交过来的数据
		$data = input('post.');
		//实例化验证
		$validate = Validate('RegOne');
		if($validate->check($data)){
			//检测手机号和验证码是否相符
			$check = $this->sms->compare($data['phone'],$data['code']);
			if($check !== false) {
				//通过验证，进行密码加密
				$data['passwd'] = get_encrypt($data['passwd']);
				//进行入库操作
				$res = Db::table('cs_shop')
						->strict(false)
						->insertGetId($data);
				if($res !== false) {
					$this->result(['sid'=>$res],1,'本页面提交成功,请继续');
				} else {
					$this->result('',0,'提交失败，请检查');
				}
			}
		} else {
			$this->result('',0,$validate->getError());
		}
	}
	/**
	 * 注册第二步，输入公司名称，简称以及负责人
	 * 新版本维修厂使用
	 * @return [type] [description]
	 */
	public function regTwo()
	{	
		//获取前端提交过来的数据
		$data = input('post.');
		//实例化验证
		$validate = Validate('RegTwo');
		if($validate->check($data)){
			$sid = $data['sid'];
			unset($data['sid']);
			$res = Db::table('cs_shop')
					->where('id',$sid)
					->update($data);
			if($res) {
				$this->result('',1,'本页面提交成功，请继续');
			} else {
				$this->result('',0,'更新失败');
			}
		} else {
			$this->result('',0,$validate->getError());
		}
	}
	/**
	 * 注册第三步，开户名，提现卡号，开户行，开户分行
	 * 新版本维修厂使用
	 * @return [type] [description]
	 */
	public function regThird()
	{
		// 获取前端提交过来的数据
		$data = input('post.');
		//实例化验证
		$validate = Validate('RegThird');
		if($validate->check($data)){
			$res = Db::table('cs_shop_set')
					->strict(false)
					->insert($data);
			if($res) {
				$this->result('',1,'注册成功,请支付系统使用费');
			} else {
				$this->result('',0,'注册失败');
			}
		} else {
			$this->result('',0,$validate->getError());
		}
	}
	/**
	 * 获取银行列表
	 * 新版本维修厂使用
	 * @return [type] [description]
	 */
	public function getBankCode()
	{
		return $this->bankCode();
	}
	/**
	 * 获取验证码
	 * 新版本维修厂使用
	 * @return [type] [description]
	 */
	public function getCode()
	{
		//获取前端提交过来的数据
		$data = input('post.');
		$validate = Validate::make([
			'phone|手机号' => 'require|mobile'
		]);
		//进行实例化验证
		if($validate->check($data)){
			//生成验证码
			$code = $this->apiVerify();
			$content = "您的验证码是：【{$code}】。请不要把验证码泄露给其他人。";
			$res = $this->sms->send_code($data['phone'],$content,$code);
			if($res == "提交成功"){
				$this->result('',1,'发送成功');
			} else {
				$this->result('',0,'由于短信平台限制，您一天只能接受五次验证码');
			}
		} else {
			//验证失败
			$this->result('',0,$validate->getError());
		}
	}

}