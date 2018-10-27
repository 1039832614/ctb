<?php 
namespace app\supply\controller;
use app\base\controller\supply;
use think\File;
use think\Db;

/**
* 个人中心
*/
class Center extends supply{
	
	function initialize(){
		parent::initialize();
		$this->agent='cg_supply';
		$this->area='cg_area';
		$this->agent_set='cg_supply_set';
		$this->china='co_china_data';
		
	}
    
   
    public function notAlert()
    {
    	$id = input('post.id');
    	if(empty($id)) $this->result('',0,'参数错误');
        $res = DB::table('cg_increase')->where(['id'=>$id,'gid'=>$this->gid])->update(['credit_status'=>1]);
        if(!$res) $this->result('',0,'关闭失败');
        $this->result('',1,'关闭成功');
    }
       // 是否弹框
   
   public function isAlert()
   { 

     $data = Db::table('cg_increase')->where(['gid'=>$this->gid,'credit_status'=>0,'audit_status'=>1])->order('id DESC')->limit(1)->field('id,name,iphone,credit price')->find();
     if(empty($data)) $this->result('',0,'');
     $this->result(['lst'=>$data],1,'');
     // return ['lst'=>$data,'code'];
   }

	/**
	 * 未审核列表
	 * @return [type] [description]
	 */
	public function notList()
	{
		$page = input('post.page')? : 1;
		$this->ration($page,0);
	}

	/**
	 * 配给成功列表
	 * @return [type] [description]
	 */
	public function ratAdopt()
	{
		$page = input('post.page')? : 1;
		$this->ration($page,1);
	}



	/**
	 * 配给驳回列表
	 * @return [type] [description]
	 */
	public function rejList()
	{
		$page = input('post.page')? : 1;
		$this->ration($page,3);
	}

	/**
	 * 点击区域个数显示区域
	 * @return [type] [description]
	 */
	public function cenRegion()
	{
		// 获取本次地区申请id
		$id = input('post.id');
		if(empty($id)) $this->result('',0,'参数错误');
		$county = Db::table('cg_increase')->where('id',$id)->value('area');
		// print_r($county);die;
		// 获取省市县名称id
        $list = $this->county($county);          
        if($list){
        	$this->result($list,1,'获取地区列表成功');
        }else{
        	$this->result('',0,'暂未设置地区');
        }
	}

	/**
	 * 驳回列表修改页面信息
	 * @return [type] [description]
	 */
	public function updIndex()
	{	
		// 获取修改地区订单id
		$id = input('post.id');
		$data = Db::table('cg_increase')->where('id',$id)->field('id,gid,voucher,price,area')->find();
		if($data){
			//根据所选县id得出所选县名称
			$list = $this->county($data['area']);
			if($list){
				$this->result(['data'=>$data,'list'=>$list],1,'获取成功');
			}else{
				$this->result('',0,'获取数据失败');
			}
		}else{
			$this->result('',0,'暂无数据');
		}
	} 

