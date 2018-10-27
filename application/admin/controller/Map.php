<?php
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;
/**
* 地图
*/
class Map extends Admin
{
	// /**
	//  * 地图一级页面
	//  * @return [type] [description]
	//  */
	// public function index()
	// {
	// 	// 获取每个省得名称id
	// 	$map = Db::table('co_china_data')->where('pid',1)->field('id,name')->select();
	// 	if($map){
	// 		$this->result($map,1,'获取省成功！');
	// 	}else{
	// 		$this->result('',0,'获取省失败！');
	// 	}
	// }


	// /** 
	//  *鼠标划入省显示该省数据（市级代理、运营商、维修厂、车主、参与次数平均值、售卡平均值）
	//  * @return [type] [description]
	//  */
	// public function deliNum($province)
	// {
	// 	// 查询市级代理数量
	// 	$agent = Db::table('cg_supply')->where('province','like',$province.'%')->count();
	// 	// 查询运营商数量
	// 	$operator = Db::table('ca_agent')->where('province','like',$province.'%')->count();
	// 	// 查询维修厂数量
	// 	$shop = $this->shopNum($province);
	// 	// 该省售卡平均值（包含复购）
	// 	$cardAvera = $this->cardNum($province) == 0 ? 0 : ceil($this->cardNum($province) / $this->shopNum($province));
	// 	$cardPrice = $this->cardPrice($province) == 0 ? 0 : ceil($this->cardPrice($province) / $this->shopNum($province));
	// 	// 该省车主数量
	// 	//获取该省维修厂的所有id
	// 	$ids = $this->shopId($province);
	// 	$user = Db::table('u_card')->whereIn('sid',$ids)->count();
	// 	$list = [
	// 		'agent'=>$agent,
	// 		'operator'=>$operator,
	// 		'shop'=>$shop,
	// 		'cardAvera'=>$cardAvera,
	// 		'cardPrice'=>$cardPrice,
	// 		'user'=>$user,
	// 	];
	// 	$this->result($list,1,'获取省数据成功');
	// }



	public function deliNum()
	{
		$map = Db::table('co_china_data')->where(['pid'=>1])->field('id,name')->select();
		foreach ($map as $k => $v) {
			//查询市级代理数量
			$agent = $this->mun($v['id']);
			// 查询运营商数量
			$operator = $this->agent($v['id']);
			// 查询维修厂数量
			$shop = $this->shopNum($v['name']);
			// 该省售卡平均值（包含复购）
			$cardAvera = $this->cardNum($v['name']) == 0 ? 0 : ceil($this->cardNum($v['name']) / $this->shopNum($v['name']));
			$cardPrice = $this->cardPrice($v['name']) == 0 ? 0 : ceil($this->cardPrice($v['name']) / $this->shopNum($v['name']));
			//获取该省维修厂的所有id
			$ids = $this->shopId($v['name']);
			$user = Db::table('u_card')->whereIn('sid',$ids)->count();
			$list = [
				'id'=>$v['id'],
				'province'=>$v['name'],
				'agent'=>$agent,
				'operator'=>$operator,
				'shop'=>$shop,
				'cardAvera'=>$cardAvera,
				'cardPrice'=>$cardPrice,
				'user'=>$user,
			];
			$data[] = $list;
		}

		$this->result($data,1,'获取省数据成功');
	}


	/**
	 * 市级代理数量
	 * id 省id
	 * @return [type] [description]
	 */
	public function mun($id)
	{
		// 查询该省的市级id
		$city = Db::table('co_china_data')->where('pid',$id)->column('id');
		// 查询有多少市级代理
		$count = Db::table('cg_area')->whereIn('area',$city)->count();
		return $count;

	}


	/**
	 * 运营商数量
	 * id 省id
	 * @return [type] [description]
	 */
	public function agent($id)
	{
		// 查询该省的市级id
		$city = Db::table('co_china_data')->where('pid',$id)->column('id');
		// 查询该该市的县区id
		$county = Db::table('co_china_data')->whereIn('pid',$city)->column('id');
		$count = Db::table('ca_area')->whereIn('area',$county)->count();
		return $count;

	}




