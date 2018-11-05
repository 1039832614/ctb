<?php
namespace app\admin\controller;
use app\base\controller\Base;
use think\Db;
use Firebase\JWT\JWT;
use think\Request;
/**
* 总后台登录
*/
class Login extends Base
{
	
		/**
	 * 登录
	 * @return [type] [description]
	 */
	public function index()
	{
		$data = input('post.');
		$validate = validate('Login');
		
		if($validate->check($data)){
			$arr = Db::table('am_auth_user')->where('uname',$data['name'])->find();
			// 比较用户名是否存在
			if($arr['uname'] == $data['name']){

				// 比较输入的密码是否和数据库存的密码一致
				if(compare_password($data['pwd'],$arr['pwd'])){
					$request = new Request();
					$a = [
						'last_login_ip'=>$request->ip(),
						'last_login_time'=>time(),
					];
					$res = Db::table('am_auth_user')->where('uid',$arr['uid'])->update($a);
					$token = $this->token($arr['uid'],$data['name']);
					// 日志写入
                    $GLOBALS['err'] = $arr['uname'].'登录成功'; 
		            $this->estruct();
					$this->result($token,1,'登录成功');
				}else{
					$this->result('',0,'密码错误');
				}

			}else{

				$this->result('',0,'用户名错误或用户不存在');
			}
		}else{

			$this->result('',0,$validate->getError());

		}
	}


	/**
     * 忘记密码
     * @return json 修改成功或失败
     */
    public function forget(){
        $data=input('post.');
        $count = Db::table('am_auth_user')->where('phone',$data['code'])->count();
        if($count <= 0){
        	$msg=['status'=>0,'msg'=>'手机号不存在,请核实您的手机号！'];
        }
        $validate=validate('Forget');
        if($validate->check($data)){
            if($this->sms->compare($data['phone'],$data['code'])){
                $res=Db::table('am_auth_user')->where('phone',$data['phone'])->setField('pwd',get_encrypt($data['pass']));
                if($res!==false){
                	// 日志写入
                    $GLOBALS['err'] = $data['phone'].'修改密码成功'; 
		            $this->estruct();
                    $msg=['status'=>1,'msg'=>'修改成功'];
                }else{
                    $msg=['status'=>0,'msg'=>'修改失败'];
                }
            }else{
                $msg=['status'=>0,'msg'=>'验证码错误'];
            }
        }else{
            $msg=['status'=>0,'msg'=>$validate->getError()];
        }
        return $msg;
    }


	/**
	 * 忘记密码短信
	 * @return [type] [description]
	 */
	public function pwdApi()
	{	
		$phone = input('post.phone');
		$this->result('',1,$this->forCode($phone));
	}


	   /*
      *  转盘中奖导出表
      *  @param parea 市级id
      */ 
     
     public function Export()
     {
     	$area = input('get.area');
        // $area = 35;
    
     	if(empty($area)) $this->result('',0,'参数错误');
     	$xlsData = Db::table('u_winner a')
               ->join('u_prize b','a.aid = b.id')
               ->field('a.man,a.phone,a.address,a.details,a.time,b.name')
     	       ->where(['a.area'=>$area,'a.status'=>1,'a.dor'=>0])->select();
        if(!$xlsData) $this->result('',0,'此地区数据为空');
        $xlsName  = "中奖列表";
        $xlsCell  = array(
            array('man','中奖用户姓名'),
            array('phone','手机号'),
            array('address','地区'),
            array('details','详细地区'),
            array('time','中奖时间'),
            array('name','中奖物品'),
        );     
        $result = Db::table('u_winner a')
               ->join('u_prize b','a.aid = b.id')
               ->where(['a.area'=>$area,'a.status'=>1])->update(['a.dor'=>1]);
        exportExcel($xlsName,$xlsCell,$xlsData);
        exit;
     } 
	

	/**
     * @param   用户id
     * @param  用户登录账户
     * @return JWT签名
     */
    private function token($uid,$login){

        $key=create_key();   //
        $token=['id'=>$uid,'login'=>$login,'type'=>1];
        $JWT=JWT::encode($token,$key);
        JWT::$leeway =600;
        return $JWT;
    }


    /**
     * 导出会员地址列表
     * @return [type] [description]
     */
    public function order()
    {
        // $city = input('get.city');
        // if(!$city) $this->result('',0,'参数city缺少');
        $expTitle = '会员地址导出';
        $array = [
            ['man','收件人'],
            ['phone','联系电话'],
            ['address','联系地址'],
            ['details','详细地址'],
            ['time','购买会员时间'],
            
        ];
        $list = Db::table('u_winner')
                ->where(['status'=>0])
                ->where('member','>',0)
                ->select();
        Db::table('u_winner')->where(['status'=>0])->where('member','>',0)->setField('status',1);
        exportExcel($expTitle,$array,$list);    
    }




}