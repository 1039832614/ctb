<?php 
namespace app\shop\controller;
use app\base\controller\Shop;
use think\Db;

/**
 * 汽修厂建议反馈
 */
class Suggest extends Shop
{
	/**
	 * 进程初始化
	 */
	public function initialize()
	{
		parent::initialize();
	}


	/**
	 * 添加反馈
	 * 新版本维修厂使用
	 */
	public function add()
	{
		$data = input('post.');
		// 实例化验证
		$validate = validate('Suggest');	
		if($validate->check($data)){
			$info = Db::table('cs_shop')
				->alias('c')
				->join(['cs_shop_set'=>'s'],'c.id = s.sid')
				->field('company,phone,province,city,county')
				->where('c.id',$this->sid)
				->find();
			// 构建插入数据
			$arr = [
				'company' => $info['company'],
				'phone' => $info['phone'],
				'title' => $data['title'],
				'content' => $data['content'],
				'owner' => 1,
				'address' => $info['province'].$info['city'].$info['county']
			];
			// 进行数据插入
			$add = Db::table('co_feed_back')->insert($arr);
			if($add){
				$this->result('',1,'提交成功，感谢您的反馈');
			}else{
				$this->result('',0,'提交失败，请重新提交');
			}
		}else{
			$this->result('',0,$validate->getError());
		}
	}
}