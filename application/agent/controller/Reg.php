<?php 
namespace app\agent\controller;
use app\base\controller\Base;
use think\Db;
use think\File;
// use think\validate;
/**
* 注册登录
*/
class Reg extends Base{

	 function initialize()
    {
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
	  * 注册操作
	  * @return json  注册成功或失败
	  */
	public function reg(){
		$data=input('post.');
		$validate=validate('Reg');
		if($validate->check($data)){
			if($this->sms->compare($data['phone'],$data['code'])){
				// 密码加密
				$data['pass']=get_encrypt($data['pass']);
				$res=Db::name("ca_agent")->strict(false)->insert($data);
				if($res){
					$this->result('',1,'注册成功');
				}else{
					$this->result('',0,'注册失败');
				}
			}else{
				$this->result('',0,'验证码错误或失效');
			}
		}else{
			$this->result('',0,$validate->getError());
		}
	}

	/**
	 * 注册发送手机验证码
	 * @return [type] [发送成功或失败]
	 */
	public function regCode()
	{
		$phone=input('post.phone');
		// 生成四位验证码
        $code=$this->apiVerify();
		$content="您的验证码是：【".$code."】。请不要把验证码泄露给其他人。";
		return $this->smsVerify($phone,$content,$code);
	}


	/**
	 * 省
	 * @return [type] [description]
	 */
	public function regPro()
	{
		return $this->province();
	}

	/**
	 * 市
	 * @return [type] [description]
	 */
	public function regCity()	
	{	
		// 省id
		$id=input('post.id');
		return $this->city($id);
	}


	/**
	 * 区县
	 * @return [type] [description]
	 */
	public function regCounty()
	{
		// 市区id
		$id = input('post.id');
		return $this->county($id);
	}




	/**
	 * 注册页面的图片上传
	 * @return [type] [description]图片路径
	 */
	public function regImg()
	{
		$origin = isset($_SERVER["HTTP_ORIGIN"]) ? $_SERVER["HTTP_ORIGIN"] : '*';
        // header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Headers:x-requested-with'); 
        header('Access-Control-Allow-Origin:'.$origin); 
        header('Access-Control-Allow-Credentials:true');
        header('Access-Control-Allow-Methods:GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers:Origin, No-Cache, X-Requested-With, If-Modified-Since, Pragma, Last-Modified, Cache-Control, Expires, Content-Type, X-E4M-With');   
		return $this->rupload('image','agent','https://cc.ctbls.com');
	}



	/**
	 * 单图片上传
	 * @param  图片字段
	 * @param  要保存的路径
	 * @return 图片保存后的路径
	 */
	 public function rupload($image,$path,$host){
	    // 本地测试地址，上线后更改
	    $host = $host ? $host : $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'];
	    // 获取表单上传文件
	    $file = request()->file($image);
	    // print_r($file);exit;
	    // 进行验证并进行上传
	    $info = $file->move( './uploads/'.$path);
	    // 上传成功后输出信息
	    if($info){
	      return  $host.'/uploads/'.$path.'/'.$info->getSaveName();
	    }else{
	      // 上传失败获取错误信息
	      return  $file->getError();
	    }
	}
	
	
}
