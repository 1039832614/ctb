<?php
namespace  app\agent\controller;
use app\base\controller\Agent;
use think\Db;
/**
* 运营商的物料库存
*/
class MaterialAgent extends Agent
{


	/**
	 * 物料申请未审核列表
	 * @return [type] [description]
	 */
	public function waitList()
	{
		$page = input('post.page')? : 1;
		$this->list(0,$page);		
	}
	/**
	 * 物料申请已通过列表
	 * @return [type] [description]
	 */
	public function auditList()
	{
		$page = input('post.page')? : 1;
		$this->list(1,$page);		
	}
	/**
	 * 物料申请驳回列表
	 * @return [type] [description]
	 */
	public function rejectList()
	{
		$page = input('post.page')? : 1;
		$this->list(2,$page);		
	}
	/**
	 * 物料申请的物料详情
	 * @return [type] [description]
	 */
	public function applyDetail()
	{
		$data = input('post.');
		$data['table'] = input('post.table')? : 1; 
		if($data['table'] == 2){
			//查cg_apply_materiel表
			$list = Db::table('cg_apply_materiel')
				     ->where('id',$data['id'])
				     ->json(['detail'])
				     ->find();
		} else {
			//查ca_apply_materiel表
			$list = Db::table('ca_apply_materiel')
				->where('id',$data['id'])
				->json(['detail'])
				->find();
		}
		$data = $list['detail'];
		if($list){
			$this->result($data,1,'获取详情成功');
		}else{
			$this->result('',1,'获取详情失败');
		}

	}

