<?php 
namespace app\shop\controller;
use app\base\controller\Shop;
use think\Db;
/**
* 维保商机()
*/
class BusOppo extends Shop
{
	public function initialize()
	{
		parent::initialize();
		$this->http = 'https://obd.ctbls.com/api/';
		// $this->http = 'http://car.douying.me:8080/api/';
	}

	/**
	 * 页面提示
	 * @return [type] [description]
	 */
	public function indexWarn()
	{
		// 养护
		$data['rem'] = $this->mainRem(1);
		// 故障
		$data['fault'] = $this->fault(1);
		// 碰撞
		$data['collRem'] = $this->collRem(1);

		if($data['rem'] != 0 || $data['fault'] != 0 || $data['collRem'] != 0){
			$this->result($data,1,'获取信息成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}
	
	
	/**
	 * 养护提醒
	 * @return [type] [description]
	 */
	public function mainRem($type = '')
	{
		$page = input('post.page')?:1;
		$search = input('post.search');
		// 调取报警接口
		$url = $this->http.'alarm';
		//获取该维修厂的所有设备号
		$eq_num = $this->eqNum($search);
		if(empty($eq_num['OBDIDs']) && $type == 1){
			return 0;
		}else if(empty($eq_num['OBDIDs'])){
			$this->result('',0,'暂无养护提醒');
		}
		//接口相同传参构造
		$data = $this->interArr($page);

		$data['alarmType']=1;
		$data['listAlarmType'] = [0,1,2,3,4,5,93,10,30,104,9];
		$data['OBDIDs'] = $eq_num;
		$data['isProcessed'] = 0;
		$data = json_encode($data);
		$data = $this->posturl($data,$url);

		$data = json_decode($data,true);

		if($type == 1){
			return $data['total'];
		}
		if($data['total'] == 0) $this->result('',0,'暂无养护提醒');

		$data= $this->arrData($data);
		// 根据设备号查询用户的车牌号 姓名  电话  车型


		if($data){
			$this->result($data,1,'获取列表成功');
		}else{
			$this->result('',0,'暂无养护提醒');
		}	

	}



	/**
	 * 故障提醒
	 * @return [type] [description]
	 */
	public function fault($type = '')
	{
		$page = input('post.page')?:1;
		$search = input('post.search');
		$url = $this->http.'faultcode';
		//获取所有已绑定小程序的用户设备号(同时是邦保养会员及该维修厂的用户)
		$eq_num = $this->eqNum($search);
		if(empty($eq_num['OBDIDs']) && $type == 1){
			return 0;
		}else if(empty($eq_num['OBDIDs'])){
			$this->result('',0,'暂无故障预警');
		}
		// print_r($eq_num);exit;
		//接口的相同传参
		$data = $this->interArr($page);
		$data['OBDIDs'] = $eq_num;
		$data['isProcessed'] = 0;
		$data = json_encode($data,JSON_UNESCAPED_UNICODE);
		$data = $this->posturl($data,$url);
		// json转数组
		$data = json_decode($data,true);


		if($type == 1){
			return $data['total'];
		} 

		// print_r($data);exit;
		if($data['total'] == 0) $this->result('',0,'暂无故障预警');
		// 和用户信息一起构造数组
		$data = $this->arrData($data);


		if($data){
			$this->result($data,1,'获取列表成功');
		}else{
			$this->result('',0,'暂无故障预警');
		}	
		


	}


	/**
	 * 碰撞提醒
	 * @return [type] [description]
	 */
	public function collRem($type = '')
	{
		$page = input('post.page')?:1;
		$search = input('post.search');
		// 调取报警接口
		$url = $this->http.'alarm';
		//获取该维修厂的所有设备号
		$eq_num = $this->eqNum($search);
		if(empty($eq_num['OBDIDs']) && $type == 1){
			return 0;
		}else if(empty($eq_num['OBDIDs'])){
			$this->result('',0,'暂无碰撞提醒');
		}
		//接口相同传参构造
		$data = $this->interArr($page);
		$data['alarmType']=22;
		$data['listAlarmType'] = [22,1];
		$data['OBDIDs'] = $eq_num;
		$data['isProcessed'] = 0;
		$data = json_encode($data);
		// print_r($data);exit;
		$data = $this->posturl($data,$url);

		// json转数组
		$data = json_decode($data,true);

		if($type == 1){
			return $data['total'];
		}

		if($data['total'] == 0) $this->result('',0,'暂无碰撞提醒');
		// 和用户信息一起构造数组
		$data= $this->arrData($data);
		 
		// 根据设备号查询用户的车牌号 姓名  电话  车型 
		if($data){
			$this->result($data,1,'获取列表成功');
		}else{
			$this->result('',0,'暂无碰撞提醒');
		}	

	}

	/**
	 * 故障处理操作
	 * @return [type] [description]
	 */
	public function handle()
	{
		$flagId = input('post.flagId');
		$obd = input('post.obd');

		// 调取修改报警及故障状态的接口
		$url = $this->http.'setProcessedStatus';
		// 接口参数
		$data = [
			// 故障报警 现在的状态 
			'alarmORfalutCode'=>'falutCode',
			// 码
			'flagId'=>$flagId,
			// 要设置的状态
			'isProcessed'=>1,
		];

		$data = json_encode($data);
		$data = $this->posturl($data,$url);
		if($data == 1){
			$res = Db::table('cs_sto_time')->insert(['obd'=>$obd,'flagId'=>$flagId,'type'=>2]);
			if($res) $this->result('',1,'操作成功');
			$this->result('',0,'操作失败');
			
		}else{
			$this->result('',0,'操作失败');
		}
	}


	/**
	 * 养护提醒处理操作
	 * @return [type] [description]
	 */
	public function curHandle()
	{
		$flagId = input('post.flagId');
		$obd = input('post.obd');
		// 调取修改报警及故障状态的接口
		$url = $this->http.'setProcessedStatus';
		// 接口参数
		$data = [
			// 故障报警 现在的状态 
			'alarmORfalutCode'=>'alarm',
			// 码
			'flagId'=>$flagId,
			// 要设置的状态
			'isProcessed'=>1,
		];

		$data = json_encode($data);
		// 调用接口
		$data = $this->posturl($data,$url);
		if($data == 1){
			$res = Db::table('cs_sto_time')->insert(['obd'=>$obd,'flagId'=>$flagId,'type'=>1]);
			if($res) $this->result('',1,'操作成功');
			$this->result('',0,'操作失败');
		}else{
			$this->result('',0,'操作失败');
		}
	}




	/**
	 * 已处理(养护提醒)
	 * @return [type] [description]
	 */
	public function mainWarn()
	{
		$page = input('post.page')?:1;
		// 调取报警接口
		$url = $this->http.'alarm';
		// 获取搜索的条件
		$search = input('post.search');
		// $this->result('',0,'暂无已处理');
		//获取所有已绑定小程序的用户设备号(同时是邦保养会员及该维修厂的用户)
		$eq_num = $this->eqNum($search);
		if(empty($eq_num['OBDIDs'])){
			$this->result('',0,'暂无已处理');
		}
		//获取设备号结束

		//接口相同传参构造
		$data = $this->interArr($page);
		$data['listAlarmType'] = [0,1,2,3,4,5,93,10,30,104,9,22];
		$data['OBDIDs'] = $eq_num;
		$data['isProcessed'] = 1;
		$data = json_encode($data);
		// print_r($data);exit;
		$data = $this->posturl($data,$url);
		// json转数组
		$data = json_decode($data,true);

		if($data['total'] == 0) $this->result('',0,'暂无已处理');
		// 和用户信息一起构造数组
		$data= $this->arrData($data);
		
		// 查询已处理的信息
		$arr = Db::table('cs_sto_time')->where('type',1)->select();

		// 构造处理时间
		$data = $this->timeHand($data,$arr);

		// 根据设备号查询用户的车牌号 姓名  电话  车型 
		if($data){
			$this->result($data,1,'获取列表成功');
		}else{
			$this->result('',0,'暂无已处理');
		}	
	}



	/**
	 * 故障提醒已处理
	 * @return [type] [description]
	 */
	public function faultHand()
	{
		$page = input('post.page')?:1;
		// 获取搜索条件
		$search = input('post.search');

		$url = $this->http.'faultcode';

		//获取所有已绑定小程序的用户设备号(同时是邦保养会员及该维修厂的用户)
		$eq_num = $this->eqNum($search);
		if(empty($eq_num['OBDIDs'])){
			$this->result('',0,'暂无已处理');
		}
		//接口的相同传参
		$data = $this->interArr($page);
		// 结束
		
		$data['OBDIDs'] = $eq_num;
		$data['isProcessed'] = 1;
		$data = json_encode($data,JSON_UNESCAPED_UNICODE);
		$data = $this->posturl($data,$url);
		$data = json_decode($data,true);
		if($data['total'] == 0) $this->result('',0,'暂无故障预警');

		// 和用户信息一起构造数组
		$data = $this->arrData($data);
		
		// 查询已处理的信息
		$arr = Db::table('cs_sto_time')->where('type',2)->select();

		// 构造处理时间
		$data = $this->timeHand($data,$arr);

		if($data){
			$this->result($data,1,'获取列表成功');
		}else{
			$this->result('',0,'暂无数据');
		}	

	}



	/**
	 * 获取用户设备状态
	 * @return [type] [description]
	 */
	public function doesRun()
	{
		$page = input('post.page')?:1;
		// 获取搜索的值
		$nameOrobdId = input('post.search');
		$pageSize = 8;
		$url = $this->http.'LastLoction';
		//获取所有已绑定小程序的用户设备号(同时是邦保养会员及该维修厂的用户)
		$eq_num = $this->eqNum($nameOrobdId);
		if(empty($eq_num['OBDIDs'])){
			$this->result('',0,'暂无数据');
		}
		$data['OBDIDs'] = $eq_num;
		$data = json_encode($data,JSON_UNESCAPED_UNICODE);
		$data = $this->posturl($data,$url);
		$data = json_decode($data,true);
		// print_r($data);exit;
		// 获取所有的OBDID
		$OBDID = array_column($data,'OBDID');
		$count = Db::table('cb_user ub')
				->join('co_car_cate cc','ub.car_cate_id = cc.id')
				->whereIn('ub.eq_num',$OBDID)
				// ->field('cc.type,ub.name,ub.phone,ub.plate,eq_num')
				->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('cb_user ub')
				->join('co_car_cate cc','ub.car_cate_id = cc.id')
				->whereIn('ub.eq_num',$OBDID)
				->order('ub.u_id desc')
				->field('cc.type,ub.name,ub.phone,ub.plate,eq_num')
				->select();
		foreach ($data as $k => $v) {
			foreach ($list as $ke => $va) {
				if($v['OBDID'] == $va['eq_num']){

					if(strtotime($v['inDate']) + 20 > time()){
						$list[$ke]['inDate'] = 1;
					}else{
						$list[$ke]['inDate'] = 0;
					}

				}
			}	
		}
		if($list){
			$this->result(['rows'=>$rows,'list'=>$list],1,'获取列表成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}


	/**
	 * 构造处理列表 返回给前端的数组
	 * @param  [数组] $data [调取接口获取的数据]
	 * @param  [数组] $arr [自己数据库查询的数据]
	 * @return [type]       [description]
	 */
	private function timeHand($data,$arr)
	{
		foreach ($data['Rows'] as $k => $v) {
			foreach ($arr as $ke => $va) {
				if($v['_flag'] == $va['flagId']){
					$data['Rows'][$k]['time'] = $va['create_time'];
				};
			}
		}

		return  $data;		
	}



	/**
	 * 获取修车厂的用户设备号
	 * @return [type] [description]
	 */
	private function eqNum($search = '')
	{
		// 查询该维修厂购卡的用户id
		$user_id = Db::table('u_card')->where(['sid'=>$this->sid,'pay_status'=>1])->column('uid');
		// 根据用户id获取unionId
		$unionId = Db::table('u_user')->whereIn('id',$user_id)->column('unionId');
		// 获取所有用户的OBD设备号
		$data = Db::table('cb_user')
				->where('status', 1)
				->whereIn('unionId',$unionId)
				->where('eq_num','<>',0)
				->where('eq_num','like','%'.$search.'%')
				->column('eq_num');
		return $data;
	}


	/**
	 * 构造数组
	 * @return [type] [description]
	 */
	private function arrData($data)
	{
		$obdid = array_column($data['Rows'],'_obdid');
		// 查询所有设备号关联的用户信息
		$list = Db::table('cb_user ub')
				->join('co_car_cate cc','ub.car_cate_id = cc.id')
				->whereIn('ub.eq_num',$obdid)
				->field('cc.type,ub.name,ub.phone,ub.plate,eq_num')
				->select();
		// 构造用户信息和故障预警的信息为一个数组
		foreach ($data['Rows'] as $k => $v) {
			
			foreach ($list as $ke => $va) {
				if($v['_obdid'] == $va['eq_num']){
					$data['Rows'][$k]['type'] = $va['type'];
					$data['Rows'][$k]['name'] = $va['name'];
					$data['Rows'][$k]['phone'] = $va['phone'];
					$data['Rows'][$k]['plate'] = $va['plate'];		
				}
			}

			// 判断是否有字段title  因此方法是公用方法，所以需要判断是否为空。有的接口没有title这个字段会报错
			if(isset($v['_title'])){
				if($v['_title'] == null){
					unset($data['Rows'][$k]);
				}
			}
			
		}
		$data['count'] = $data['total'];
		$data['total'] = ceil($data['total']/8);
		return $data;
	}



	/**
	 * 接口公共的相同参数
	 * @param  [type] $page [description]
	 * @return [type]       [description]
	 */
	private function interArr($page)
	{
		return $data= [
			'beginDate' => date("Y-m-d",strtotime("-7 day")),
			'endDate' => date("Y-m-d"),
			'pageSize' => 8,
			'pageNum' => $page,
		];
	}



  	
  	// /**
  	//  * 搜索 已经用不到
  	//  * @param  [type] $search [搜索关键字]
  	//  * @return [type]         [description]
  	//  */
  	// private function search($type,$search='',$start_time='',$end_time = '')
  	// {
  	// 	if(!empty($search) && !empty($start_time) && !empty($end_time)){

  	// 		// 搜索该时间所有obdid
			// $time = Db::table('cs_sto_time')
			// 	->whereTime('create_time', 'between', [$start_time, $end_time])
			// 	->column('obd');
			// // 所有该名称或obd的id
			// $eq_num = $this->eqNum($search);
			// foreach ($time as $k => $v) {
			// 	foreach ($eq_num as $ke => $va) {
			// 		if($v == $va){
			// 			$arr[] = $va;
			// 		}
			// 	}
			// }

			// if(isset($arr)){
			// 	return $arr;
			// }else{
			// 	$this->result('',0,'没有符合条件的信息');
			// }

  	// 	}else if(!empty($start_time) && !empty($end_time)){

  	// 		$start_time = date('Y-m-d 01:00:00',strtotime($start_time));
  	// 		$end_time = date('Y-m-d 24:00:00',strtotime($end_time));
  	// 		// 搜索该时间内的所有obdid
			// $arr = Db::table('cs_sto_time')
			// 	->whereTime('create_time', 'between', [$start_time, $end_time])
			// 	->where('type',$type)
			// 	->column('obd');
  	// 	}else{
  	// 		// 名称或者obdid
			// $arr = $this->eqNum($search);
  	// 	}
  		
  	// 	return $arr;
  	// }

}
