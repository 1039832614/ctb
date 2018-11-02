<?php 
namespace app\shop\controller;
use think\Controller;
use think\Db;

class Test extends Controller
{
	public function index()
	{
		// $ids = Db::table('test')->where('uid',8)->where('status',0)->column('id');
		// if(count($ids) > 0){
		// 	return Db::table('test')->where('id',$ids[0])->setField('status',1);
		// }
		$ex_count = Db::table('cs_gift')->field('id,excode')->where('sid',8)->where('status',1)->select();
		return $ex_count[0]['excode'];
	}
	public function admin(){
		$sid = 31;
		$list = Db::table('cs_apply_materiel')
				->where('sid',$sid)
				->json(['detail'])
				->where('audit_status',2)
				->field("detail,FROM_UNIXTIME(audit_time)")
				->select();
		return $list;
	}
}