	// 获取地区
	public function xarea()
	{
		$id = input('post.id');
		if(empty($id)) $this->result('',0,'参数为空');
		$data = DB::table('cg_increase a')
		        // ->join('ca_agent b','a.aid = b.aid')
		        ->where(['a.gid'=>$this->gid,'a.id'=>$id])
                ->field('a.id,a.voucher,a.price,a.area')
		        ->find();
		
        if(empty($data)) $this->result('',0,'null');
  
        
         
         // $data['area'] = $this->ares($data['area']);
       // kai  
        
         $c = explode(',',$data['area']);
         // 获取省名称
         
         // dump($c);die;
         $sheng = Db::table('co_china_data')->whereIn('id',$c)->value('pid');
         $sheng = Db::table('co_china_data')->whereIn('id',$sheng)->field('id,name')->find();
         $cc = Db::table('co_china_data')->whereIn('id',$c)->column('id,name');
// dump($sheng);die;
         $arr = [];
         foreach($cc as $k=>$v)
         {   
         	 $arr[$k]['id'] = $k;
         	 $arr[$k]['name'] = $sheng['name'].' '.$v;
         	 // $cc[$k]['name'] = $sheng['name'].' '.$v;
         }
        

        $data['sheng'] = $sheng;
        $data['shi'] = array_values($arr);


        if($data){
        	$this->result($data,1,'true');
        }else{
        	$this->result('',0,'false');
        }
        // dump($arr);

	}

// 获取地区
	public function ares($area)
	{

       // $qu = Db::table('co_china_data')->whereIn('id',$area)->column('pid,name');
       // $xian = Db::table('co_china_data')->whereIn('id',array_keys($qu))->column('pid,name');
       // $shi = Db::table('co_china_data')->whereIn('id',array_keys($xian))->column('name');
       
        
       $pid = Db::table('co_china_data')->where('id',$area)->value('pid');
       // echo $pid;die;
       $shi = DB::table('co_china_data')->where('id',$pid)->value('id');

        
       return ['sheng'=>$area,'shi'=>$shi];

       // return array(['sheng'=>array_values($shi),'shi'=>array_values($xian),'qu'=>array_values($qu)]);
       // print_r(array_values($xian));
	} 





	/**
	 * 配给列表操作
	 * @param  [type] $status [description]
	 * @return [type]         [description]
	 */
	private function ration($page,$status)
	{	
		$pageSize = 10;
		$count = Db::table('cg_increase')->where(['audit_status'=>$status,'gid'=>$this->gid])->count();
		if(empty($count)) $this->result('',0,'数据为空');
		$rows = ceil($count / $pageSize);
		$list = Db::table('cg_increase a')
		        // ->join('cg_supply b','a.gid = b.aid')
				->where(['a.audit_status'=>$status,'a.gid'=>$this->gid])
				->order('a.id desc')
				->field('a.id,a.regions,a.price,a.create_time,a.audit_time,a.audit_status,a.reason')
				->page($page,$pageSize)
				->select();
		foreach ($list as $key => $value) {
			 if(!empty($value['audit_time']))
			 {

			 	$list[$key]['audit_time'] = Date("Y-m-d H:i:s",$value['audit_time']);
			 }
			 
		}
		// echo Db::table('cg_increase')->getLastSql();exit;
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}


	/**
	 * 个人中心首页
	 * @return 运营商信息，运营商id
	 */
	public function index(){
			// 页面数据

            $list=Db::table('cg_supply a')
					->where('gid',$this->gid)
					->field('status,gid,login,company,account,leader,province,city,county,address,phone,open_shop,license,usecost,agent_nums shop_nums,login')
					->find();
			if($list){
				$this->result($list,1,'获取页面数据成功');

			}else{
				$this->result('',0,'获取页面数据失败');
			}
		
	}

	
	/**
	 * 设置运营商供应地区及上传支付凭证
	 * @return [type]
	 */
	public function setArea(){
         
         $data = Db::table('cg_increase')->where(['gid'=>$this->gid,'audit_status'=>0])->find();
         if(!empty($data)) $this->result('',0,'您有未被审核的申请，请等待！');
		// // 选择的区县、转账金额、转账凭证
		// // 
		$data = Db::table('cg_area')->select();

	
		$data = input('post.');
        $data['area'] = $data['county'];
		if($data){
			
			$is = Db::table('cg_area')->whereIn('area',$data['area'])->select();
            if(!empty($is)) $this->result('',0,'该地区已选择');
			if(is_array($data['area']))
			{   
				// 入地区表
				$arr = null;
		        foreach($data['area'] as $k=>$v)
		        {    

                   $arr[$k]['gid'] = $this->gid;
                   $arr[$k]['area'] = $v;
		        } 		

		        DB::table('cg_area')->insertAll($arr);
                
				$data['area'] = implode(',',$data['area']);

			}
            
			$data['regions'] = $data['price']/105000;
			$data['gid'] = $this->gid;
			//填写总金额，上传支付凭证
			$res = Db::table('cg_increase')->strict(false)->insert($data);
		
			if($res){
	
				$this->result('',1,'设置成功,请等待总后台审核');
			}else{
			
				$this->result('',0,'设置失败');
			}

		}else{
		
			$this->result('',0,'地区不能为空');
		}

	}

