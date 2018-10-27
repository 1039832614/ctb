<?php 
namespace app\shop\controller;
use app\base\controller\Shop;
use think\Db;
use Ana\ShopAna;

/**
 * 数据分析
 */
class Analysis extends Shop
{
	/**
	 * 初始化
	 * @return [type] [description]
	 */
	public function initialize()
	{
//		$this->sid = input('post.sid');
        parent::initialize();
		$this->ShopAna = new ShopAna;
	}
	/**
	 * 店面关注度以及邦保养参与
	 */
	public function deliver_num(){
		return $this->ShopAna->deliver_num($this->sid);
	}
	/**
	 * 复购量
	 */
	public function repetition_num(){
		return $this->ShopAna->repetition_num($this->sid);
	}
	/**
	 * 复购详情
	 */
	public function repetition_detail()
	{
		return $this->ShopAna->repetition_detail($this->sid);
	}
	/**
	 * 技师服务次数
	 */
	public function worker_serve(){
		return $this->ShopAna->worker_serve($this->sid);
	}
	/**
	 * 技师礼品兑换
	 */
	public function worker_gift(){
		return $this->ShopAna->worker_gift($this->sid);
	}
	/**
	 * 技师服务奖励
	 */
	public function worker_award(){
		return $this->ShopAna->worker_award($this->sid,1);
	}
	/**
	 * 技师文章奖励
	 */
	public function worker_article(){
		return $this->ShopAna->worker_award_list($this->sid,2);
	}
	/**
	 * 一次卡
	 * @return [type] [description]
	 */
	public function one_card(){
		return $this->ShopAna->one_card($this->sid);
	}
	/**
	 * 四次卡
	 * @return [type] [description]
	 */
	public function four_card(){
		return $this->ShopAna->four_card($this->sid);
	}
	/**
	 * 参与次数
	 * @return [type] [description]
	 */
	public function take_times(){
		return $this->ShopAna->take_times($this->sid);
	}
	/**
	 * 剩余次数
	 * @return [type] [description]
	 */
	public function remain_times(){
		return $this->ShopAna->remain_times($this->sid);
	}
	/**
	 * 车主详情
	 * @return [type] [description]
	 */
	public function userInfo(){
		return $this->ShopAna->userInfo($this->sid);
	}

    /**
     * 期初配给
     * @return [type] [description]
     */
    public function allotment()
    {
        return $this->ShopAna->allotment($this->sid);
    }

    /**
     * 物料剩余
     * @return [type] [description]
     */
    public function surplus()
    {
        return $this->ShopAna->surplus($this->sid);
    }

    /**
     * 物料补充
     * @return [type] [description]
     */
    public function embody()
    {
        return $this->ShopAna->embody($this->sid);
    }

    /**
     * 物料消耗
     * @return [type] [description]
     */
    public function dissipation()
    {
        return $this->ShopAna->dissipation($this->sid);
    }

    /**
     * 增加授信
     * @return [type] [description]
     */
    public function credit()
    {
        return $this->ShopAna->credit($this->sid);
    }

    /**
     * 首页数据
     * @return [type] [description]
     */
    public function total()
    {
        return $this->ShopAna->total($this->sid);
    }

    /**
     * 资金收入
     * @return [type] [description]
     */
    public function money()
    {
        return $this->ShopAna->money($this->sid);
    }

    /**
     * 提现详情
     * @return [type] [description]
     */
    public function withdrawDetail()
    {
        return $this->ShopAna->withdrawDetail($this->sid);
    }
}