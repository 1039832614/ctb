<?php
namespace app\agent\controller;
use  app\base\controller\Agent;
use think\Db;
/**
* 运营商反馈
*/
class FeedBack extends Agent
{

	// /**
	//  * 点击反馈获取运营商公司名称，电话，省市县地址
	//  * @return [type] [description]
	//  */
	// public function index()
	// {
	// 	//获取运营商信息
	// 	$data = Db::table('ca_agent')->where('aid',$this->aid)->field('company,phone,province,city,county')->find();
	// 	if($data){
	// 		$this->result($data,1,'获取运营商信息成功');
	// 	}else{
	// 		$this->result('',0,'获取运营商信息失败');
	// 	}
	// }

	/**
	 * 运营商反馈
	 * @return [type] [description]
	 */
	public function feedBack()
	{
		// 获取运营商公司名称、电话、地址、反馈标题、反馈内容
		$data = input('post.');
		$validate = validate('feedBack');
		$arr = Db::table('ca_agent')->where('aid',$this->aid)->field('company,phone,province,city,county,address')->find();
		if($validate->check($data)){
			$data = [
				'company' => $arr['company'],
				'phone'	  => $arr['phone'],
				'address' => $arr['province'].$arr['city'].$arr['county'].$arr['address'],
				'title'	  => $data['title'],
				'content' => $data['content'],
				'owner'	  => 2,	 
			];
			$res = Db::table('co_feed_back')->strict(false)->insert($data);
			if($res){
				$this->result('',1,'反馈成功');
			}else{
				$this->result('',0,'反馈失败');
			}
		}else{
			$this->result('',0,$validate->getError());
		}
	}

}