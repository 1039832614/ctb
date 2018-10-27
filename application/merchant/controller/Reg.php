<?php 
namespace app\merchant\controller;
use app\base\controller\Merchant;
use think\Db;
use think\File;
use Pay\Epay;
/**
 * 供应商注册登录
 */
class Reg extends Merchant{

	/**
	 * 初始化方法
	 * @return [type] [description]
	 */
	public function initialize(){
		parent::initialize();
        $origin = isset($_SERVER["HTTP_ORIGIN"]) ? $_SERVER["HTTP_ORIGIN"] : '*';
        // header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Headers:x-requested-with'); 
        header('Access-Control-Allow-Origin:'.$origin); 
        header('Access-Control-Allow-Credentials:true');
        header('Access-Control-Allow-Methods:GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers:Origin, No-Cache, X-Requested-With, If-Modified-Since, Pragma, Last-Modified, Cache-Control, Expires, Content-Type, X-E4M-With');    
	}

	/**
	 * 注册登录
	 * @return [type] [description]
	 */
	public function reg(){
		$data = input('post.');
		$validate = validate('Reg');
		if($validate->check($data)){
			//如果验证成功,则继续验证手机号和验证码
			$check = $this->sms->compare($data['phone'],$data['code']);
			if($check !== false) {
				$data['pwd'] = get_encrypt($data['pwd']);
				//入库
				$res = Db::table('cp_merchant')
						->strict(false)
						->insertGetId($data);
				if($res) {
					$this->result(['sid'=>$res],1,'注册成功');
				} else {
					$this->result('',0,'注册失败');
				}
			} else {
				$this->result('',0,'手机验证码无效或已过期');
			}
		} else {
			//验证失败则返回失败信息
			$this->result('',0,$validate->getError());
		}
	}
	/**
	 * 发送短信验证码
	 * @return [type] [description]
	 */
	public function getCode(){
		$mobile = input('post.phone');
		$code = $this->apiVerify();
		$content="您的验证码是：【{$code}】。请不要把验证码泄露给其他人。";
		$res = $this->sms->send_code($mobile,$content,$code);
		$this->result('',1,'验证码发送中');
	}
	/**
	 * 获取所有的省
	 * @return [type] [description]
	 */
	public function getPro()
	{
		return $this->province();
	}
	/**
	 * 获取省下面的市
	 * @return [type] [description]
	 */
	public function getCity()
	{
		$id = input('get.id');
		return $this->city($id);
	}
	/**
	 * 获取区县
	 * @return [type] [description]
	 */
	public function getCounty()
	{
		$id = input('get.id');
		return $this->county($id);
	}

	 public function inex(){
 	$epay = new Epay();
 	
 	return $epay->banklog('6021536835733');
 	}
}