<?php 
namespace app\supply\controller;
use think\Controller;
use Firebase\JWT\JWT;
use think\Db;
use Msg\Sms;
// use PHPExcel\PHPExcel;
/**
* token登录  验证
*/
class CoController extends Controller{
	

     public function Export()
     {
     	$area = input('post.area');
        $area = 35;
     	if(empty($area)) $this->result('',0,'参数错误');
     	$xlsData = Db::table('u_winner a')
               ->join('u_prize b','a.aid = b.id')
               ->field('a.man,a.phone,a.address,a.details,a.time,b.name')
     	       ->where(['a.area'=>$area,'a.status'=>1])->select();
        if(!$xlsData) $this->result('',0,'此地区数据为空');
        $xlsName  = "提现申请";
        $xlsCell  = array(
            array('man','中奖用户姓名'),
            array('phone','手机号'),
            array('address','地区'),
            array('details','详细地区'),
            array('time','中奖时间'),
            array('name','中奖物品'),
        );  
                                                                    
        $this->exportExcel($xlsName,$xlsCell,$xlsData);
     } 










}