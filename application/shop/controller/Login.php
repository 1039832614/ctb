<?php 
namespace app\shop\controller;
use app\base\controller\Shop;
use Firebase\JWT\JWT;
use Msg\Sms;
use think\Db;

/**
* 登录
*/
class Login extends Shop
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
	 * 进行登录操作
	 */
	public function doin()
	{
		// 获取提交过来的数据
		$data = input('post.');
		// 实例化验证
		$validate = validate('Login');
		// 如果验证通过则进行登录操作
		if($validate->check($data)){
			// 查找用户是否存在
			$us = Db::table('cs_shop')
					->field('id,passwd,audit_status')
					->where('usname',$data['usname'])
					->find();
			if($us){
				if(compare_password($data['passwd'],$us['passwd'])){
					// 生成token作为验证使用
					$token = $this->token($us['id'],$data['usname']);
					$login_status = $this->loginStatus($us['id']);
					// 绑定运营商
					$this->setAgent($us['id']);
					// 登录成功返回数据
					$this->result(['token'=>$token,'sid'=>$us['id']],$login_status['code'],$login_status['msg']);
				}else{
					$this->result('',5,'用户名或密码错误');
				}
			}else{
				$this->result('',5,'用户不存在');
			}
		}else{
			$this->result('',5,$validate->getError());
		}
	}


    /**
     * 用户登录返回状态
     * @param  [type] $token [description]
     * @return array [type]        [description]
     */
	public function loginStatus($sid)
	{
		$status = Db::table('cs_shop')
					->where('id',$sid)
					->value('audit_status');
		$ration = Db::table('cs_ration')
					->where('sid',$sid)
					->column('stock');
		$stock = array_sum($ration);
		if($status == 99){

			return  ['code'=>0,'msg'=>'请支付系统使用费：2000元'];

		}else if($status == 0 || $status == 3){

			return  ['code'=>1,'msg'=>'请您完善信息'];

		}else if($status == 6 && $stock <= 0){

			return  ['code'=>3,'msg'=>'您已取消合作且做完剩余邦保养,可提现余额'];

		}else if($status == 4){

			return  ['code'=>3,'msg'=>'您已取消合作,可提现余额'];

		}else if($status == 2 || $status == 6){

			return  ['code'=>2,'msg'=>'登录成功'];

		}else if($status == 5){

			return  ['code'=>4,'msg'=>'您因连续三次未完成任务,店铺已关停,请联系运营商'];

		}else{

			return  ['code'=>2,'msg'=>'登录成功'];

		}
	}


	/**
	 * 找回密码
	 */
	public function forget()
	{
		// 获取提交过来的数据
		$data = input('post.');
		// 实例化验证
		$validate = validate('Forget');
		// 如果验证通过则进行登录操作
		if($validate->check($data)){
			// 检测手机验证码是否正确
			$check = $this->sms->compare($data['mobile'],$data['code']);
			if($check !== false){
				// 进行修改密码的操作
				$count = Db::table('cs_shop')
							->where('phone',$data['mobile'])
							->count();
				if($count > 0){
					$res = Db::table('cs_shop')
							->where('phone',$data['mobile'])
							->setField('passwd',get_encrypt($data['passwd']));

					if($res !== false){
	                    $this->result('',1,'修改成功');
	                }else{
	                    $this->result('',0,'修改失败');
	                }
				}else{
					$this->result('',0,'您的手机号没有注册过该系统，请核实');
				}
				
			}else{
				$this->result('',0,'手机验证码无效或已过期');
			}
		}else{
			$this->result('',0,$validate->getError());
		}
	}

	/**
	 * 生成图文验证码
	 */
	public function getCode()
	{
		$code = $this->apiVerify();
		$this->result(['imgCode'=>$code],1,'获取成功');
	}

	/**
	 * 发送短信验证码
	 */
	public function vcode()
	{
		$mobile = input('post.mobile');
		$code = $this->apiVerify();
		$content = "您的短信验证码是【{$code}】。您正在通过手机号重置登录密码，如非本人操作，请忽略该短信。";
		$res = $this->sms->send_code($mobile,$content,$code);
		$this->result('',1,$res);
	}


	/**
     * @param   用户id
     * @param  用户登录账户
     * @return string
     */
    private function token($sid,$usname){
        $key = create_key();   //
        $token = ['id'=>$sid,'login'=>$usname,'type'=>4];
        $JWT = JWT::encode($token,$key);
        JWT::$leeway = 600;
        return $JWT;
    }
    /**
     * 获取协议内容
     * @return [type] [description]
     */
    public function protocol()
    {

        $con = Db::table('am_protocol')
               ->where('type',2)
               ->value('content');
        $con = str_replace('img src=&quot;/data/imgs/','img src="https://doc.ctbls.com/data/imgs/',$con);
        $content = htmlspecialchars_decode($con);
        return $content;
    }
}