	/**
	 *  地区修改
	 */
	
	public function updRegion()
	{
        $data = input('post.');

        if(!$data['id']) $this->result('',0,'参数错误');
        // 判断是否是已经选择地区
        $is = Db::table('cg_area')->whereIn('area',$data['county'])->select();
        if(!empty($is)) $this->result('',0,'该地区已选择');    
        if(empty($data['county'])) $this->result('',0,'地区不能为空');    
        if(is_array($data['county']))
        {
			// 入地区表
			$arr = null;
	        foreach($data['county'] as $k=>$v)
	        {    

               $arr[$k]['gid'] = $this->gid;
               $arr[$k]['area'] = $v;
	        } 		

	        DB::table('cg_area')->insertAll($arr);
            
			$data['area'] = implode(',',$data['county']);        	
        }
        // 计算地区数量
        $data['regions'] = $data['price']/105000;
        // 计算时间
        $data['create_time'] = Date('Y-m-d H:i:s',time());
        // 修改状态为 未审核  审核状态 0未审核 1已审核 3驳回
        $data['audit_status'] = 0;
        // 判断是否非法传入 gid
        if(isset($data['gid'])) unset($data['gid']);
        // 进行修改操作
        $res = Db::table('cg_increase')->strict(false)->update($data);
        
        if(!$res)  $this->result('',0,'修改地区失败');
        $this->result('',1,'修改地区成功！');
        

	}
 
	
	/**
     * 获取县级城市名称
     * @return  未选中的城市名称及已选中的城市名称
     */
    public function selCounty(){
        $city=input('post.id');

        $county=$this->city($city); //未被选中城市

        $selCounty=Db::table('cg_area')->select();//已被选中的城市
        if(!empty($county)){
        	$data = ['county'=>$county,'selCounty'=>$selCounty];
        	$this->result($data,1,'获取列表成功');
        }else{
        	$this->result($data,0,'获取列表失败');
        }

    }

    // 上传图片
    public function im_files()
    {

    	$url = upload('image','supply','https://ceshi.ctbls.com');
        $this->result($url,1,'获取成功');
    }

	
	/**
	 * 上传营业执照
	 * @return 成功或失败
	 */
	public function license(){

		$license=input('post.license');
                      
		$res=Db::table($this->agent)->where('gid',$this->gid)->update(['license'=>$license,'status'=>1]);
		if($res){
			$this->result($license,1,'上传营业执照成功');
		}else{
			$this->result('',0,'上传营业执照失败');
		}
	}




	/**
	 * 上传系统使用费
	 * @return 成功或失败
	 */
	public function usecost(){

		$usecost=input('post.usecost');

		$res=Db::table($this->agent)->where('gid',$this->gid)->update(['usecost'=>$usecost,'status'=>1]);
		if($res){
			$this->result('',1,'上传支付凭证成功');
		}else{
			$this->result('',0,'上传支付凭证失败');
		}
	}



	// /**
	//  * 查看运营商所供应地区
	//  * @return 返回地区名称
	//  */
	// public function area(){

	// 	// 通过区县id获得市级id
	// 	$county=Db::table($this->area)->where('gid',$this->gid)->select();

	// 	$county=array_str($county,'area');

	// 	$city=$this->areaList($county);
	// 	$province=$this->areaList($city);

	// 	$list=Db::table($this->china)->whereIn('id',$province.','.$city.','.$county)->select();
	// 	if($list){

	// 		$list=get_child($list,$list[0]['pid']);
	// 		$this->result($list,1,'获取列表成功');

