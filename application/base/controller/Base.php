<?php 
namespace app\base\controller;
use think\Controller;
use Firebase\JWT\JWT;
use think\Db;
use Msg\Sms;
// use PHPExcel\PHPExcel;
/**
* token登录  验证
*/
class Base extends Controller{
    
    function initialize()
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


        public function estruct(){
        // echo 1;exit;
        if(isset($GLOBALS['err'])){
            Db::table('co_log')->insert(['message'=>$GLOBALS['err']]);
        }

    } 
    
    /*
     * 获取管理员名称
     */
    
    public function ifName()
    {
        $id = $this->ifToken();
        return Db::table('am_auth_user')->where('uid',$id)->value('uname');
   
    }
    
    /*
     * 获取运营商名称
     */  
    
    public function yName($id)
    {
       return Db::table('ca_agent')->where('aid',$id)->value('company');
    }

    /**
     * 验证token
     * @return [type] [description]
     */
    // public function ifToken()
    // {
    //     $token=input('post.token');
    //     if(!$token || empty($token)){
    //         $this->result('',0,'token失效或未登录');
    //     }else{
    //         // 进行分割验证格式
    //         if (count(explode('.', $token)) != 3) {
    //             $this->result('',0,'token无效');
    //         }
    //         $id = $this->checkToken($token);
    //         if(!$id){
    //             $this->result('',0,'token失效或未登录');
    //         }else{
    //             return $id;
    //         }
    //     }
    // }



    // /**
    //  * 前台返回token时验证token
    //  * @param  token
    //  * @return 包含用户名、id的数组
    //  */
    // private function checkToken($token){
    //     $key=create_key();
    //     $sign=JWT::decode($token,$key,array('HS256'));
    //     $sign=json_encode($sign);
    //     $sign=json_decode($sign,true);
    //     return $sign['id'];
    // }

    
    /**
     * 验证token
     * @return [type] [description]
     */
    public function ifToken()
    {   
        // return 8;
        $token=input('post.token');
        if(!$token || empty($token)){
            $this->result('',403,'token失效或未登录');
        }else{
            // 进行分割验证格式
            if (count(explode('.', $token)) != 3) {
                $this->result('',403,'token无效');
            }
            $id = $this->checkToken($token);
            if(!$id){
                $this->result('',403,'token失效或未登录');
            }else{
                // print_r($id);die;
                return $id;
            }
        }
    }



    /**
     * 前台返回token时验证token
     * @param  token
     * @return 包含用户名、id的数组
     */
    private function checkToken($token){
        $key=create_key();
        $sign=JWT::decode($token,$key,array('HS256'));
        $sign=json_encode($sign);
        $sign=json_decode($sign,true);
        
        if(!$sign)  $this->result('',403,'token失效或未登录');
        if(!isset($sign['type']))  $this->result('',403,'token失效或未登录');

        // 验证登录
        $sign['id']  = $this->ifType($sign['id'],$sign['login'],$sign['type']);

        return $sign['id'];
    }


    /**
     * 前台返回token时验证token
     * @param  token
     * @return 包含用户名、id的数组
     */    
    
