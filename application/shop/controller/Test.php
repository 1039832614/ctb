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
	public function a()
	{
		$a = 1;
		if($a ==true)
		$a = [
			'aid' => 1,
			'bind' => 2,
		];
		$b = [
			'aid' => 1,
			'bind' => 2
		];
		if($a===$b)
		{
			return 1;
		} else {
			return 2;
		}
	}
	public function b()
	{
		$uid = input('get.uid')?:die('缺少uid');
		$sum = DB::table('u_member_table')
    	       ->where([
    	       	        'uid' => $uid,
    	       	        'pay_status' => 1,
    	                ])
    	       ->sum('pay_time');
    	return $sum ? : 0;
	}
}