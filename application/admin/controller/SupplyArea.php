<?php
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;
/**
* 市级代理添加区域
*/
class SupplyArea extends Admin
{
	/**
	 * 添加区域 申请列表
	 * @param  [type] $page [description]
	 * @return [type]       [description]
	 */
	public function appList($page)
	{
		$list = $this->index($page,0);
		if($list) $this->result($list,1,'获取申请列表成功');
		$this->result('',0,'暂无数据');
	}


	/**
	 * [添加区域 通过列表]
	 * @param  [type] $page [description]
	 * @return [type]       [description]
	 */
	public function adList($page)
	{
		$list = $this->index($page,1);
		if($list) $this->result($list,1,'获取通过列表成功');
		$this->result('',0,'暂无数据');
	}


	/**
	 * [添加区域 驳回列表]
	 * @return [type] [description]
	 */
	public function rejList($page)
	{
		$list = $this->index($page,3);
		// echo Db::table('cg_increase')->getLastSql();exit;
		if($list) $this->result($list,1,'获取驳回列表成功');
		$this->result('',0,'暂无数据');
	}

	/**
	 * 获取地区详情
	 * @param  [type] $id [添加地区订单id]
	 * @return [type]     [description]
	 */
	public function region($id)
	{
		$area = Db::table('cg_increase')->where('id',$id)->value('area');
		// 获取该市的省id
		$province = Db::table('co_china_data')->whereIn('id',$area)->column('pid');
		$province = implode(',',$province);
		// 查询所有省市县的数据
        $list=Db::table('co_china_data')->whereIn('id',$province.','.$area)->where('pid','>',0)->field('name,pid,id')->select();
        if($list){
            // 把数据换成树状结构
            $this->result(get_child($list,$list[0]['pid']),1,'获取地区成功');
        }else{  
        	$this->result('',0,'暂未设置地区');
        }

	}

	/**
	 * 添加区域查看详情
	 * @param [type] $id [地区订单id]
	 */
	public function addList($id)
	{
		$list = Db::table('cg_increase')
				->alias('gi')
				->join('cg_supply gs','gi.gid = gs.gid')
				->where('id',$id)
				->field('company,phone,gi.price,gi.regions,voucher,gi.id,gi.gid')
				->find();
		// print_r($list);exit;
		if($list) $this->result($list,1,'获取详情成功');
		$this->result('',0,'暂无数据');

	}