	// 	}else{	

	// 		$this->result('',0,'暂未设置供应地区');
	// 	}
	// }
     
 	/**
	 * 查看运营商所供应地区
	 * @return 返回地区名称
	 */
	
	// public function area()
	// {

	//      $data  = Db::table('cg_area')->where('gid',$this->gid)->column('area');
 //         if(empty($data)) $this->result('',0,'暂未设置供应地区');
 //         // dump($data);die;
	//      $sheng = Db::table('co_china_data')->whereIn('id',$data)->field('name,pid')->select();
	//      if(empty($sheng[0]['pid'])) $this->result('',0,'数据错误501');
	//      $ids = [];
	//      foreach ($sheng as $key => $value) {
	//      	$ids['pid'][] = $value['pid'];
	//      }
	       
 //         $shi   = DB::table('co_china_data')->whereIn('id',$ids['pid'])->field('name,id')->select();
 //         if(empty($shi[0]['id'])) $this->result('',0,'数据错误502');
 //         // dump($ids);
 //         // dump($sheng);
 //         // dump($shi);
 //         $arr = [];
         
 //         foreach ($shi as $key => $value) {
         	 
 //         	 foreach ($sheng as $k => $v) {
         	 	
 //                 if($value['id']==$v['pid']){

 //                 	 $shi[$key]['son'][] = $v;
 //                 }

 //         	 }
 //         }

 //         if(empty($shi)) $this->result('',0,'数据错误503');
 //         $this->result($shi,1,'获取成功');
         
	// }  

 	/**
	 * 查看运营商所供应地区
	 * @return 返回地区名称
	 */
	
	public function area()
	{    
		// 获取市级 id
		$ids = DB::table('cg_increase')->where(['gid'=>$this->gid,'audit_status'=>1])->column('area');

		if(empty($ids)) $this->result('',0,'暂无供应地区');

		// 取出value合并为一个索引
		$arr = null;
		$i = -1;
		$arrs = null;

		foreach ($ids as $key => $value) {
			 
			  $arr[] = explode( ',' , $value); 
              if(isset($arr[$i])){
               	
                 if(isset($arrs[$i-1])){
                    $arrs[$i-1] = array_merge($arrs[$i-1],$arr[$i+1]);     
                 }else{
                 	$arrs[$i] = array_merge($arr[$i],$arr[$i+1]);
                 }
                 
              }else{
              	$arrs[0] = $arr[0];
              }

              $i++;

		}
// die;
		
         $arrs = $arrs[0];
         // 获取市级名称 省级id
	     $sheng = Db::table('co_china_data')->whereIn('id',$arrs)->field('name,pid')->select();
	     if(empty($sheng[0]['pid'])) $this->result('',0,'数据错误501');
                                            
	     $ids = [];
	     foreach ($sheng as $key => $value) {
	     	$ids['pid'][] = $value['pid'];
	     }
	       
         $shi   = DB::table('co_china_data')->whereIn('id',$ids['pid'])->field('name,id')->select();
         if(empty($shi[0]['id'])) $this->result('',0,'数据错误502');
         
         $arr = [];

         foreach ($shi as $key => $value) {
         	 
         	 foreach ($sheng as $k => $v) {
         	 	
                 if($value['id']==$v['pid']){

                 	 $shi[$key]['son'][] = $v;
                 }

         	 }
         }

         if(empty($shi)) $this->result('',0,'数据错误503');
         $this->result($shi,1,'获取成功');
	}

    
    // public function 

 
	/**
	 * 修改账户信息
	 * @return 修改成功或失败
	 */    
    
    public function sheng(){
        
       $data = DB::table('co_china_data')->where('pid',1)->field('id,name')->select();

       $this->result($data,1,'获取成功');
       // $this->province();
    }
 
