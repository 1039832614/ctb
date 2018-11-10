<?php 

	/**
	 * 获取用户
	 * 新版本使用
	 */
	public function getUsers()
	{
		$page = input('post.page') ? : 1;
		// 获取每页条数
		$pageSize = 10;
		// 获取分页总条数
		$count =  Db::table('u_card')
					->alias('c')
					->join(['u_user'=>'u'],'c.uid = u.id')
					->field('plate,card_number,remain_times,u.name,u.phone,sale_time')
					->where('sid',$this->sid)
					->where('pay_status',1)
					->count();
		$rows = ceil($count / $pageSize);
		// 获取数据
		$list = Db::table('u_card')
				->alias('c')
				->join(['u_user'=>'u'],'c.uid = u.id')
				->field('c.uid,plate,card_number,remain_times,u.name,u.phone,sale_time')
				->where('sid',$this->sid)
				->where('pay_status',1)
				->order('u.id desc')
				->page($page, $pageSize)
				->select();
		// 判断用户是否是会员
		foreach ($list as $k => $v) {
			$status = Db::table('u_member_table')
						->where([
							'uid'=>$v['uid'],
							'pay_status'=>1
						])
						->count();
			$list[$k]['car_pic'] = Db::table('cb_privil_ser')
									->where('plate',$list[$k]['plate'])
									->order('id desc')
									->limit(1)
									->value('car_pic');
			if(empty($list[$k]['car_pic'])){
				$list[$k]['car_pic'] = 'https://ceshi.ctbls.com/uploads/shop/photo/20181107/317821873.png';
			}
			if($status > 0){
				$list[$k]['status'] = 1;
			}else{
				$list[$k]['status'] = 0;
			}
		}
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}