	/**
	 * 物料详情
	 * @param  [type] $id [订单id]
	 * @return [type]     [description]
	 */
	public function materiel($id)
	{
		$list = Db::table('cg_increase')->where('id',$id)->json(['details'])->find();
		foreach ($list['details'] as $k => $v){
			$name = Db::table('co_bang_cate')->where('id',$v['materiel_id'])->value('name');
			$list['details'][$k]['materiel'] = $name;
		}
		if($list){
			$this->result($list['details'],1,'获取物料详情成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}

	// /**
	//  * 查看驳回理由
	//  * @return [type] [description]
	//  */
	// public function reaDetail($id)
	// {
	// 	$list = Db::table('cg_increase')->where('id',$id)->value('reason');
	// 	if($list){
	// 		$this->result($list,1,'获取驳回理由成功');
	// 	}else{
	// 		$this->result('',0,'暂无数据');
	// 	}
	// }


	/**
	 * [添加区域通过操作]
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function adopt()
	{	
		// 获取 订单id  延迟罚款delay_fines   授信金额credit  联系人name  电话 iphone 区域个数 region 
		// 市级代理id gid
		Db::startTrans();
		$data = input('post.');
		// print_r($data);exit;
		if(empty($data['id']) && empty($data['delay_fines'])) $this->result('',0,'参数有误');
		$data['audit_time'] = time();
		$data['audit_status'] = 1;
		// 获取配给的数量
		$detail = $this->firstData($data['gid'],$data['regions']);
		// print_r($detail);exit;
		$b = $this->arr($detail);
		// 构造入库的物料详情字段
		$data['details'] = $b;
		// 修改cg_supply 表审核时间
		Db::table('cg_supply')->where('gid',$data['gid'])->update(['audit_time'=>time()]);
		// 增加市区代理库存
		// 查询市区代理 有无进行过库存添加
		$if_add = Db::table('cg_stock')->where('gid',$data['gid'])->count();
		// 进行过库存添加 则直接增加库存
		if($if_add > 0){
			// 增加库存及期初配给
			// 大于0 为提高配给   直接修改运营商的库存
			foreach ($this->bangCate() as $k => $v) {
				$ress = Db::table('cg_stock')
					->where(['gid'=>$data['gid'],'materiel'=>$v['id']])
					->inc('ration',$v['def_num']*15*$data['regions'])
					->inc('materiel_stock',$v['def_num']*15*$data['regions'])
					->inc('open_stock',$v['def_num']*15*$data['regions'])
					->update();
			}
		}else{
			// 如果小于等于0则表示第一次配给运营商库存插入数据 
			$arr = $this->firstData($data['gid'],$data['regions']);
			$ress = Db::table('cg_stock')->insertAll($arr);
		}

		if($ress || $ress !== false){
			//把运营商此次的支付金额和可开通修车厂数量增加到运营商表
			$result = Db::table('cg_supply')
					->where('gid',$data['gid'])
					->inc('deposit',$data['regions']*105000)
					->inc('regions',$data['regions'])
					->update();
			unset($data['token']);
			// 获取管理员名称
			$admin = Db::table('am_auth_user')->where('uid',$this->admin_id)->value('uname');
			$data['audit_person'] = $admin;
			// 修改添加区域表数据
			$res = Db::table('cg_increase')->where('id',$data['id'])->json(['details'])->update($data);
			if($result !== false && $res !== false && $ress !== false ){
				Db::commit(); 
				$this->result('',1,'操作成功');
			}else{
				Db::rollback();
				$this->result('',0,'操作失败');
			}
			
		}
	}

	/**
	 * 驳回
	 * @param  [type] $id     [订单id]
	 * @param  [type] $reason [驳回理由]
	 * @return [type]         [description]
	 */
	public function reject($id,$reason)
	{
		$data = [
			'audit_time' => time(),
			'audit_status'=>3,
			'reason'=>$reason,
		];
		$res = Db::table('cg_increase')->where('id',$id)->update($data);
		$gid = Db::table('cg_increase')->where('id',$id)->value('gid');
		// 删除市级代理所选择的地区
		$result = Db::table('cg_area')->where('gid',$gid)->delete();
		if($res !== false) $this->result('',1,'驳回操作成功');
		$this->result('',0,'操作失败');
	}


	/**
	 * 区域列表
	 * @param  [type] $page   [当前页]
	 * @param  [type] $status [状态]
	 * @return [type]         [description]
	 */
	private function index($page,$status)
	{
		$pageSize = 10;
		$count = Db::table('cg_increase')->where('audit_status',$status)->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('cg_increase')
				->alias('gi')
				->join('cg_supply gs','gi.gid = gs.gid')
				->field('company,gs.phone,leader,price,gi.regions,gi.create_time,gi.id,gi.audit_time,reason,gs.license')
				->where('gi.audit_status',$status)
				->order('gi.id desc')
				->page($page,$pageSize)
				->select();
			
		if($count > 0) return ['list'=>$list,'rows'=>$rows];
	}

	/**
	 * 第一次配给所用数组
	 * @return [type] [description]
	 */
	private function firstData($gid,$region)
	{
		foreach ($this->bangCate() as $k => $v) {
				$arr[]=[
					'gid'=>$gid,
					'materiel'=>$v['id'],
					'ration'=>$v['def_num']*15*$region,
					'warning'=>ceil($v['def_num']*15*$region*20/100),
					'materiel_stock'=>$v['def_num']*15*$region,
				];
		}
		return $arr;

	}

	/**
	 * 构造配给所用的json串
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	private function arr($data)
	{
		foreach ($data as $k => $v) {
			if($v['materiel'] == 7){
				// 构造入库的物料详情字段
				$arr =  [
					'materiel_id' =>$v['materiel'],
					'ration' => $v['ration'],
					'warning' => $v['warning'],
					'materiel_stock'=>$v['materiel_stock'],
					'num' => $v['ration'],
				];
			}else{
				$arr =  [
					'materiel_id' =>$v['materiel'],
					'ration' => $v['ration'],
					'warning' => $v['warning'],
					'materiel_stock'=>$v['materiel_stock'],
					'num' => $v['ration']/12,
				];
			}
		
			$b[] = $arr;
		}
		return $b;
	}



}