	/**
	 * 获取该省交易总额按月
	 */
	public function total($province)
	{
		//获取该省维修厂的所有id
		$ids = $this->shopId($province);
		if(empty($ids)) return 0;
		$total = Db::table('u_card')
				->whereIn('sid',$ids)
				->where("DATE_FORMAT(sale_time,'%Y')=Year(CurDate())")
				->where('pay_status',1)
				->field("DATE_FORMAT(sale_time,'%c') as month,sum(card_price) as price")
				->group('month')
				->select();
		$total = Mfile($total,'price');
		if($total){
			$this->result($total,1,'获取交易总额成功！');
		}else{
			$this->result(0,0,'暂无交易金额');
		};
	}


	/**
	 * 该省售卡总数按月
	 * @return [type] [description]
	 */
	public function card($province)
	{
		//获取该省维修厂的所有id
		$ids = $this->shopId($province);
		if(empty($ids)) return 0;
		// 获取售卡总数
		$card = Db::table('u_card')
				->whereIn('sid',$ids)
				->where("DATE_FORMAT(sale_time,'%Y')=Year(CurDate())")
				->where('pay_status',1)
				->field("DATE_FORMAT(sale_time,'%c') as month,count(id) as count")
				->group('month')
				->select();
		$card = Mfile($card,'count');
		if($card) $this->result($card,1,'获取售卡总数成功');
		$this->result(0,0,'暂无售卡');

	}


	/**
	 * 该省总服务次数  按月
	 * @return [type] [description]
	 */
	public function service($province)
	{
		//获取该省维修厂的所有id
		$ids = $this->shopId($province);
		if(empty($ids)) $this->result(0,0,'暂无服务次数');
		// 获取服务次数（按月）
		$service = Db::table('cs_income')
					->whereIn('sid',$ids)
					->where("DATE_FORMAT(create_time,'%Y')=Year(CurDate())")
					->field("DATE_FORMAT(create_time,'%c') as month,count(id) as count")
					->group('month')
					->select();
		$service = Mfile($service,'count');
		if($service) $this->result($service,1,'获取服务次数成功');
		$this->result(0,0,'暂无服务次数');
	}

	/**
	 * 好评次数
	 * @param string $province [省名称]
	 */
	public function praiseNum($province)
	{
		//获取该省维修厂的所有id
		$ids = $this->shopId($province);
		if(empty($ids)) $this->result(0,0,'暂无好评！');
		// 获取该省好评奖励（按月）
		$comment = Db::table('u_comment')
					->alias('uc')
					->whereIn('sid',$ids)
					->where("DATE_FORMAT(create_time,'%Y')=Year(CurDate())")
					->field("DATE_FORMAT(create_time,'%c') as month,count(id) as num")
					->group('month')
					->select();
		$comment = Mfile($comment,'num');
		if($comment) $this->result($comment,1,'获取好评成功');
		$this->result(0,0,'暂无好评');
		
	}


	/**
	 * 物料消耗（根据做邦保养升数计算）
	 * @return [type] [description]
	 * @param string $id [省id]
	 */
	public function material($id)
	{
		// 查询地区表 获取该省下属的所有市id
		$area = Db::table('co_china_data')->where('pid',$id)->column('id');
		// 根据市id 查询该省所有市级代理id
		$city = Db::table('cg_area')->whereIn('area',$area)->column('gid');
		// print_r($city);exit;
		if(!isset($city)) $this->result(0,0,'暂无消耗');
		// 查询市级代理的物料消耗
		$list = Db::table('cg_apply_materiel')
				->whereIn('gid',$city)
				->json(['detail'])
				->field("DATE_FORMAT(create_time,'%c') as month,detail")
				// ->group('month')
				->where('audit_status',1)
				->select();
				
		$data = $this->EarRebort($list);
		// print_r($data);exit;
		// $list = Mfile($list,'size');
		if($data) $this->result($data,1,'获取物料消耗成功');
		$this->result(0,0,'暂无消耗');
	}


