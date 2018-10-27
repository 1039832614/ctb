<?php 
namespace app\st\controller;
use app\base\controller\St;
use Firebase\JWT\JWT;
use Msg\Sms;
use think\Db;

/**
 * 登录
 */
class Login extends St
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
	 * 进行登录操作
	 */
	public function doin()
	{
		$data = input('post.');
		//实例化验证
		$validate = validate('Login');
		//进行验证
		if($validate->check($data)){
			//查找用户是否存在
			$us = Db::table('st_shop')
					->field('id,passwd')
					->where('usname',$data['usname'])
					->find();
			if($us){
				if(compare_password($data['passwd'],$us['passwd'])){
					//生成token作为验证使用
					$token = $this->token($us['id'],$data['usname']);
//					 $login_status = $this->loginStatus($us['id']);
					//登录成功后返回数据
					$this->result(['token'=>$token,'sid'=>$us['id']],1,'登录成功');
				} else {
					$this->result('',3,'用户名或密码错误');
				}
			} else {
				$this->result('',4,'用户名不存在');
			}
		} else {
			$this->result('',5,$validate->getError());
		}
	}
	/**
	 * 找回密码
	 */
	public function forget()
	{
		$data = input('post.');
		$validate = validate('Forget');
		if($validate->check($data)) {
			//检测手机验证码是否正确
			$check = $this->sms->compare($data['mobile'],$data['code']);
			if($check !== false){
				//进行修改密码的操作
				$count = Db::table('st_shop')
							->where('phone',$data['mobile'])
							->count();
				if($count > 0) {
					$res = Db::table('st_shop')
							->where('phone',$data['mobile'])
							->setField('passwd',get_encrypt($data['passwd']));
					if($res !== false) {
						$this->result('',1,'修改成功');
					} else {
						$this->result('',0,'修改失败');
					}
				} else {
					$this->result('',0,'您的手机号没有注册过该系统，请核实');
				}
			} else {
				$this->result('',0,'手机验证码无效或过期');
			}
		} else {
			$this->result('',0,$validate->getError());
		}
	}
	/**
     * @param   用户id
     * @param  用户登录账户
     * @return JWT签名
     */
    private function token($sid,$usname){
        $key=create_key();   //
        $token=['id'=>$sid,'login'=>$usname,'type'=>7];
        $JWT=JWT::encode($token,$key);
        JWT::$leeway = 600;
        return $JWT;
    }
}