	/**
	 * 运营商总库存列表
	 * @return json  物料名称、物料库存剩余量
	 */
	public function index()
	{
		$page = input('post.page')? : 1;
		$pageSize = 10;
		$count = Db::table('ca_ration')
					->where('aid',$this->aid)
					->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('ca_ration ar')
				->join('co_bang_cate bc','ar.materiel=bc.id')
				->field('name,materiel_stock,open_stock')
				->where('aid',$this->aid)
				->page($page,$pageSize)->select();
		if($count > 0){                   
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}
	/**
	 * 运营商库存    （2018.8.28 孙烨兰修改）
	 *   期初配给  剩余库存
	 * @return 
	 */
	public function ration(){
		//剩余库存
    		$list = Db::table('ca_ration ar')
				->join('co_bang_cate bc','ar.materiel=bc.id')
				->field('name,materiel_stock,ration')
				->where('aid',$this->aid)
				->select();
		if($list){
			$this->result($list,'1','获取成功');
		}else{
			$this->result('','0','暂无数据');
		}
    }
    /**
	 * 运营商库存    （2018.8.28 孙烨兰修改）
	 *  期初授信  增加授信  
	 * @return 
	 */
    	public function credit_num(){
    		$list = Db::table('ca_ration a')
				->join('co_bang_cate b','a.materiel=b.id')
				->field('name,ration,open_stock')
				->where('aid',$this->aid)
				->select();
		if($list){
			$this->result($list,'1','获取成功');
		}else{
			$this->result('','0','暂无数据');
		}
    }
	/**
	 * 运营商申请物料操作
	 * @return [json] [成功或失败]
	 */
	public function applyAgent()
	{	
		// 获取物料种类id，获取要申请的总升数，和备注的30/40 油各多少升
		$data = input('post.');
		// 判断这个运营商是否有市级代理
		$gid = Db::table('ca_agent')
				->where('aid',$this->aid)
				->value('gid');
		if($gid !== 0){
			//所在区域有市级代理
			$price = 0;
			$size =0;
			$max =0;
			foreach ($data['materiel_id'] as $k => $v) {
				$arr[] = [
					'materiel_id'=>$v,
					'materiel'=>$data['materiel'][$k],
					'num'=>$data['num'][$k],
					'remarks'=>$data['remarks'][$k]
				];
				//如果上级有市级代理 报错 未定义改字段  2018-10-9cjx 修改 
				//利润 
				// $price += $this->get_price($v)['price']*$data['num'][$k];
				$price += $this->get_price($v)['set_price']*$data['num'][$k];
				//交易总件数
				$size += $data['num'][$k];
				//交易总金额
				$max += ($this->get_price($v)['set_price']+$this->get_price($v)['price'])*$data['num'][$k];
			}
			$ar=[
				'odd_number'=>build_order_sn(),
				'aid'=>$this->aid,
				'gid'=>$gid,
				'detail'=>$arr,
				'price' => $price,
				'size' => $size,
				'max' => $max
			];
			$res = Db::table('cg_apply_materiel')
					->json(['detail'])
					->lock(true)
					->insert($ar);
			if($res) {
				$this->success('',1,'申请成功，请等待市级代理发货');
			} else {
				$this->success('',0,'申请失败');
			}
		} else {
			//所在区域没有市级代理
			$ar = $this->detail($data);
			$res = Db::table('ca_apply_materiel')
					->json(['detail'])
					->lock(true)
					->insert($ar);
			if($res){
				$this->success('',1,'申请成功,请等待总后台发货');
			}else{
				$this->success('',0,'申请失败');
			}
		}
	}
	/**
	 * 获取油的信息
	 * @return [type] [物品id]
	 */
	public function get_price($materiel_id){
		$res = Db::table('co_bang_cate')
				->where('id',$materiel_id)
				->find();
		return $res;
	}
	/**
	 * 获得运营商需要补货的列表
	 * 20180821 11:18 徐佳孟修改
	 * @param  [type] $aid [运营商id]
	 * @return [type]      [description]
	 */
	public function applyIndex()
	{
		$list = Db::table('ca_ration cr')
				->join('co_bang_cate bc','cr.materiel=bc.id')
				->where('aid',$this->aid)
				->where('materiel_stock < ration')
				->select();
		// 判断是否有需要预警的物料
		if($this->ifWarning() == true){
			// 获取运营商需要补货的物料种类，id,物料库存和需要补货的数量
			foreach($list as $k => $v){
				// 当物料是豪华大礼包时
				if($list[$k]['materiel'] == 7) {
					$arr = [
					'materiel' => $v['name'],
					'materiel_stock' => $v['materiel_stock'],
					'apply' => $v['ration'] - $v['materiel_stock'],
					'materiel_id' => $v['id']
					];
					$data[] = $arr;
				} else {
					if($v['ration'] - $v['materiel_stock'] >= 12) {
						$arr = [
						'materiel' => $v['name'],
						'materiel_stock' => $v['materiel_stock'],
						'apply' => floor(($v['ration'] - $v['materiel_stock'])/12),//floor — 舍去法取整(向下取整)
						'materiel_id' => $v['id']
						];
						$data[]=$arr;
					}
				}
			}
			$this->result($data,1,'获取补货列表成功');
		}else{
			$this->result('',0,'当前物料充足无需补充物料');
		}
	}

	/**
	 * 判断是否预警
	 * 20180821 11:13 徐佳孟修改
	 * @param  [type] $data [运营商本身库存的数组列表]
	 * @return [type]       [description]
	 */
	public function ifWarning()
	{
		// 运营商是否处于取消合作状态
		$status = Db::table('ca_agent')
					->where('aid',$this->aid)
					->value('status');
		//运营商的物料库是否小于预警值 
		$list = Db::table('ca_ration')
				->where('aid',$this->aid)
				->select();
		$re = array();
		$a = 0;
		foreach ($list as $key => $value) {
			//当物料为豪华大礼包时，当物料库存小于预警值时，$a 不为 0
			if($list[$key]['materiel'] == 7) {
				if($list[$key]['warning'] - $list[$key]['materiel_stock'] > 0){
					$a = 1;
				} else {
					$a = 0;
				}
			}
			//当物料不是豪华大礼包时
			if($list[$key]['warning'] - $list[$key]['materiel_stock'] > 0){
					if($list[$key]['ration'] - $list[$key]['materiel_stock'] > 12) {
						$re[] = 1;
					} else {
						$re[] = 0;
					}
			} else {
				$re[] = 0;
			}
		}
		array_push($re,$a);//向数组尾部添加一个元素
		$as = in_array(1, $re);//判断数组中是否存在 1
		if($as  && $status !== 6){
			return true;
		}else{
			return false;
		}
	}
	/**
	 * 获取申请物料的详情 20180828 18:18 xjm 
	 * @param  [type] $data [物料id]
	 * @return [type]       [description]
	 */
	private function detail($data)
	{
		foreach ($data['materiel_id'] as $k => $v) {
				$arr[] = [
					'materiel_id' => $v,
					'materiel' => $data['materiel'][$k],
					'num' => $data['num'][$k],
					'remarks' => $data['remarks'][$k]
				];
		}
		$ar=[
			'odd_number'=>build_order_sn(),
			'aid'=>$this->aid,
			'detail'=>$arr,
		];
		return $ar;
	}
	/**
	 * 列表
	 */
	public function List($audit_status,$page){
		$pageSize = 10;
		$list_a = Db::table('ca_apply_materiel')
				->where([
					'aid'=>$this->aid,
					'audit_status'=>$audit_status
				])
				->field('id,odd_number,create_time,audit_person,audit_time,reason,audit_status')
				->select();
		foreach ($list_a as $key => $value) {
			$list_a[$key]['table'] = 1;//在申请物料详情那里，需要用到这个字段
		}
		$list_s = Db::table('cg_apply_materiel')
					->alias('m')
					->join('cg_supply s','s.gid = m.gid')
					->field('m.id,odd_number,m.create_time,s.leader as audit_person,m.audit_time,m.reason,m.audit_status')
					->where([
						'aid'=>$this->aid,
						'm.audit_status'=>$audit_status
					])
					->select();
		foreach ($list_s as $key => $value) {
			$list_s[$key]['table'] = 2;
			//在申请物料详情那里，需要用到这个字段
		}
		$list_z = array_merge($list_s,$list_a);
		$count = count($list_z);
		$list_z = $this->sort($list_z,$count,'create_time');
		$rows = ceil($count/$pageSize);
		$list = array_slice($list_z, ($page-1)*$pageSize,$pageSize);//分页的另外一种方式。
		if($list){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取数据成功');
		} else {
			$this->result('',0,'暂无数据');
		}
	}
	/**
	 * 获取该运营商的地区
	 * @return [type] [description]
	 */
	public function getAgentArea(){
		$area = Db::table('ca_area')
				->alias('a')
				->join('co_china_data d','a.area = d.id')
				->field('d.name')
				->where('aid',$this->aid)
				->select();
		if($area){
			$this->result($area,1,'获取成功');
		} else {
			$this->result('',0,'暂无数据');
		}
	}
}