	public function EarRebort($data)
    {
       
        $array = [];

        // 重构数组
        // print_r($data);exit;

        foreach ($data as $key => $value) {
             if(!empty($value['detail'])){
               foreach ($value['detail'] as $k => $v) {
                
                    if(!isset($array[$value['month']]))
                    {
                      $array[$value['month']] = 0;
                    }
                     $array[$value['month']] += $v['num'];
                
                }            
             }
        }

        return $this->Mfiles($array,'month');    

    }


    public function Mfiles($arr,$comm)
    {

       $ids = [1,2,3,4,5,6,7,8,9,10,11,12];
   
       // 查找 未配给的物料  填充为0
       foreach ($arr as $key => $value) {
          foreach ($ids as $k => $v) {
              if(!isset($arr[$v]))
              {  
                   $arr[$v] = 0;
              }
          }
       } 
       ksort($arr);
       $arr = array_values($arr);
       return $arr;       
    } 

	/**
	 * 该省复购次数
	 * @param  [type] $province [省名称]
	 * @return [type]           [description]
	 */
	public function repeatTime($province)
	{
		//获取该省维修厂的所有id
		$ids = $this->shopId($province);
		// print_r($ids);exit;
		if(empty($ids)) $this->result(0,0,'暂无复购');
		$reptime = Db::table('u_card')
					->whereIn('sid',$ids)
					->where("DATE_FORMAT(sale_time,'%Y')=Year(CurDate())")
					->field("DATE_FORMAT(sale_time,'%c') as month,(count(uid)-count(distinct uid)) as num")
					->where('pay_status',1)
					->group('month')
					->select();
		$reptime = Mfile($reptime,'num');
		if($reptime) $this->result($reptime,1,'获取复购次数成功');
		$this->result(0,0,'暂无复购');

	}


	/**
	 * 市级代理售卡平均
	 * @param  [type] $province [省名称]
	 * @param  [type] $pro_id   [省id]
	 * @return [type]           [description]
	 */
	public function munCard($province,$pro_id)
	{
		$card = $this->averCard($province,$pro_id,'cg_area');

		if($card) $this->result($card,1,'获取售卡平均值成功');
		$this->result(0,0,'暂无售卡');
	}


	/**
	 * 运营商售卡平均
	 * @param  [type] $province [省名称]
	 * @param  [type] $pro_id   [省id]
	 * @return [type]           [description]
	 */
	public function agentCard($province,$pro_id)
	{
		$city = Db::table('co_china_data')->where('pid',$pro_id)->column('id');
		$card = $this->averCard($province,$city,'ca_area');

		if($card) $this->result($card,1,'获取售卡平均值成功');
		$this->result(0,0,'暂无售卡');
	}

	/**
	 * 维修厂售卡平均
	 * @param  [type] $province [省名称]
	 * @param  [type] $pro_id   [省id]
	 * @return [type]           [description]
	 */
	public function shopCard($province,$pro_id)
	{
		$card = $this->averCard($province,$pro_id);

		if($card) $this->result($card,1,'获取售卡平均值成功');
		$this->result(0,0,'暂无售卡');
	}

	/**
	 * 市级代理售卡金额平均值
	 * @param  [type] $province [省名称]
	 * @param  [type] $pro_id   [省id]
	 * @return [type]           [description]
	 */
	public function munPrice($province,$pro_id)
	{
		// 形参 省名称，省id,市级代理供货地区表
		$total = $this->averPrice($province,$pro_id,'cg_area');
		if($total){
			$this->result($total,1,'获取交易平均值成功！');
		}else{
			$this->result(0,0,'暂无交易金额');
		}

	}


	/**
	 * 运营商售卡金额平均值
	 * @param  [type] $province [省名称]
	 * @param  [type] $pro_id   [省id]
	 * @return [type]           [description]
	 */
	public function agentPrice($province,$pro_id)
	{
		$city = Db::table('co_china_data')->where('pid',$pro_id)->column('id');
		// 形参 省名称，省id,市级代理供货地区表
		$total = $this->averPrice($province,$city,'ca_area');
		if($total){
			$this->result($total,1,'获取交易平均值成功！');
		}else{
			$this->result(0,0,'暂无交易金额');
		}

	}


