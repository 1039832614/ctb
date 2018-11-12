<?php

namespace app\shop\controller;
use app\base\controller\Shop;
use think\Db;

/**
 * vip维修厂后台
 */

class Svip extends Shop
{

/** 首次录入 **/
	public function firstEntry()
	{
    	/* 
    	   获取车牌号、服务项id 、产品名称 、规格 、数量 、价格
    	   维修厂id 
    	 */
        
    	$data = input('post.');
        $validate = validate('vip');
        if (!$validate->check($data)) {
        	$this->result('',0,$validate->getError());
        }            
        
        // 获取用户Uid 、 设备号
        $user = $this->getUid($data['plate']);
        if (!$user) {
        	$this->result('', 0, '车牌号错误或该用户暂不是车服管家用户');
        }

        // 判断是否是首次录入
        $sum  = DB::table('cb_privil_ser')->where('plate', $data['plate'])->where('shop_id', $data['shop_id'])->count();
        // if ($sum) {
        //     $this->result('', 0, '改车牌已录入');
        // }
        
        // 获取品牌 、 车型 、 油耗
        $brand = Db::table('co_car_menu')->where('id', $data['brand'])->value('name');  // 品牌名称
        $car = Db::table('co_car_cate')->where('id', $data['car'])->value('type');      // 车型名称
        $oil = Db::table('co_car_cate')->where('id', $data['series'])->value('series');    // 油型
        unset($data['series']);
        unset($data['brand']);
        unset($data['car']);
        $brand_car_displa = $brand . '/' . $car . '/' . $oil;


        // 获取产品图片 、更换周期 、产品描述 、服务项id 
        $product = $this->moShopDetails($data['shop_id']);
        $data['odd_num'] = build_order_sn(); 
        $data['cycle'] = $product['cycle'];
        $data['pro_pic'] = $product['pro_pic'];
        $data['desc'] = $product['content'];
        $data['fid'] = $product['fid'];
        $data['pay_status'] = 2;
        $data['uid'] = $user['u_id'];
        $data['eq_num'] = $user['eq_num'];
        $data['sid'] = $this->sid;
       
        $data['brand_car_displa'] = $brand_car_displa;
        $data['redate'] = DB::table('am_serve')->where('id', $data['shop_id'])->value('redate');
        $result = Db::table('cb_privil_ser')->strict(false)->insert($data);

        if (!$result) {
        	$this->result('', 0, '录入失败');
        }
        $this->result('', 1, '录入成功');
	}
    
    /**
     *  图片上传
     */
    
    public function file()
    {
        return upload('file','shop/vip','https://ceshi.ctbls.com');
    }

    /**
     * 车牌号检索
     */
    
    public function examine()
    {
        $plate = input('post.plate');
        if (!$plate) {
            $this->result('', 0, '车牌号错误');
        }
        // 获取用户Uid 、 设备号
        $user = Db::table('cb_user')->where('plate', $plate)->field('u_id, name')->find();
        if (!$user) {
            $this->result('', 0, '车牌号错误或该用户暂不是车服管家用户');
        }         
        $sum  = DB::table('cb_privil_ser')->where('plate', $plate)->count();
        // if ($sum) {
        //     $this->result('', 0, '改车牌已录入');
        // }
        $this->result([$user], 1, '首次录入');
    }
    
    /**
     * 获取用户id 、设备号
     *
     */
    
    public function getUid($plate)
    {

    	$user = DB::table('cb_user')->where('plate', $plate)->field('u_id , eq_num')->find();

    	return $user;
    }

    /**
     * 获取服务项
     * 
     */
    
    public function getService()
    {

    	$data = Db::table('am_ser_pro')->where('pro_num', '>' , 0)->field('id fid, ser_name')->select();
        
    	if (!$data) {
    		$this->result('', 0, '获取失败');
    	}
    	$this->result($data, 1, '获取成功');
    }

    /**
     * 获取产品名称 、更换周期 、产品图片 、产品描述 、产品规格 、服务项id
     * 
     */
    
