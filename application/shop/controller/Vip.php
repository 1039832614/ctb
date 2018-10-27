<?php 
namespace app\shop\controller;
use app\base\controller\Shop;
use think\Db;
use Msg\Sms;

/**
 * vip维修厂后台
 */
class Vip extends Shop
{
	public function initialize()
	{
		parent::initialize();
		$this->Sms = new Sms();
	}
	/**
	 * 首次录入
	 */
	public function entering(){
		$data = input('post.');
		$data['plate']= input('post.plate','','strtoupper');
		$validate = validate('Vip');
		if($validate->check($data)){
			$data['order_num'] = $this->createCardNum();
			$data['sid'] = $this->sid;
			if(empty($data['uid'])){
				$this->result('','0','该用户不是邦保养vip');
			}
			//获取维修厂地区（市）
				$area = Db::table('cs_shop_set')
						->where('sid',$this->sid)
						->value('city');
				//查询是否有供应商
				$pid = Db::table('cp_area')
						->where('area',$area)
						->where('serve_id',$data['fid'])
						->where('status',1)
						->value('pid');
				if(empty($pid)){
					$data['mid'] = '0';
				}else{
					$data['mid'] = $pid;
				}
			unset($data['token']);
			$list = Db::table('v_order')->insert($data);
			if($list){
				$this->result('',1,'添加成功,请车主在小程序支付');
			}else{
				$this->result('',0,'添加失败');
			}
		}else{
			$this->result('',0,$validate->getError());
		}
	}
	/**
	 * 点击搜索
	 */
	public function seek(){
		$plate = input('post.');
		$plate['plate']= input('post.plate','','strtoupper');
		$validate = validate('plate');
		if($validate->check($plate)){
			$list = Db::table('u_card c')
					->join('u_user u','u.id = c.uid')
					->where('c.plate',$plate['plate'])
					->field('uid')
					->select();
			$data = array_unique(array_column($list,'uid'));
			$info = Db::table('u_user')
					->where('id','in',$data)
					->field('id,name')
					->select();
			if($info){
					$this->result($info,1,'获取成功');
			}else{
				$this->result('',0,'暂时没有此vip用户');
			}
		}else{
			$this->result('',0,$validate->getError());
		}
	}
	/**
	 * 后续更换
	 */
	public function  change(){
		$vin_num = input('post.vin_num'); 
		if(empty($vin_num)){
			$this->result('',0,'请输入VIN码');
		}else{
			$list = Db::table('v_order o')
				->join('u_user u','o.uid = u.id')
				->join('am_serve p','o.fid = p.id')
				->where('o.vin_num',$vin_num)
				->field('o.brand,o.type,o.zero,o.plate,o.fid,p.name,product,o.spec_name,o.number,o.price,p.redate,(o.price*(p.redate/100)) as z_price,u.phone')
				->select();
			foreach ($list as $key => $value) {
				$list[$key]['z_price'] = substr($list[$key]['z_price'],0,-7);
			}
			if($list){
				$this->result($list,1,'获取成功');
			}else{
				$this->result('',0,'该vin码暂无记录');
			}
		}
	}
	/**
	 * 发送短信验证码
	 */
	public function vcode()
	{
		$mobile = input('post.mobile');
		$code = $this->apiVerify();
		$content = "您的验证码是：【{$code}】。请不要把验证码泄露给其他人。";
		$res = $this->Sms->send_code($mobile,$content,$code);
		if($res == "提交成功"){
			$this->result('',1,$res);
		}else{
			$this->result('',0,$res);
		}
	}
	/**
	 * 产品编码修改状态
	 */
	public function num_find(){
	    $num = input('post.');
	    $check = $this->Sms->compare($num['phone'],$num['code']);
	    if ($check != false){
	    	$list = array();
	    	if(empty($num['num'])){
	    		$this->result('',0,'请填写产品编码');
	    	}else{
	    		foreach ($num['num'] as $key => $value) {
	            $arr = Db::table('cp_code')
	                ->where('product_code',$num['num'][$key]['code'])
	                ->update(['u_status'=>1]);
	        	}
		        array_push($list,$arr);
		        $as = in_array(0, $list);
		        if($as){
		        	$this->result('','0','产品编码有误');
		   		}else{
		        	$this->result('','1','提交成功');
		    	}
	    	}
	    }else{
	        $this->result('',0,'验证码错误或已过期');
	    }
	}
	/**
	 * 免费特权
	 */
	public function free(){
		$code = input('post.code');
		if(strlen($code) !==16){
			$this->result('',0,'兑现码格式不正确');
		}
		// 检测兑换码是否有效
		$res = Db::table('v_free_change')
				 ->where('code',$code)
				 ->where('status',0)
				 ->find();
		// 如果符合兑换则进行兑换
		if($res){
			$ex = Db::table('v_free_change')
					->where('code',$code)
					->update([
						'status'=>1,
						'sid'=>$this->sid
					]);
			$list = Db::table('v_free_change f')
				->join('am_free a','f.fid = a.id')
				->where('f.code',$code)
				->field('a.name,a.size,a.number,a.subsidy')
				->select();
			if($ex !== false){
				$this->result($list,1,'兑换成功');
			}else{
				$this->result('',0,'兑现码失效');
			}
		}else{
			$this->result('',0,'兑现码失效');
		}
	}
	/**
	 * 用户预约列表
	 */
	public function order($status){
		$pageSize = 8;
        $page = input('post.page')? : 1;
        $count = Db::table('v_subscribe s')
				->join('u_user u','u.id = s.uid')
				->join('v_order o','o.id = s.oid')
				->where('s.sid',$this->sid)
				->where('s.status',$status)
				->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('v_subscribe s')
				->join('u_user u','u.id = s.uid')
				->join('v_order o','o.id = s.oid')
				->where('s.sid',$this->sid)
				->field('s.id,o.plate,u.phone,s.time,s.status')
				->where('s.status',$status)
				->order('time desc')
				->page($page,$pageSize)
				->select();
		if($count){
            $this->result(['list'=>$list,'rows'=>$rows],1,'获取成功');
        }else{
            $this->result('',0,'暂无数据');
        }
	}
	//待确定
	public function d_order(){
		return $this->order('0');
	}
	//已确定
	public function y_order(){
		//是否有超时
		$this->over_status();
		return $this->order('1');
	}
	//确定预约
	public function confirm(){
		$id = input('post.id');
		$list = Db::table('v_subscribe')
				->where('id',$id)
				->update(['status'=>1]);
		if($list){
				//向供应商发送补货单
				//获取维修厂地区
				$area = Db::table('v_subscribe s')
						->join('cs_shop_set p','p.sid =s.sid')
						->where('s.id',$id)
						->value('city');
				//获取供应项
				$serve = Db::table('v_subscribe s')
						->join('v_order o','s.oid = o.id')
						->where('s.id',$id)
						->value('o.fid');
				//查询是否有供应商
				$pid = Db::table('cp_area')
						->where('area',$area)
						->where('serve_id',$serve)
						->where('status',1)
						->value('pid');
				if(empty($pid)){
					$arr = [
						'pid'=>0,
						'cid'=>$id,
						'time'=>time(),
					];
				}else{
					$arr = [
						'pid'=>$pid,
						'cid'=>$id,
						'time'=>time(),
					];
				}
				$info = Db::table('cp_order')->insert($arr);
				$this->result($list,1,'预约成功');
			}else{
				$this->result('',0,'已预约');
		}
	}
	/**
	 * 预约详情
	 */
	public function order_detail(){
		$data = input('post.');
		$list = Db::table('v_subscribe s')
				->join('v_order o','o.id = s.oid')
				->join('am_serve v','o.fid = v.id')
				->where('s.id',$data['id'])
				->field('o.brand,o.type,o.zero,o.plate,v.name,o.product,o.spec_name,o.number,o.price,v.redate')
				->select();
		if($list){
				$this->result($list,1,'获取成功');
			}else{
				$this->result('',0,'获取失败');
		}
	}
	/**
	 * 已完成
	 */
	public function complete(){
		$pageSize = 8;
        $page = input('post.page')? : 1;
        $count =$list = Db::table('v_subscribe s')
				->join('v_order o','s.oid = o.id')
				->join('am_serve v','o.fid = v.id')
				->join('u_user u','u.id = o.uid')
				->where('s.status','2')
				->where('s.sid',$this->sid)
				->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('v_subscribe s')
				->join('v_order o','s.oid = o.id')
				->join('am_serve v','o.fid = v.id')
				->join('u_user u','u.id = o.uid')
				->where('s.status','2')
				->where('s.sid',$this->sid)
				->field('plate,o.product,o.spec_name,o.number,o.price,v.redate,u.phone,s.time')
				->order('s.time desc')
				->page($page,$pageSize)
				->select();
		if($count > 0){
				$this->result(['list'=>$list,'rows'=>$rows],1,'获取成功');
			}else{
				$this->result('',0,'暂无数据');
		}
	}
	/**
	 * 订单异常
	 */
	public function anomaly(){
		$this->over_status();
		$pageSize = 8;
        $page = input('post.page')? : 1;
        $count =  Db::table('v_subscribe s')
				->join('cp_order p','p.cid = s.id')
				->join('v_order o','o.id = s.oid')
				->join('u_user u','u.id = s.uid')
				->where('s.status','3')
				->where('s.sid',$this->sid)  
				->count();
		$rows = ceil($count / $pageSize);
		$list =  Db::table('v_subscribe s')
				->join('cp_order p','p.cid = s.id')
				->join('v_order o','o.id = s.oid')
				->join('u_user u','u.id = s.uid')
				->where('s.status','3')
				->where('s.sid',$this->sid)  
				->field('p.id,o.plate,u.phone,s.create_time,s.time,p.m_status')
				->order('s.time desc')
				->page($page,$pageSize)
				->select();
		foreach ($list as $key => $value) {
			$list[$key]['overtime']  = date('Y-m-d H:i:s', time());
		}
		if($count){
				$this->result(['list'=>$list,'rows'=>$rows],1,'获取成功');
			}else{
				$this->result('',0,'暂无数据');
		}
	}
	/**
	 * 修改超时状态值
	 */
	public function over_status(){
		$list = Db::table('v_subscribe')
				->where('sid',$this->sid)
				->field('id,time,status')
				->select();
		foreach ($list as $key => $value) {
			if($list[$key]['status'] = 1){
				$time = strtotime($list[$key]['time']);
				if(time() - ($time + 259200) >0){
					$info = Db::table('v_subscribe')
							->where('id',$list[$key]['id'])
							->setField('status',3);
				}
			}
		}
	}

