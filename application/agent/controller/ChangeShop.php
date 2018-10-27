<?php 
namespace app\agent\controller;
use app\base\controller\Agent;
use think\File;
use think\Db;
/**
* 
*/
class ChangeShop extends Agent
{
	public function Index()
	{
		$page = input('page')? :1;
		$pageSize = 10;
		$count = Db::table('u_ex_shop')->where('extype',1)->count();
		$rows =	ceil($count / $pageSize);
		$list = Db::table('u_ex_shop es')
				->join('u_user uu','es.uid = uu.id')
				->where(['extype'=>1,'aid'=>$this->aid])
				->field('uu.name,uu.phone,es.card_number,es.old_shop,es.reason,es.create_time,new_shop')
				->page($page,$pageSize)
				->order('uid desc')
				->select();
		if($count > 0){
			$this->result(['rows'=>$rows,'list'=>$list],1,'获取换店列表成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}
}