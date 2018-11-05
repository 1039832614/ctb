<?php
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;
/**
* 会员地址列表
*/
class MemAddList extends Admin
{
	
	/**
	 *	待发货列表
	 * @return [type] [description]
	 */
	public function waitList()
	{
		$page = input('post.page')?:1;
		$this->list($page,0);		
	}


	/**
	 * 已发货列表
	 * @return [type] [description]
	 */
	public function already()
	{
		$page = input('post.page')?:1;
		$this->list($page,1);
	}

	/**
	 * 获取省
	 * @return [type] [description]
	 */
	public function provinceList()
    {
        $province = $this->province();
        // return $province;
        if($province) $this->result($province,1,'获取省成功');
        $this->result('',0,'暂无数据');
    }

    /**
     * 获取市
     * @return [type] [description]
     */
    public function cityL()
    {   
        $pid = input('post.pid');
        $city = $this->city($pid);
        if($city) $this->result($city,1,'获取市成功');
        $this->result('',0,'暂无数据');
    }


	/**
	 * 导出会员地址列表
	 * @return [type] [description]
	 */
	public function order()
	{
		$city = input('get.city');
		if(!$city) $this->result('',0,'参数city缺少');
		$expTitle = '会员地址导出';
		$array = [
			['man','收件人'],
			['phone','联系电话'],
			['address','联系地址'],
			['details','详细地址'],
			['time','购买会员时间'],
			
		];
		$list = Db::table('u_winner')
				->where(['area'=>$city,'status'=>0])
				->where('member','>',0)
				->select();
		exportExcel($expTitle,$array,$list);	
	}




	/**
	 * 列表
	 * @return [type] [description]
	 */
	public function list($page,$status)
	{
		

		$pageSize = 10;
		$count = Db::table('u_winner w')
		->join('u_member_table mt','w.member = mt.id')	
		->where('w.member','>',0)
		->where('w.status',$status)
		->count();

		$rows = ceil($count / $pageSize);

		$list = Db::table('u_winner w')
				->join('u_member_table mt','w.member = mt.id')	
				->where('w.member','>',0)
				->page($page,$pageSize)
				->where(['w.status'=>$status,'mt.pay_status'=>1])
				->order('w.id desc')
				->field('w.man,w.phone,w.uid,w.address,w.details,area')
				->select();
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
		}else{
			$this->result('',0,'暂无数据');
		}
				
	}
}