<?php
namespace app\admin\controller;
use app\base\controller\Base;
use think\Db;
/**
* 总后台审核运营商物料后三天自动入库
*/
class SupplyTime extends Base
{
	
	public function index()
	{
		// 获取市级代理gid  物料详情   物料审核通过时间
		$list = Db::table('cg_company')->where(['status'=>1])->field('id,gid,to_time,details')->json(['details'])->select();
		// print_r($list);exit;
		foreach ($list as $k => $v) {
			// $a = strtotime('+1 day',$v['to_time']);
				// echo $a;exit;
				// echo date('Y-m-d',$a);exit;
			if(time() > strtotime('+1 day',$v['to_time'])){
				foreach ($v['details'] as $ke => $va) {
					// print_r($v['details']);exit;
					$res = Db::table('cg_stock')
						->where(['materiel'=>$va['id'],'gid'=>$v['gid']])
						->setInc('materiel_stock',$va['size']*12);
					$result = Db::table('cg_company')->where('id',$v['id'])->setField('status',4);
				}
			}else{
				return true;
			}
		}
		
	}
}