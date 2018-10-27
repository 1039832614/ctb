<?php 
namespace app\shop\controller;
use app\base\controller\Shop;
use think\Db;
use think\File;

/**
 * 汽修厂获取系统消息
 */
class Service extends Shop
{
	/**
	 * 进程初始化
	 */
	public function initialize()
	{
		parent::initialize();
	}


	/**
	 * 添加服务项目
	 */
	// public function addItem()
	// {
	// 	$data = input('post.');
	// 	// 实例化验证
	// 	$validate = validate('Service');
	// 	// 构建插入数据
	// 	$arr = [
	// 		'sid' => $this->sid,
	// 		'service' => $data['service'],
	// 		'cover' => $data['cover'],
	// 		'price' => $data['price'],
	// 		'about' => $data['about']
	// 	];
	// 	// 如果验证通过则进行邦保养操作
	// 	if($validate->check($arr)){
	// 		if(Db::table('cs_service')->insert($arr)){
	// 			$this->result('',1,'提交成功');
	// 		}else{
	// 			$this->result('',0,'提交失败');
	// 		}
	// 	}else{
	// 		$this->result('',0,$validate->getError());
	// 	}
	// }

	/**
	 * 获取服务项目
	 */
	public function getItem()
	{
		$info = Db::table('cs_service')->where('sid',$this->sid)->field('service,cover,price,about')->find();
		if($info){
			$this->result($info,1,'获取消息成功');
		}else{
			$this->result('',1,'暂无信息');
		}
	}

	/**
	 * 修改服务项目
	 */
	public function setItem()
	{
		$data = input('post.');
		// 构建插入数据
		$arr = [
			'service' => $data['service'],
			'cover' => $data['cover'],
			'price' => $data['price'],
			'about' => $data['about']
		];
		// 检测是否存在该数据
		$count = Db::table('cs_service')->where('sid',$this->sid)->count();
		// 实例化验证
		$validate = validate('Service');
		// 如果验证通过则进行邦保养操作
		if($validate->check($arr)){
			if($count > 0){
				$res = Db::table('cs_service')->where('sid',$this->sid)->update($arr);
			}else{
				$arr['sid'] = $this->sid;
				$res = Db::table('cs_service')->insert($arr);
			}
			if( $res && $res !== false){
				$this->result('',1,'保存成功');
			}else{
				$this->result('',0,'保存失败');
			}
		}else{
			$this->result('',0,$validate->getError());
		}
	}

	/**
	 * 配图上传
	 */
	public function uploadImg()
	{
		return upload('file','shop/service','https://cc.ctbls.com');
	}


}