    public function getProduct()
    {
    	$server_id = input('post.server_id')?:die('缺少服务项server_id');

        $data = $this->moProduct($server_id);

    	if (!$data) {
    		$this->result('', 0, '获取失败');
    	}
    	$this->result($data, 1, '获取成功');
    }

    /**
     * 品牌列表
     * 
     */
    
    public function getBrands()
    {

    	$data = DB::table('co_car_menu')->field('id, name')->select();

    	if (!$data) {
    		$this->result('', 0, '获取失败');
    	}
    	$this->result($data, 1, '获取成功');
    }
    
    /**
     * 车型 
     * 
     */
    
    public function getCycle()
    {

    	$brand = input('post.brand_id')?:die('缺少品牌brand_id');
      // $brand = DB::table('co_car_menu')->where()
        $data = Db::table('co_car_cate')->field('distinct(type), id')->where('brand',$brand)->select();
        // $data = array_unique(array_column($data,'type'));

    	if (!$data) {
    		$this->result('', 0, '获取失败');
    	}
    	$this->result($data, 1, '获取成功');
    }

    /**
     * 油耗
     * 
     */
    public function getOil()
    {
        $gid = input('post.id');
        // echo $gid;die;
        if (!$gid) {
            $this->result('',0,'车型id错误');
        }
        // echo $gid;die;
        $type = DB::table('co_car_cate')->where('id', $gid)->value('type');
        // echo $type;die;
        $data= Db::table('co_car_cate')->where('type', $type)->select();
        if (!$data) {
            $this->result('', 0 ,'暂无油耗');
        }
        $this->result($data,1,'');
    }



    /**
     * 获取产品名称 、产品规格 
     * 
     */
    
    public function moProduct($server_id)
    {      
        
        $data = DB::table('am_serve')->where('pid', $server_id)
                ->field('id shop_id, name pro_ame, size spec')
                ->select();

        return $data;      
    }

    /**
     * 获取产品名称 、更换周期 、产品图片 、产品描述 、产品规格 、服务项id
     */
    
    public function moShopDetails($id)
    {
        $data = Db::table('am_serve')->where('id', $id)->field('period cycle, image pro_pic, pid fid ,content')->find();
        return $data;
    }

/** 用户预约 **/

    /**
     * 获取待确认列表
     * 
     */
     
	public function getTobeMake()
	{   
    	$data = $this->moMakeList($this->sid, 0);
        if (!$data['list']) {
            $this->result('', 0, '暂无数据');
        }

        $data['list'] = changeTimes($data['list'], 'make', 'Y/m/d   H:i');
        $this->result($data, 1, '获取成功');
	}
    
    /**
     * 获取已确认列表
     * 
     */
    
    public function  getAffirmMake()
    {
    	$data = $this->moMakeList($this->sid, 2);
        if (!$data['list']) {
            $this->result('', 0, '暂无数据');
        }   

        $data['list'] = changeTimes($data['list'], 'make', 'Y/m/d   H:i');
        $this->result($data, 1, '获取成功');
    }

    /**
     * 获取已完成列表
     * 
     */
    
    public function  getCompleteMake()
    {   
    	$data = $this->moMakeList($this->sid, 1);
        if (!$data['list']) {
            $this->result('', 0, '暂无数据');
        }   

        $data['list'] = changeTimes($data['list'], 'pay_time', 'Y/m/d   H:i');
        $this->result($data, 1, '获取成功');

    }    

    /**
     * 获取异常订单列表
     *
     * @todo  
     */
    
    public function  getUnusualMake()
    {   
    	$data = $this->moMakeList($this->sid, 4);
        if (!$data['list']) {
            $this->result('', 0, '暂无数据');
        }   
        $data['list'] = changeTimes($data['list'], 'create_time', 'Y/m/d   H:i');
        $data['list'] = changeTimes($data['list'], 'make', 'Y/m/d   H:i');
        $this->result($data, 1, '获取成功');
    }  