	//物料是否回收
	public function is_status(){
		$id = input('get.id');
		$list = Db::table('cp_order')
				->where('id',$id)
				->where('f_status','>',0)
				->update(['m_status'=>'1']);
		if($list){
				$this->result($list,1,'物料回收成功');
			}else{
				$this->result('',0,'物料回收失败');
		}
	}
	/**
	 * 物料申请订单
	 */
	public function apply(){
		$pageSize = 8;
        $page = input('post.page')? : 1;
        $count =  Db::table('cp_order o')
				->join('cp_merchant p','o.pid = p.id')
				->join('v_order v','o.cid = v.id')
				->where('v.sid',$this->sid)
				->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('cp_order o')
				->join('cp_merchant p','o.pid = p.id')
				->join('v_order v','o.cid = v.id')
				->where('v.sid',$this->sid)
				->field('o.id,company,leader,p.phone,v.product,FROM_UNIXTIME(o.time) as time,o.f_status')
				->order('o.time desc')
				->page($page,$pageSize)
				->select();
		if($list){
				$this->result(['list'=>$list,'rows'=>$rows],1,'获取成功');
			}else{
				$this->result('',0,'暂无数据');
		}
	}
	/**
	 * 物料申请订单详情
	 */
	public function apply_detail(){
		$data = input('post.');
		$list = Db::table('cp_order o')
				->join('cp_merchant p','o.pid = p.id')
				->join('v_order v','o.cid = v.id')
				->where('o.id',$data['id'])
				->field("v.brand,v.type,v.zero,v.product,v.spec_name,v.number,p.province,p.city,p.county,p.address")
				->select();
		if($list){
			$this->result($list,'1','获取成功');
		}else{
			$this->result('','0','获取失败');
		}
	}
	/**
 	* 物料申请订单详情中确定收货
 	*/
	public function confirm_num(){
	    $num = input('post.');
	    $list = Db::table('cp_order')->where('id',$num['id'])->value('number');
	    $data['list'] = explode(',',$list);
	    $arr = array();
	    foreach ($num['num'] as $key=>$value){
	        $arr[] = $num['num'][$key]['code'];
	    }
	    $datas = array_diff($data['list'],$arr);
	    if (!empty($datas)){
	        $this->result('',0,'所输入的产品编码有误');
	    }else{
	        Db::table('cp_order')->where('id',$num['id'])->update(['f_status'=>2]);
	        $this->result('',1,'提交成功');
	    }
	}
	/**
	 * 供应商  搜索
	 */
	public function search(){
		$data = input('post.');
		$page = input('post.page')? : 1;
        $pageSize = 8;
        if(!isset($data['searchType'])){
        	$this->result('',0,'请选择搜索类型');
        }
		if($data['searchType'] == 'time'){
			if(!isset($data['start'])){
        		$this->result('',0,'请输入时间区间');
        	}
			$data['start'] = substr($data['start'],0,-3);
			$data['end'] = substr($data['end'],0,-3);
			$count = Db::table('cp_order o')
				->join('cp_merchant p','o.pid = p.id')
				->join('v_order v','o.cid = v.id')
				->where('v.sid',$this->sid)
				->where('time','>',$data['start'])
				->where('time','<',$data['end'])
				->count();
			$rows = ceil($count / $pageSize);
			$list = Db::table('cp_order o')
				->join('cp_merchant p','o.pid = p.id')
				->join('v_order v','o.cid = v.id')
				->where('time','>',$data['start'])
				->where('time','<',$data['end'])
				->where('v.sid',$this->sid)
				->field('o.id,company,leader,phone,v.product,FROM_UNIXTIME(o.time) as time,o.f_status')
				->page($page,$pageSize)
				->order('o.time desc')
				->select();
		}else{
			if(!isset($data['searchKey'])){
        		$this->result('',0,'请输入搜索内容');
        	}
			$count = Db::table('cp_order o')
				->join('cp_merchant p','o.pid = p.id')
				->join('v_order v','o.cid = v.id')
				->where('v.sid',$this->sid)
				->where($data['searchType'],'like','%'.$data['searchKey'].'%')
				->count();
			$rows = ceil($count / $pageSize);
			$list = Db::table('cp_order o')
				->join('cp_merchant p','o.pid = p.id')
				->join('v_order v','o.cid = v.id')
				->where('v.sid',$this->sid)
				->where($data['searchType'],'like','%'.$data['searchKey'].'%')
				->field('o.id,company,leader,phone,v.product,FROM_UNIXTIME(o.time) as time,o.f_status')
				->page($page,$pageSize)
				->order('o.time desc')
				->select();
		}
		if($list){
				$this->result(['list'=>$list,'rows'=>$rows],1,'获取成功');
			}else{
				$this->result('',0,'暂无数据');
		}
	} 
	/**
     * 
     * @param int $length
     * @return 产生的随机字符串
     */
   	public function createCardNum(){
        static $i = -1;$i ++ ;
        $a = substr(date('YmdHis'), -12,12);
        $b = sprintf ("%02d", $i);
        if ($b >= 100){
            $a += $b;
            $b = substr($b, -2,2);
        }
        return $a.$b;
    }
	/**
	 * 获取汽车品牌
	 */
	public function getBrand()
	{
		$data = Db::table('co_car_menu')->field('id as brand_id,name,abbr')->select();
		if($data){
			$this->result( $data,1,'获取成功');
		}else{
			$this->result('',0,'获取数据异常');
		}
	}

	/**
	 * 获取汽车类型
	 */
	public function getAudi()
	{
		$bid = input('get.brand_id');
		$res = Db::table('co_car_cate')->field('type')->where('brand',$bid)->select();
		$data = array_unique(array_column($res,'type'));
		if($data){
			$this->result($data,1,'获取成功');
		}else{
			$this->result('',0,'获取数据异常');
		}
	}

	/**
	 * 获取汽车排量
	 */
	public function getDpm()
	{
		$bid = input('get.brand_id');
		$type = input('get.type');
		$res = Db::table('co_car_cate')->where('brand',$bid)->where('type',$type)->field('series')->select();
        $data = array_unique(array_column($res,'series'));
        if($data){
			$this->result($data,1,'获取成功');
		}else{
			$this->result('',0,'获取数据异常');
		}
	}

	/**
	 * 服务项
	 */
	public function servers(){
		$list = Db::table('am_serve')
				->field('id,name')
				->select();
		if($list){
			$this->result($list,1,'获取成功');
		}else{
			$this->result('',0,'获取数据异常');
		}
	}
}