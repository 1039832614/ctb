<?php 
namespace app\merchant\controller;
use app\base\controller\Merchant;
use Firebase\JWT\JWT;
use Msg\Sms;
use think\Db;
/**
 * 登录
 */
class Login extends Merchant
{
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
	 * 登录操作
	 * @return [type] [description]
	 */
	public function doLogin()
	{
		$data = input('post.');
		$validate = validate('Login');
		if($validate->check($data)){
			$user = Db::table('cp_merchant')
					->where('login',$data['login'])
					->find();
			if($user){
				$check = compare_password($data['pwd'],$user['pwd']);
				if($check){
					$token = $this->token($user['id'],$data['login']);
					$login_status = $this->loginStatus($user['id']);
					$this->result(['token'=>$token,'id'=>$user['id']],$login_status['code'],$login_status['msg']);
				} else {
					$this->result('',5,'用户名或密码错误');
				}
			} else {
				$this->result('',5,'用户不存在');
			}
		} else {
			$this->result('',5,$validate->getError());
		}
	}

	/**
	 * 用户登录返回状态
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function loginStatus($id)
	{
		$status = Db::table('cp_merchant')
					->where('id',$id)
					->value('status');
		if($status == 99){

			return ['code'=>0,'msg'=>'请支付系统使用费：2000元'];

		} else if($status == 0){

			return ['code'=>1,'msg'=>'请您完善信息'];

		} else if($status == 2){

			return ['code'=>2,'msg'=>'登录成功'];

		} else if($status == 3){

			return ['code'=>3,'msg'=>'请选择地区以及供应项'];

		} else if($status == 4){

			return ['code'=>2,'msg'=>'登录成功'];

		} else if($status == 5){

			return ['code'=>4,'msg'=>'请修改您选择的地区以及供应项'];
		}
	}
	/**
	 * 找回密码
	 * @return [type] [description]
	 */
	public function forget()
	{
		$data = input('post.');
		$validate = validate('Forget');
		if($validate->check($data)){
			$check = $this->sms->compare($data['phone'],$data['code']);
			if($check !== false) {
				$count = Db::table('cp_merchant')
							->where('phone',$data['phone'])
							->count();
				if($count > 0) {
					$res = Db::table('cp_merchant')
							->where('phone',$data['phone'])
							->setField('pwd',get_encrypt($data['pwd']));
					if($res !== false){
						$this->result('',1,'修改成功');
					} else {
						$this->result('',0,'修改失败');
					}
				} else {
					$this->result('',0,'您的手机号没有注册过该系统，请核实');
				}
			} else {
				$this->result('',0,'手机验证码无效或已过期');
			}
		} else {
			$this->result('',0,$validate->getError());
		}
	}
	/**
	 * 生成token
	 * @param  [type] $id   [description]
	 * @param  [type] $name [description]
	 * @return [type]       [description]
	 */
	public function token($id,$name){
		$key = create_key();
		$token = ['id'=>$id,'login'=>$name];
		$JWT = JWT::encode($token,$key);
		JWT::$leeway = 600;
		return $JWT;
	}
	/**
	 * 发送短信验证码
	 * @return [type] [description]
	 */
	public function vcode()
	{
		$mobile = input('post.phone');
		$code = $this->apiVerify();
		$content = "您的短信验证码是【{$code}】。您正在通过手机号重置登录密码，如非本人操作，请忽略该短信。";
		$res = $this->sms->send_code($mobile,$content,$code);
		if($res == '提交成功') {
			$this->result('',1,'验证码发送中');
		} else {
			$this->result('',0,'今日短信验证码已发送过多。');
		}
	}
}