    public function ifType($id,$login,$type)
    { 
        // dump($id);
        // dump($login);
        // dump($type);die;
        // 总后台标识
         if($type===1){

            $id = Db::table('am_auth_user')->where(['uid'=>$id,'uname'=>$login])->value('uid');
            if(empty($id))  $this->result('',403,'token失效或未登录');
            // 返回 gid 或 id
            return  $this->AdminExtend($id);
            
         }  
        // 供应商标识                   
         if($type===2){

            $id = Db::table('cg_supply')->where(['gid'=>$id,'login'=>$login])->value('gid');
            if(empty($id)){
               $this->result('',403,'token失效或未登录');
            } 

            return $this->ifleave($id);
            return $id;      
         }  
        // 运营商标识
         if($type===3){
            $id = Db::table('ca_agent')->where(['aid'=>$id,'login'=>$login])->value('aid');
            if(empty($id))
            $this->result('',404,'token失效或未登录');
            return $id;            
         }
        // 维修厂标识
         if($type===4){
            $id = Db::table('cs_shop')->where(['id'=>$id,'usname'=>$login])->value('id');
            if(empty($id))
            $this->result('',403,'token失效或未登录');
            return $id;            
         }
         //供应商标识
        if($type === 5){
            $id = Db::table('cp_merchant')
                    ->where([
                        'id'=>$id,
                        'login'=>$login
                    ])
                    ->value('id');
            if(empty($id)){
                $this->result('',403,'token失效或未登录');
            }
            return $id;

        }
        //仲达养车标识
        if($type === 7){
            $id = Db::table('st_shop')
                    ->where(['id'=>$id,'usname'=>$login])
                    ->value('id');
            if(empty($id)){
                $this->result('',403,'token失效或未登录');
            }
            return $id;
        }

         $this->result('',403,'token失效或未登录');
    }

    /**
     * 判断状态
     * @return [type] [description]
     */   
    
    public function ifleave($gid)
    {     

        $status = DB::table('cg_supply')->where('gid',$gid)->value('status');
      
        if($status == 6)
        {
            if( request()->controller() != 'Wealth' )
            {
                $this->result('',0,'  您已取消合作,暂无法查看');
            }

        }
        return $gid;
    }


    /**
     * 管理员扩展
     * @param  $id 管理员id  
     */
    
    public function AdminExtend($id)
    { 

        // 总后台使用 市级代理 内 方法 参数 ggid
        $gid = input('post.ggid');
        if(!empty($gid))
        {   
            
            // $this->b($gid);
            return $gid;
        }

        // 总后台使用 市级代理 内 方法 参数 ggid
        $aid = input('post.aaid');
        if(!empty($aid))
        {
            $this->b($aid);
            return $aid;
        }

        return $id;
    }

    public function b($id)
    {
        if(!$this->authAction($id)){
            $this->result('',0,'暂无权限');
        }
    }

    /**
     * 权限跳转
     * @return [type] [description]
     */
    public function authAction($id)
    {
        $role = Db::table('am_role_user')->where('user_id',$id)->column('role_id');
        // $role = ['role'=>4];
        // // 查询多个用户组所拥有的权限并去重(获取用户组所拥有的的权限)
        $list = Db::table('am_auth_user au')
            ->join('am_role_user ru','au.uid = ru.user_id')
            ->join('am_auth_role ar','ru.role_id = ar.rid')
            ->join('am_rule_role rr','ar.rid = rr.role_id')
            ->join('am_auth_auth aa','rr.rule_id = aa.id')
            ->group('rule_id')
            ->where(['user_id'=>$id])
            ->column('auth_action');
            // print_r($list);exit;
        //获取当前控制器方法
        $action = request()->action();
        $controller = request()->controller();
       // print_r($controller.'/'.$action);exit;
       // print_r($role);exit;
        if(in_array(1,$role)) return $id;
        $suadmin=['Auth/ifuser','Auth/erauth'];
        if(in_array($controller.'/'.$action,$suadmin) || in_array($controller.'/'.$action,$list)){
            return $id;
               
        }else{
             $this->result('',1,'暂无权限！');
        }
    }



















    /**
     * 获取省级名称
     * @return 省级地区名称
     */
    public function province(){
        return Db::table('co_china_data')->field('id,name,code')->where('pid',1)->select();
    }
    /**
     * 获取市级
     * @return 市级地区名称
     */
    public function city($pid){
        return Db::table('co_china_data')->field('id,name,code')->where('pid',$pid)->select();
    }

    
    /**
     * @return 获取城市名称
     */
    public function county($cid){
        return Db::table('co_china_data')->field('id,name,code')->where('pid',$cid)->select();
    }
    /**
     * 生成验证码
     * @return json 验证码
     */
    public function verify(){
        $this->result($this->apiVerify(),1,'验证码获取成功');
    }
    /**
     * 生成API验证码
     */
    public function apiVerify()
    {
        return mt_rand(1000,9999);
    }
    /**
     * 注册银行列表
     * @return [type] [description]
     */
    public function bankCode(){
        return Db::table('co_bank_code')->select();
    }


