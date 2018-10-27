<?php 
namespace app\supply\controller;
use app\base\controller\Base;
use Firebase\JWT\JWT;
use think\Db;
use AES\AES;
/**
* 维修厂公共类
*/
class Login extends Base
{ 

   /**
     * 登录
     * @return json
     */
    public function login(){
        $data=input('post.');
        $validate=validate('Login');
        // $data = []
        if($validate->check($data)){
            $arr=Db::table('cg_supply')->where('login',$data['login'])->find();
            if($arr){
                if(compare_password($data['pass'],$arr['pass'])){
                    $token=$this->token($arr['gid'],$data['login']);
          
                    $loginSta = $this->loginSta($arr['gid'],$token);
                    


                    // ['token'=>$token],$loginSta['code'],$loginSta['msg']
                    $this->result(['token'=>$token],1,'成功');
                }else{
                    $this->result('',6,'密码错误');
                }
            }else{
                $this->result('',0,'用户不存在');
            }
        }else{
             $this->result('',0,$validate->getError());
        }
    }
    
    public function ddd()
    {   
        
        $res = Db::table('u_card')
                 ->alias('c')
                 ->join(['u_user'=>'u'],'c.uid = u.id')
                 ->field('c.id,card_price,u.name,u.phone')
                 ->where('u.id',1)
                 ->find();

        print_r( Db::table('u_card')->getLastsql() );

    }
    
    /**
     * 用户登录返回状态
     * @param  [type] $token [description]
     * @param  [type] $sid   [description]
     * @return [type]        [description]
     */
    public function loginSta($gid,$token)
    {
        // 判断用户有没有上传营业执照
        $status = Db::table('cg_supply')->where('gid',$gid)->value('status');
        // 查看运营商是否设置地区
        $count = Db::table('cg_increase')->where('gid',$gid)->count();
        if($status == 0){
           return true;
        }else if($status == 3){
            return true;
            $this->result('',2,'您还未上传营业执照，请您到个人中心上传.');
        }else if($status == 1 ){
            return true;
            $this->result('',3,'您已上传营业执照,请等待总后台审核。');
        }else if($status == 6){
                   
            $this->result(['token'=>$token],4,'您已取消合作,可提现余额');
        }else if($count < 0){
            return true;
            $this->result('',5,'您还未设置地区，请您到个人中心->供应地区->设置您的地区。');
        }else{
            return true;
            // $this->result('',5,'登录成功');
        }
    }

    


    /**
     * 忘记密码
     * @return json 修改成功或失败
     */
    public function forget(){
        $data=input('post.');

        $validate=validate('Forget');
        if($validate->check($data)){
          
            if($this->sms->compare($data['phone'],$data['code'])){
                $res=Db::table('cg_supply')->where('phone',$data['phone'])->setField('pass',get_encrypt($data['pass']));
                if($res!==false){
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
     * @param   用户id
     * @param  用户登录账户
     * @return JWT签名
     */
    private function token($uid,$login){
        $key=create_key();   //
        $token=['id'=>$uid,'login'=>$login,'type'=>2];
        $JWT=JWT::encode($token,$key);
        JWT::$leeway =10;
        return $JWT;
    }



    /**
     * 登录的修改密码短信验证码
     * @var string
     */
    public function loginFor()
    {
        $phone=input('post.phone');
        return $this->forCode($phone);
    }
    
    /*
     *  系统使用费 
     */
    

}