    public function shi(){
        
       $pid = input('post.id');
       if(empty($pid)) $this->result('',0,'参数错误');
       $data = DB::table('co_china_data')->where('pid',$pid)->field('id,name')->select();
       $fu = DB::table('cg_area')->column('area id');

       $this->result(['list'=>$data,'check'=>$fu],1,'获取成功');
       // $this->province();
    }
    


	/**
	 * 修改账户信息
	 * @return 修改成功或失败
	 */
	public function editAccount(){

		$data=input('post.');
		$validate=validate('Account');
		if($validate->check($data)){
			if($this->sms->compare($data['phone'],$data['code'])){
				$arr = [
					'phone'  =>  $data['phone'],
					'account'=>  $data['account'],
					'bank_name'=> $data['bank_name'],
					'branch' =>  $data['branch'],
					'bank'   =>  $data['bank'],
					// 'bank'=>$data['branch']
				];
				$res=Db::table($this->agent)->where('gid',$this->gid)->update($arr);

				if($res!==false){
					
					$this->result('',1,'修改账户成功');
				}else{

					$this->result('',0,'修改账户失败');
				}
			}else{
				$this->result('',0,'手机验证码错误');

			}
		}else{
			$this->result('',0,$validate->getError());
		}
		

	}

	/**
	 * 修改账户信息默认值
	 * @return 修改成功或失败
	 */
	
	public function exitcheck()
	{
		$data = Db::table('cg_supply')->where('gid',$this->gid)->field('account,bank_name,bank,branch,phone')->find();
		if(empty($data)) $this->result('',0,'获取数据失败');
		$this->result($data,1,'获取账户信息成功');

	}







	// code 
    public function ccc()
    {
       // $data = DB::table('co_bank_code')->column('code');
    	$data  = Db::table('co_bank_code')->field('code,name')->select();
       $this->result($data,1,'获取成功');
    }
   
	/**
	 * 发送修改账户信息的验证码
	 * @return [type] [description]
	 */
	public function accountCode()
	{
		$phone=input('post.phone');
		// 生成四位验证码
        $code=$this->apiVerify();

		$content="您的短信验证码是【".$code."】。您正在通过手机号修改银行账户号，如非本人操作，请忽略该短信。";

		return $this->smsVerify($phone,$content,$code);
	}


	/**
	 * 发送密码的验证码
	 * @return [type] [description]
	 */
	public function passCode()
	{
		$phone=input('post.phone');
		// 生成四位验证码
        return $this->forCode($phone);
	}

    // 获取用户手机号
    
    public function phones()
    {
       return  DB::table('cg_supply')->where('gid',$this->gid)->value('phone');
    }

	/**
	 * 修改密码
	 * @return [type] [description]
	 */
	public function modifyPass()
	{
		$data=input('post.');
		$validate=validate('ModifyPass');
		if($validate->check($data)){
			// 判断原密码是否输入正确
			if($this->pass($data['pass'],$this->gid) == false){

				$this->result('',0,'请输入正确的原密码');
			}
			// 判断手机验证码是否正确
			if($this->sms->compare($data['phone'],$data['code'])){

				if($this->xPass(get_encrypt($data['npass']),$this->gid)){

					$this->result('',1,'修改密码成功');

				}else{

					$this->result('',0,'修改密码失败');
				}

			}else{
				$this->result('',0,'手机验证码错误');
			}
			
		}else{
			$this->result('',0,$validate->getError());
		}
	}


	/**
	 * 检查运营商是否有上传营业执照，和设置供应地区
	 * @return [type] [description]
	 */
	public function selArea()
	{
		// 判断用户有没有上传营业执照
		$lice = Db::table('cg_supply')->where('gid',$this->gid)->value('license');
		$usecost = Db::table('cg_supply')->where('gid',$this->gid)->value('usecost');
		if(empty($lice) && empty($usecost)){
			$this->result('',2,'您还未上传营业执照，请您到个人中心上传。');
		}
		$status = Db::table('cg_supply')->where('gid',$this->gid)->value('status');
		if($status == 2 ){
			// 查看运营商是否设置地区
			$count = Db::table('cg_increase')->where('gid',$this->gid)->count();
			if($count > 0){
				$this->result('',1,'已设置地区');
			}else{
				$this->result('',0,'您还未设置地区，请您到个人中心->供应地区->设置您的地区。');
			}
		}else{
			$this->result('',3,'您已上传营业执照,请等待总后台审核。');
		}
	}