    /**
     * 手机发送验证码
     * @param  [type] $phone   [手机号]
     * @param  [type] $content [短信发送内容]
     * @return [type]          [发送成功或失败]
     */
    public function smsVerify($phone,$content,$code = '')
    {
        return $this->sms->send_code($phone,$content,$code);
    }


     /**
     * 修改密码发送手机验证码
     * @return [type] [发送成功或失败]
     */
    public function forCode($phone)
    {
        // 生成四位验证码
        $code=$this->apiVerify();
        $content="您的短信验证码是：【".$code."】。您正在通过手机号重置登录密码，如非本人操作，请忽略该短信。";
       return  $this->smsVerify($phone,$content,$code);
    }






    /**
     * 获取每组油的升数
     * @return 数组
     */
    public function bangCate()
    {   $where=[['pid','>','0'],['def_num','>',0]];
        return Db::table('co_bang_cate')->where($where)->field('id,def_num')->select();
    }





    /**
     * 修改状态
     * @param  [type] $table   要修改的表
     * @param  [type] $sid     要修改的id
     * @param  [type] $status  要修改的状态值
     * @return [type]         [description]
     */
    public function status($table,$id,$status)
    {
        $res=Db::table($table)->where('id',$id)->setField('audit_status',$status);
        if($res!==false){
            return true;
        }
    }

    /**
 * 单图片上传
 * @param  图片字段
 * @param  要保存的路径
 * @return 图片保存后的路径
 */
 function upload($image,$path,$host){
    // 本地测试地址，上线后更改
    $host = $host ? $host : $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'];
    // 获取表单上传文件
    $file = request()->file($image);
    // 进行验证并进行上传
    $info = $file->validate(['size'=>3145728,'ext'=>'jpg,png,jpeg'])->move( './uploads/'.$path);
    // 上传成功后输出信息
    if($info){
      return  $host.'/uploads/'.$path.'/'.$info->getSaveName();
    }else{
      // 上传失败获取错误信息
      return  $file->getError();
    }
}

/*导出exc方法*/
public function exportExcel($expTitle,$expCellName,$expTableData){
    $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);
    $fileName = $xlsTitle.date('_YmdHis');
    $cellNum = count($expCellName);
    $dataNum = count($expTableData);
    require '../extend/PHPExcel/PHPExcel.php';
    $objPHPExcel = new \PHPExcel();
    // $objPHPExcel = new PHPExcel();
    $cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
    for($i=0;$i<$cellNum;$i++){
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'1', $expCellName[$i][1]);
    }
    for($i=0;$i<$dataNum;$i++){
        for($j=0;$j<$cellNum;$j++){
            $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j].($i+2), $expTableData[$i][$expCellName[$j][0]]);
        }
    }
    header('pragma:public');
    header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
    header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$xlsTitle.'.xls"');
    header("Content-Disposition:attachment;filename=$fileName.xls");
    $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    $objWriter->save('php://output');

    $GLOBALS['err'] = $this->ifName().'导出了中奖信息表';
    $this->estruct();
    exit;
}

    
/**
 * 删除图片
 * @param  $src 图片路径
 */
 
 public function imgdel($src)
 {  
    if(empty($src)){
         return true;
    }
    $src = '../'.substr($src,strpos($src, 'public'));
    if(empty(substr($src,strpos($src, 'public')))) return true;
    if(file_exists($src)){
        unlink($src);
    }
    
    // 日志写入
    $GLOBALS['err'] = $this->ifName().'删除了'.$src;
    $this->estruct();
    return true;
 }
    
}