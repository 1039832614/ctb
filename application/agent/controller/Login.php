<?php 
namespace app\agent\controller;
use app\base\controller\Base;
use Firebase\JWT\JWT;
use think\Db;


/**
* 登录
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
        if($validate->check($data)){
            $arr=Db::table('ca_agent')->where('login',$data['login'])->find();
            if($arr){
                if(compare_password($data['pass'],$arr['pass'])){
                    $token=$this->token($arr['aid'],$data['login']);
                    $this->binding($arr['aid']);
                    $loginSta = $this->loginSta($arr['aid']);
                    $this->result(['token'=>$token,'aid'=>$arr['aid']],$loginSta['code'],$loginSta['msg']);
                }else{
                    $this->result('',0,'登录失败');
                }
            }else{
                $this->result('',0,'用户不存在');
            }
        }else{
             $this->result('',0,$validate->getError());
        }
    }
    
    
    /**
     * 用户登录返回状态
     * @param  [type] $token [description]
     * @param  [type] $sid   [description]
     * @return [type]        [description]
     */
    public function loginSta($aid)
    {
        // 判断用户有没有上传营业执照
        $status = Db::table('ca_agent')->where('aid',$aid)->value('status');
        // 查看运营商是否设置地区
        $count = Db::table('ca_increase')->where('aid',$aid)->count();
        if($status == 0){
            return  ['code'=>1,'msg'=>'您还未支付系统使用费,请扫码支付'];
        }else if($status == 3){
            return  ['code'=>2,'msg'=>'您还未设置地区，请您到个人中心->供应地区->设置您的地区。'];
        }else if($status == 1 ){
            return  ['code'=>3,'msg'=>'您已上传营业执照,请等待总后台审核。'];
        }else if($status == 6){
            return  ['code'=>4,'msg'=>'您已取消合作,可提现余额'];
        }else if($count <= 0 && $status !== 6){
            return  ['code'=>5,'msg'=>'您还未设置地区，请您到个人中心->供应地区->设置您的地区。'];
        }else if($status == 7){
             return  ['code'=>7,'msg'=>'您的系统已被关停，如有疑问或重新恢复系统请联系运营，电话：15931155969'];//xjm 10.16 11:30添加该条
        }else{
             return  ['code'=>6,'msg'=>'登录成功'];
        }
    }



    /**
     * 忘记密码
     * @return json 修改成功或失败
     */
    public function forget(){
        $data=input('post.');
        $validate=validate('Forget');
        if($validate->check($data))
        {
            //检测手机验证码是否正确
            $check = $this->sms->compare($data['phone'],$data['code']);
            if($check !== false)
            { 
                // 2018/8/6 徐佳孟修改 
                //查看用户输入的手机号是否注册过系统
                $count = Db::table('ca_agent')
                            ->where('phone',$data['phone'])
                            ->count();
                if($count > 0) {
                    $res=Db::table('ca_agent')->where('phone',$data['phone'])->setField('pass',get_encrypt($data['pass']));
                    if($res!==false){
                        $msg=['status'=>1,'msg'=>'修改成功'];
                    }else{
                        $msg=['status'=>0,'msg'=>'修改失败'];
                    }
                } else {
                    $msg = ['status'=>0,'msg'=>'您的手机号尚未注册系统，请核实'];
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
        $token=['id'=>$uid,'login'=>$login,'type'=>3];
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
     * 系统使用费 提示语
     */
    public function hint()
    {
        $number = input('post.number');
        $money = $number * 5000;
        return '区域增加，请支付系统使用费:'.$money.'元';
    }
    /******************************************/
    // 2018.09.18 15:02 张乐召 添加
    // 绑定市级代理
    public function binding($aid)
    {
      // 查找该运营商的供应的地区的id
        $area = Db::table('ca_increase')
            ->where('aid',$aid)
            ->field('area')
            ->select();
        if(!empty($area)) {
            // 查找运营商供应地区的市级ID
            foreach ($area as $key=>$value){
                $cid[] = Db::table('co_china_data')
                    ->where('id','in',$area[$key]['area'])
                    ->value('pid');
            }
            $cid = array_unique($cid);
            // 查找是否有该地区的供应商
            $g_area = Db::table('cg_area')->select();
            foreach ($g_area as $key=>$value){
                if ($cid['0'] == $g_area[$key]['area']){
                    $gid = $g_area[$key]['gid'];
                }
            }
            // 如存在 供应商 则 更新该运营商的供应商
            if (!empty($gid)){
                Db::table('ca_agent')->where('aid',$aid)->update(['gid'=>$gid]);
            }
        }
    }
    /**
     * 获取协议内容
     * @return [type] [description]
     */
    public function protocol()
    {

        $con = Db::table('am_protocol')
            ->where('type',1)
            ->value('content');
        $con = str_replace('img src=&quot;/data/imgs/','img src="https://doc.ctbls.com/data/imgs/',$con);
        $content = htmlspecialchars_decode($con);
        return $content;
    }
}