	/**
	 * 根据县区id获取县市省名称
	 * @return [type] [description]
	 */
	public function county($county)
	{
		// 市级id转换为字符串
        $city=$this->areaList($county);
        // 省级id转换为字符串
        $province=$this->areaList($city);
        // 查询所有省市县的数据
        $list=Db::table('co_china_data')->whereIn('id',$province.','.$city.','.$county)->select();
        return get_child($list,$list[0]['pid']);

	}



	/**
	 * 判断原密码是否正确
	 * @param  [type] $npass [修改的原密码]
	 * @param  [type] $gid   [运营商id]
	 * @return [type]        [布尔值]
	 */
	private function pass($pass,$gid)
	{	
		$pwd=Db::table('cg_supply')->where('gid',$gid)->value('pass');
		if(get_encrypt($pass) !== $pwd){
			return false;
		}else{
			return true;
		}
	}

	/**
	 * 修改密码
	 * @param  [type] $npass [新密码]
	 * @param  [type] $gid   [运营商id]
	 * @return [type]        [布尔值]
	 */
	private function xPass($npass,$gid)
	{
		$res=Db::table('cg_supply')->where('gid',$gid)->setField('pass',$npass);
		if($res !== false){
			return true;
		}
	}



	/**
	 * 运营商供应地区树形结构
	 * @return 城市地区id
	 */
	private function areaList($city)
	{
		$city=Db::table($this->china)->whereIn('id',$city)->select();
		return array_str($city,'pid');
	}




	/**
	 * 修改shop表状态为2
	 * @return  布尔值
	 */
	private function shopSta($gid){
		
		$res=Db::table($this->agent)->where('gid',$gid)->setField('status',2);
		if($res){
			return true;
		}
	}


	 /**
     * 登录的修改密码短信验证码
     * @var string
     */
    public function cenFor()
    {
        $phone=input('post.phone');
        return $this->forCode($phone);
    }
    
    // 取消合作
    public function Dissolution()
    {
    	$content = input('post.content');
        
        $status = Db::table('cg_apply_cancel')->where('gid',$this->gid)->value('status');
        if($status === 0) $this->result('',1,'正在审核中');

        if($content){

           $arr = Db::table('cg_supply')->where('gid',$this->gid)->field('gid,company,leader')->find();
           $area = DB::table('cg_area')->where('gid',$this->gid)->column('area');
           $arr['region'] = json_encode($area);
           $arr['reason'] = $content;
           $arr['phone'] = Db::table('cg_supply')->where('gid',$this->gid)->value('phone');
           $stock = Db::table('cg_stock a')
                    ->join('co_bang_cate b','a.materiel = b.id')
                    ->field('a.ration,a.materiel_stock,a.open_stock,b.name,b.set_price')
                    ->select();
           $arr['detail'] = json_encode($stock);
           
           $res = Db::table('cg_apply_cancel')->insert($arr);
            
            if($res){
            	// Db::table('cg_supply')->where('gid',$this->gid)->update(['status'=>6]);
            	$this->result('',1,'提交成功');
            }else{
            	$this->result('',0,'提交失败');
            }



        }
    }

    // 取消合作  
    // public function Dissolution()
    // {
    // 	 $content = input('post.content');
    // 	 if(empty($content)) $this->result('',0,'请输入取消理由');
    // 	 $status = Db::table('cg_apply_cancel')->where('gid',$this->gid)->value('status');
    // 	 if($status === 0) $this->result('',1,'正在审核中');
    	 
    // }
	
}