	/**
	 * 维修厂售卡金额平均值
	 * @param  [type] $province [省名称]
	 * @param  [type] $pro_id   [省id]
	 * @return [type]           [description]
	 */
	public function shopPrice($province,$pro_id)
	{
		// 形参 省名称，省id,市级代理供货地区表
		$total = $this->averPrice($province,$pro_id);
		if($total){
			$this->result($total,1,'获取交易平均值成功！');
		}else{
			$this->result(0,0,'暂无交易金额');
		}

	}


	/**
	 * 市级代理售卡平均
	 * @param  [type] $province [省名称]
	 * @return [type]           [description]
	 */
	private function averCard($province,$pro_id,$table = '')
	{
		//获取该省维修厂的所有id
		$ids = $this->shopId($province);
		if(empty($ids)) return 0;
		// 查询该省所有市级代理
		if(empty($table)){
			$count = count($ids);
		}else{
			$count = Db::table($table)
				->alias('ga')
				->join('co_china_data cd','ga.area = cd.id')
				->whereIn('pid',$pro_id)
				->count();
		}
		// 获取售卡平均每月总数
		$card = Db::table('u_card')
				->whereIn('sid',$ids)
				->where("DATE_FORMAT(sale_time,'%Y')=Year(CurDate())")
				->where('pay_status',1)
				->field("DATE_FORMAT(sale_time,'%c') as month,(count(id) / ".$count.") as num")
				->group('month')
				->select();
		return Mfile($card,'num');

	}


	/**
	 * 售卡金额平均
	 * @param  [type] $province [省名称]
	 * @return [type]           [description]
	 */
	private function averPrice($province,$pro_id,$table = '')
	{
		//获取该省维修厂的所有id
		$ids = $this->shopId($province);
		if(empty($ids)) return 0;
		// 查询该省所有市级代理、运营商、维修厂
		if(empty($table)){
			$count = count($ids);
		}else{
			$count = Db::table($table)
				->alias('ga')
				->join('co_china_data cd','ga.area = cd.id')
				->whereIn('pid',$pro_id)
				->count();
		}
		$total = Db::table('u_card')
				->whereIn('sid',$ids)
				->where("DATE_FORMAT(sale_time,'%Y')=Year(CurDate())")
				->where('pay_status',1)
				->field("DATE_FORMAT(sale_time,'%c') as month,(sum(card_price) / ".$count.") as price")
				->group('month')
				->select();
		// print_r($total);exit;
		return Mfile($total,'price');
		
	}

	
	/**
	 * [全省维修厂数量]
	 * @param  [type] $province [省名称]
	 * @return [type]           [description]
	 */
	private function shopNum($province)
	{
		return Db::table('cs_shop_set')->where('province',$province)->count();

	}

	/**
	 * 该省总售卡数
	 * @param  string $value [description]
	 * @return [type]        [description]
	 */
	private function cardNum($province)
	{
		// 获取该省的售卡总数
		$list =Db::table('cs_shop_set')
				->alias('ss')
			   	->join(['cs_shop'=>'cs'],'ss.sid = cs.id')
				->where('ss.province',$province)
				->sum('card_sale_num');
		// echo $list;
		return $list;
	}


	/**
	 * 该省售卡总金额
	 * @return [type] [description]
	 */
	private function cardPrice($province)
	{
		// 获取该省的所有维修厂id
		$ids = $this->shopId($province);
		if(empty($ids)){
			return 0;
		}
		// 获取该省所有维修厂的售卡总额
		return Db::table('u_card')->whereIn('sid',$ids)->where('pay_status',1)->sum('card_price');
	}


	/**
	 * 获取该省所有维修厂id
	 * @return [type] [description]
	 */
	private function shopId($province)
	{
		// 获取该省的所有维修厂id
		return Db::table('cs_shop_set')->where('province',$province)->column('sid');
	}
}