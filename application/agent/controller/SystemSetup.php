<?php
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;
/**
* 总后台登录
*/
class SystemSetup extends Admin
{

	/**
	 * 轮播图上传
	 * @return 轮播图上传后的路径地址
	 */
	public function uploadPic()
	{
		return upload('image','bby','http://cc.ctbls.com/');
	}

	public function oilList()
	{
		$page = input('post.page') ? :1;
		$pageSize = 4;
		// 查询油品信息
		$count = Db::table('co_bang_cate_about')->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('co_bang_cate_about')
				->field('id,name,cover')
				->order('id desc')
				->page($page,$pageSize)
				->select();
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
		}else{
			$this->result('',0,'暂无数据');
		}

	}


	/**
	 * 设置油品下拉框选择油品名称
	 * @param string $value [description]
	 */
	public function oilName()
	{
		$data = Db::table('co_bang_cate')->field('id,name')->where([['id','<>',1],['id','<>','6']])->select();
		if($data){
			$this->result($data,'1','获取数据成功');
		}else{
			$this->result('',0,'暂无数据');
		}	
	}

	/**
	 * 设置油品数据
	 */
	public function setOil()
	{
		// 油名称、油id、油图片、油简介、油价钱
		$data = input('post.');
		$validate = validate('SetOil');
		if($validate->check($data)){
			$res = Db::table('co_bang_cate_about')->strict(false)->insert($data);
			if($res){
				// 日志写入
				$GLOBALS['err'] = $this->ifName().'新增了【'.$data['name'].'】油品数据'; 
				$this->estruct();
				$this->result('',1,'设置成功');
			}else{
				$this->result('',0,'设置失败');
			}
		}else{
			$this->result('',0,$validate->getError());
		}

	}


	/**
	 * 修改油品页面
	 */
	public function setIndex()
	{
		$id = input('post.id');

		$list = Db::table('co_bang_cate_about')->where('id',$id)->find();
		if($list){
			$this->result($list,1,'获取数据成功');
		}else{
			$this->result('',0,'获取数据失败');
		}

	}


	/**
	 * 修改油品操作
	 */
	public function setOper()
	{
		//自增id、油id、油图片、油简介、油价钱
		$data = input('post.');
		$validate = validate('SetOper');
		if($validate->check($data)){
			$res = Db::table('co_bang_cate_about')->strict(false)->where('id',$data['id'])->update($data);
			if($res !== false){
				// 日志写入
				$GLOBALS['err'] = $this->ifName().'修改了【'.$data['name'].'】油品数据'; 
				$this->estruct();	
				$this->result('',1,'修改成功');
			}else{
				$this->result('',0,'修改失败');
			}
		}else{
			$this->result('',0,$validate->getError());
		}

	}

	/**
	 * 删除油品简介
	 * @return [type] [description]
	 */
	public function delOil()
	{
		$id = input('post.id');
		$res = Db::table('co_bang_cate_about')->where('id',$id)->delete();
		if($res){
			// 日志写入
			$name = Db::table('co_bang_cate_about')->where('id',$id)->value('name');
			$GLOBALS['err'] = $this->ifName().'删除了【'.$name.'】油品数据'; 
			$this->estruct();
			$this->result('',1,'删除成功');
		}else{
			$this->result('',0,'删除失败');
		}
	}

	/**
	 * 查看油品详情
	 * @return [type] [description]
	 */
	public function detail()
	{
		$id = input('post.id');
		$detail = Db::table('co_bang_cate_about')->where('id',$id)->find();
		if($detail){
			$this->result($detail,1,'获取详情成功');
		}else{
			$this->result('',0,'获取详情失败');
		}
	}


	/**
	 * 获取运营商公司名称  地区个数  地区详情  是否设置免费领取保养次数
	 * @return [type] [description]
	 */
	public function agentDetail()
	{
		$page = input('post.page')?:1;
		$pageSize = 10;
		$count = Db::table('ca_agent')->where('status',2)->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('ca_agent')->where('status',2)->field('aid,company,regions,free_times,leader')->select();
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取运营商列表成功');
		}else{
			$this->result('',0,'暂无数据');
		}

	}


	/**
	 * 修改该运营商所管辖区域的免费体验次数
	 */
	public function setFree()
	{
		$free_times = input('post.free_times');
		$aid = input('post.aid');
		$res = Db::table('ca_agent')->where('aid',$aid)->setField('free_times',$free_times);
		if($res !== false){
			$agent_name = Db::table('ca_agent')->where('aid',$aid)->value('company');
			// 日志写入
			$GLOBALS['err'] = $this->ifName().'修改了【'.$agent_name.'】的地区免费体验次数'; 
			$this->estruct();
			$this->result('',1,'修改成功');
		}else{
			$this->result('',0,'修改失败');
		}

	}


	/**
	 * 增加保险公司
	 * @return [type] [description]
	 */
	public function policyCompany()
	{
		// 获取保险公司名称   保险公司id
		$data = input('post.');
		$validate = validate('SystemSetup');
		if($validate->check($data)){
			if(empty($data['id'])){
				unset($data['token']);
				$res = Db::table('am_policy')->insert($data);
				if($res) $this->result('',1,'增加保险公司成功');
				$this->result('',0,'增加失败');

			}else{

				$a = [
					'company'=>$data['company'],
					'create_time'=>date('Y/m/d H/i/s'),
				];
				$res = Db::table('am_policy')->where('id',$data['id'])->update($a);
				if($res !== false) $this->result('',1,'修改保险公司成功');
				$this->result('',0,'修改失败');


			}
		}else{
			$this->result('',0,$validate->getError());
		}
		
		
	}

	/**
	 * 保险公司列表
	 * @return [type] [description]
	 */
	public function policyList()
	{
		$page = input('post.page')?:1;
		$pageSize = 10;
		$count = Db::table('am_policy')->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('am_policy')->page($page,$pageSize)->order('id desc')->select();
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}


	/**
	 * 	添加服务项
	 */
	public function addSer()
	{
		// 获取服务项名称
		$data = input('post.');
		
		if(!$data['ser_name']) $this->result('',0,'请填写服务项');

		unset($data['token']);
		if(empty($data['id'])){
			$res = Db::table('am_ser_pro')->insert($data);
			if($res){
				// 日志写入
				$GLOBALS['err'] = $this->ifName().'添加了【'.$data['ser_name'].'】服务项'; 
				$this->estruct();
				$this->result('',1,'添加成功');
			}else{
				$this->result('',0,'添加失败');
			} 
		
		}else{
			$a = [
				'ser_name'=>$data['ser_name'],
				'create_time'=>date('Y/m/d H/i/s'),
			];
			$res = Db::table('am_ser_pro')->where('id',$data['id'])->update($a);
			if($res !== false){
				// 日志写入
				$GLOBALS['err'] = $this->ifName().'修改了【'.$data['ser_name'].'】服务项'; 
				$this->estruct();
				$this->result('',1,'修改成功');
			}else{
				$this->result('',0,'修改失败');
			}
			
		}
		
	}


	/**
	 * 	添加产品
	 */
	public function addPro()
	{	
		//获取服务项id=pid
		//产品名称  name
		//更换周期 period
		//后续折扣 redate
		//产品图片 image
		//供应项描述  content
		//规格   size
		//数量  number
		$data = input('post.');
		$validate = validate('Addpro');
		Db::startTrans();
		if($validate->check($data)){

			unset($data['token']);
			$data['content'] = htmlspecialchars_decode($data['content']);
			if(empty($data['id'])){

				$res = Db::table('am_serve')->insert($data);
				$result = Db::table('am_ser_pro')->where('id',$data['pid'])->setInc('pro_num');
				if($res && $result !== false){
					// 日志写入
					$GLOBALS['err'] = $this->ifName().'添加了【'.$data['ser_name'].'】产品'; 
					$this->estruct();
					Db::commit();
					$this->result('',1,'添加产品成功');
				}else{
					Db::rollback();
					$this->result('',0,'添加产品失败');
				}
			}else{
				$res = Db::table('am_serve')->where('id',$data['id'])->strict(false)->update($data);

				if($res !== false){
					// 日志写入
					$GLOBALS['err'] = $this->ifName().'修改了【'.$data['ser_name'].'】产品'; 
					$this->estruct();
					Db::commit();
					$this->result('',1,'修改产品成功');
				}else{
					Db::rollback();
					$this->result('',0,'修改产品失败');
				}

			}
		}else{	
			$this->result('',0,$validate->getError());
		}
	}


	public function proImg()
	{
		return upload('image','admin/pro','https://ceshi.ctbls.com'); 
	}



	/**
	 * 服务项列表
	 * @return [type] [description]
	 */
	public function serItem()
	{
		$page = input('post.page')?:1;
		$pageSize = 10;
		$count = Db::table('am_ser_pro')->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('am_ser_pro')->page($page,$pageSize)->select();
		if($list){ 
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
		}else{
			$this->result('',0,'暂无数据');
		}

	}

	/**
	 * 产品列表
	 * @return [type] [description]
	 */
	public function proItem()
	{
		$page = input('post.page')?:1;
		$pageSize = 10;
		
		$count = Db::table('am_serve')->count();

		$rows = ceil($count / $pageSize);
		$list = Db::table('am_serve s')
				->join('am_ser_pro sp','s.pid = sp.id')
				->field('s.id,ser_name,name,image,size,number,period,redate,content,create_time,s.pid')
				->select();
		foreach ($list as $k => $v) {
			// 转换为html标签格式
			$content = htmlspecialchars_decode($v['content']);
			$list[$k]['content'] = $content;
		}

		if($list){ 
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}


	/**
	 * 投保类型列表
	 * @return [type] [description]
	 */
	public function insureList()
	{
		$page = input('post.page')?:1;
		$pageSize = 10;
		$count = Db::table('am_insure_type')->count();

		$rows = ceil($count / $pageSize);
		$list = Db::table('am_insure_type')
				->page($page,$pageSize)
				->order('id desc')
				->select();
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}


	/**
	 * 投保类型增加 修改
	 * @return [type] [description]
	 */
	public function insureAdd()
	{
		//获取投保类型名称 type    投保id
		$data = input('post.');
		unset($data['token']);
		if(empty($data['type'])) $this->result('',0,'请填写投保类型');

		if(empty($data['id'])){

			$res = Db::table('am_insure_type')->insert($data);
			if($res){
				// 日志写入
				$GLOBALS['err'] = $this->ifName().'添加了【'.$data['type'].'】投保类型'; 
				$this->estruct();
				$this->result('',1,'增加成功');
			}else{
				$this->result('',0,'增加失败');
			}

		}else{
			$res = Db::table('am_insure_type')->where('id',$data['id'])->strict(false)->update($data);
			if($res !== false){
				// 日志写入
				$GLOBALS['err'] = $this->ifName().'修改了【'.$data['type'].'】投保类型'; 
				$this->estruct();
				$this->result('',1,'修改成功');
			}else{
				$this->result('',0,'修改失败');
			}
		}
		
	}

	/**
	 * 删除投保类型
	 * @return [type] [description]
	 */
	public function delInsure()
	{
		$id = input('post.id');
		if(empty($id)) $this->result('',0,'缺少字段id');
		$res = Db::table('am_insure_type')->where('id',$id)->delete();
		if($res){
			$type = Db::table('am_insure_type')->where('id',$id)->value('type');
			// 日志写入
			$GLOBALS['err'] = $this->ifName().'删除了【'.$type.'】投保类型'; 
			$this->estruct();
			$this->result('',1,'删除成功');
		}else{
			$this->result('',0,'删除失败');
		}
	}


	//服务项列表  产品列表   保险公司列表   
	public function serdel()
	{
		$data = input('post.');
		$res = Db::table('am_ser_pro')->where('id',$data['id'])->delete();

		if($res){
			// 日志写入
			$GLOBALS['err'] = $this->ifName().'删除了【'.$data['ser_name'].'】服务项'; 
			$this->estruct();
			$this->result('',1,'删除成功');
		}else{
			$this->result('',0,'删除失败');
		}
	}


	//服务项列表  产品列表   保险公司列表   
	public function prodel()
	{
		$data = input('post.');
		$res = Db::table('am_serve')->where('id',$data['id'])->delete();

		if($res){
			// 日志写入
			$GLOBALS['err'] = $this->ifName().'删除了【'.$data['name'].'】产品'; 
			$this->estruct();
			$this->result('',1,'删除成功');
		}else{
			$this->result('',0,'删除失败');
		}
	}



	//服务项列表  产品列表   保险公司列表   
	public function comdel()
	{
		$data = input('post.');
		$res = Db::table('am_serve')->where('id',$data['id'])->delete();

		if($res){
			// 日志写入
			$GLOBALS['err'] = $this->ifName().'删除了【'.$data['company'].'】'; 
			$this->estruct();
			$this->result('',1,'删除成功');
		}else{
			$this->result('',0,'删除失败');
		}
	}













}