    /**
     * 获取预约详情
     * @todo  缺少品牌、车型、排量 ； 待商议
     */
    
    public function getDetailMake()
    {
        // die;
    	$id = input('post.id')?:die('缺少id');

    	$data = Db::table('cb_privil_ser a')
    	        ->join('am_ser_pro b', 'a.fid = b.id')
    	        ->where('a.id', $id)
    	        ->field('a.brand_car_displa, a.plate, b.ser_name, a.pro_ame, a.spec, a.number, a.price, a.redate, a.type')
    	        ->where('a.sid', $this->sid)
    	        ->find();

        if (!$data) {
        	$this->result('', 0, '获取失败');
        }

        if ($data['redate'] == 0) {
            $redate = 1;
        } else {
            $redate = $data['redate'];
        }

        if ($data['type'] == 0) {

            $data['one_price'] = $data['price'];   // 首次价格
            $data['two_price'] = bcmul($data['price'], $redate/100, 2); // 折扣后价格 
        }
        elseif ($data['type'] == 1) {
            $data['one_price'] = bcmul(bcdiv($data['price'], $redate, 2), 100, 2);  // 折扣前价格
            $data['two_price'] = $data['price'];  // 折扣后价格
        }

        if (isset($data['redate'])) {
            $data['redate'] = $data['redate'].'%';        
        }
      
        $this->result($data, 1, '获取成功');
    }
    
    /**
     * 待确认确定操作
     * 
     */
    
    public function tobeConfirm()
    {
    	$id = input('post.id')?:die('缺少id');
        
        $result = Db::table('cb_privil_ser')
                  ->where([
        	                'id' => $id,
        	                'sid' => $this->sid,
        	                'pay_status' => 0
        	             ])
                  ->update(['pay_status'=>2]);
        
        if ($result === false) {
        	$this->result('', 0, '确认失败');
        }
        $this->result('', 1, '确认成功');

    }

    /**
     * 用户预约 vip列表
     * 
     * @param  $status 0 未支付(预约)  1已支付  2已确认（未支付）   3预约超时   4  异常订单
     * @todo 预约内容不知所云， 暂定为 desc 产品描述
     */
	public function moMakeList($sid,$status,$size=8)
	{   

		$count = Db::table('cb_privil_ser')
                ->where([
                            'type' => 1,
                            'pay_status' => $status,
                            'sid'  => $sid,
                         ])
                ->count();
		if (!$count) {
			$this->result('', 0, '暂无数据');
		}
		$rows = ceil($count/$size);
		// 获取要查询的字段
		$field = $this->moType($status);
		$data = Db::table('cb_privil_ser a')
		        ->join('cb_user b' , 'a.uid = b.u_id')
		        ->where('a.type', 1)
		        ->where('a.pay_status', $status)
		        ->where('a.sid', $this->sid)
		        ->page(input('post.page')?:1,$size)
		        ->field($field)
		        // ->field('a.id, a.plate, b.phone, a.make, a.desc')
		        ->select();

    return ['rows'=>$rows,'list'=>$data];
		$this->result(['rows'=>$rows,'list'=>$data], 1, '获取成功');
	}

	/**
	 * 构造获取字段
	 *
	 * @todo  更换时间暂定为  cycle 更换周期； 待商议
	 * @todo  预约服务暂定为 产品描述desc； 
	 */
	
	public function moType($status)
	{   
        // 0未支付(预约)  2已确认(未支付)
    	if ($status == 0 || $status == 2) {
        	$field = 'a.id, a.plate, b.phone, a.make, a.pro_ame';
    	}
        // 1已支付
    	elseif ($status == 1) {
        	$field = 'a.id, a.pro_ame, a.spec, a.number, a.price, a.redate, a.plate, b.phone ,a.pay_time';
    	}
        // 4异常订单
    	elseif ($status == 4) {
    		$field = 'a.id, a.plate, b.phone, a.create_time, a.make, a.pro_ame';
    	}

    	return $field?:'';
	}
    
}