<?php
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;
use Ana\ShopAna;
// use app\facade\ShopAna;
// use think\Facade;
/**
 * 维修厂数据分析
 */
class Shopview extends Admin
{
   
    public function __construct()
    {
    	parent::__construct();
    	$this->Ana = new ShopAna;
    }

    /**
     *  邦保养关注度 or 参与度
     */
    
    public function Follow()
    {
    	// 获取维修厂id
    	$ids  = $this->ShopAll();
        $data = $this->Ana->deliver_num($ids);

        if($data===false)
        	$this->result('',0,'获取邦保养关注度以及参与度失败');
        $this->result($data,1,'获取邦保养关注度以及参与度成功');

    }
 

    /**
     *  资金详情
     */
    
    public function PriceDetails()
    {   
    	// 获取维修厂id
    	$ids = $this->ShopAll();
    	$data = $this->Ana->money($ids);
    }
    

    /**
     *  技师服务次数
     */
    
    public function TechSum()
    {
    	// 获取维修厂id
    	$ids  = $this->ShopAll();
    	$data = $this->Ana->worker_serve($ids);
	
    }

    /**
     *  技师- 礼品兑换
     */
    
    public function GiftSum()
    {
    	// 获取维修厂id
    	$ids = $this->ShopAll();
    	$data = $this->Ana->worker_gift($ids);	
    }
       
    /**
     *  技师- 服务奖励
     */
    
    public function TechPrice()
    {
    	// 获取维修厂id
    	$ids = $this->ShopAll();
    	$data = $this->Ana->worker_award($ids,1);
  	
    }   

    /**
     *  技师- 文章推荐奖励
     */
    
    public function TechPush()
    {
    	// 获取维修厂id
    	$ids = $this->ShopAll();
    	$data = $this->Ana->worker_award($ids,2);
    }

    /**
     *  维修厂- 授信物料
     */
    
    public function CreditSum()
    {
    	// 获取维修厂id
    	$ids = $this->ShopAll();
    	$data = $this->Ana->total($ids);
    	
    }

    /**
     *  维修厂- 期初配给
     */
    
    public function Rationum()
    {
    	// 获取维修厂id
    	$ids = $this->ShopAll();
    	$data = $this->Ana->allotment($ids);
   	
    }


    /**
     *  维修厂- 物料消耗
     */
    
    public function Materielsume()
    {
    	// 获取维修厂id
    	$ids = $this->ShopAll();
    	// $data = $this->Ana->dissipation($ids);
        //该维修厂下的数据   cs_income 邦保养记录表     cs_shop  修车厂表
        $pageSize = 8;
        $page = input('post.page')? : 1;
        $count = Db::table('cs_income')
            ->where('sid','in',$ids)
            ->where("DATE_FORMAT(create_time,'%Y') = YEAR(CurDate())")
            ->field('DATE_FORMAT(create_time,"%c") as month,oil,sum(litre) as litre')
            // ->page($page,$pageSize)
            ->group('month')
            ->select();
        $count = count($count);
        $totalpages = ceil($count / $pageSize);
        $list = Db::table('cs_income')
            ->where('sid','in',$ids)
            ->where("DATE_FORMAT(create_time,'%Y') = YEAR(CurDate())")
            ->field('DATE_FORMAT(create_time,"%c") as month,oil,sum(litre) as litre')
            ->page($page,$pageSize)
            ->group('month')
            ->select();
        // print_R($list);die;
        $oil = Db::table('co_bang_cate')->where('pid','<>','0')->field('id,name')->select();
      	foreach ($list as $k => $v) {
      		foreach ($oil as $ke => $va) {
      			if($va['name'] == $v['oil']){
      				switch ($va['id']){
		                case 2:
		                    $list[$k]['num1'] =  $v['litre'];
		                    break;
		                case 3:
		                    $list[$k]['num2'] =  $v['litre'];
		                    break;
		                case 4:
		                    $list[$k]['num3'] =  $v['litre'];
		                    break;
		                case 5:
		                    $list[$k]['num4'] =  $v['litre'];
		                    break;
		                case 7:
		                    $list[$k]['num5'] =  $v['litre'];
		                    break;
		            }
	      		}
	      		foreach ($list as $key => $value) {
	                for ($i=1; $i < 6; $i++) {
	                    if(empty($value['num'.$i])){
	                        $list[$key]['num'.$i] = 0;
	                    }
	                }
            	}
      		}
      	}    	
        // echo $totalpages;die;
        // print_r($list);die;
        if ($count > 0){
            $this->result(['list'=>$list,'totalpages'=>$totalpages],1,'获取成功');
        }else{
            $this->result('',0,'暂无数据');
        } 
  	
    }


    /**
     *  维修厂- 物料补充
     */
    
    public function MaterielSupp()
    {
    	// 获取维修厂id
    	$ids = $this->ShopAll();
    	$data = $this->Ana->embody($ids);
  	
    }

    /**
     *  维修厂- 物料剩余
     */
    
    public function MaterialSurplus()
    {
    	// 获取维修厂id
    	$ids = $this->ShopAll();
    	$data = $this->Ana->surplus($ids);
  	
    }


    /**
     *  维修厂- 增加授信
     */
    
    public function CreditAdd()
    {
    	// 获取维修厂id
    	$ids = $this->ShopAll();
    	$data = $this->Ana->credit($ids);
 	
    }


    /**
     *  维修厂- 购卡一次
     */
    
    public function ShopOne()
    {
    	// 获取维修厂id
    	$ids = $this->ShopAll();
    	$data = $this->Ana->one_card($ids);
        if($data===false)
        	$this->result('',0,'获取购卡一次失败');
        $this->result($data,1,'获取购卡一次成功');     	
    }

    /**
     *  维修厂- 购卡四次
     */
    
    public function ShopFour()
    {
    	// 获取维修厂id
    	$ids = $this->ShopAll();
    	$data = $this->Ana->four_card($ids);
        if($data===false)
        	$this->result('',0,'获取购卡四次失败');
        $this->result($data,1,'获取购卡四次成功');     	
    }

    /**
     *  维修厂- 参与次数
     */
    
    public function PartSum()
    {
    	// 获取维修厂id
    	$ids = $this->ShopAll();
    	$data = $this->Ana->take_times($ids);
        if($data===false)
        	$this->result('',0,'获取参与次数失败');
        $this->result($data,1,'获取参与次数成功');     	
    }

    /**
     *  维修厂- 剩余次数
     */
    
    public function OverSum()
    {
    	// 获取维修厂id
    	$ids = $this->ShopAll();
    	$data = $this->Ana->remain_times($ids);
        if($data===false)
        	$this->result('',0,'获取剩余次数失败');
        $this->result($data,1,'获取剩余次数成功');     	
    }

    /**
     *  维修厂- 车主详情
     */
    
    public function UserInfo()
    {
    	// 获取维修厂id
    	$ids = $this->ShopAll();
    	$data = $this->Ana->userInfo($ids);
        if($data===false)
        	$this->result('',0,'获取车主详情失败');
        $this->result($data,1,'获取车主详情成功');     	
    }

    /**
     *  维修厂- 复购量
     */
    
    public function ReSum()
    {
    	// 获取维修厂id
    	$ids = $this->ShopAll();
    	$data = $this->Ana->repetition_num($ids);
        if($data===false)
        	$this->result('',0,'获取复购量失败');
        $this->result($data,1,'获取复购量成功');     	
    }

    /**
     *  维修厂- 复购详情
     */
    
    public function ReSumDetails()
    {
    	// 获取维修厂id
    	$ids = $this->ShopAll();
    	$data = $this->Ana->repetition_detail($ids);
        if($data===false)
        	$this->result('',0,'获取复购详情失败');
        $this->result($data,1,'获取复购详情成功');     	
    }


    /**
     *  维修厂- 复购量
     */
    
    public function Resumsize()
    {
        // 获取维修厂id
        $ids = $this->ShopAll();
        $data = $this->Ana->repetition_num($ids);
        if($data===false)
            $this->result('',0,'获取复购详情失败');
        $this->result($data,1,'获取复购详情成功');      
    }




    /**
     *  柱形图- 交易总额 
     */
    
    public function ToCardPrice()
    {   
     // 获取维修厂id
     $sid = $this->ShopSid();
        if(empty($sid))
        $this->result('',0,'参数错误');
        $list = $this->CardSums($sid,',sum(card_price) price');
        $list = mfile($list,'price');
        $this->result($list,1,'获取交易总额成功');
    }


    /**
     *  柱形图- 售卡总数 
     */
    
    public function ToCardSum()
    {   
    	// 获取维修厂id
    	$sid = $this->ShopSid();
        if(empty($sid))
        	$this->result('',0,'参数错误');
        $list = $this->CardSums($sid,',count(uid) number');
        $list = mfile($list,'number');
        $this->result($list,1,'获取售卡总数成功');
    }
                                                                             
    /**
     *  柱形图- 服务次数 
     */
    
    public function ToServeSum()
    {   
    	// 获取维修厂id
    	$sid = $this->ShopSid();
        if(empty($sid))
        	$this->result('',0,'参数错误');
        $data = $this->Ana->worker_serve($sid);

    }    

    /**
     *  柱形图- 好评次数 
     */
    
    public function GoodSum()
    {   
    	// 获取维修厂id
    	$sid = $this->ShopSid();
        if(empty($sid))
        	$this->result('',0,'参数错误');

        $data = Db::table('u_comment')
        ->whereIn('sid',$sid)
        ->field("DATE_FORMAT(create_time,'%c') month,count(id) number")
        ->where("DATE_FORMAT(create_time,'%Y') = YEAR(CurDate())")
        ->group('month')
        ->select();

       
        if($data===false)
        	$this->result('',0,'获取好评次数失败');
        $data = mfile($data,'number');
        $this->result($data,1,'获取好评次数成功');
    }  

    /**
     *  柱形图- 物料消耗(件数)
     */
    
    public function StageSum()
    {   
    	// 获取维修厂id
    	$sid = $this->ShopSid();
        if(empty($sid))
        	$this->result('',0,'参数错误');

        $data = DB::table('cs_income')
        ->whereIn('sid',$sid)
        ->where("DATE_FORMAT(create_time,'%Y') = YEAR(CurDate())")
        ->field("DATE_FORMAT(create_time,'%c') as month,floor(sum(litre)/12) number")
        ->select();
        if($data===false)$this->result('',0,'获取物料消耗失败');
        $data = mfile($data,'number');
        $this->result($data,1,'获取物料消耗成功');
    }  

 

    




    /**
     *  柱形图- 复购次数 
     */
    
    public function ToRepu()
    {   
    	// 获取维修厂id
    	$sid = $this->ShopSid();
        if(empty($sid))
        	$this->result('',0,'参数错误');
        $data = $this->Ana->repetition_num($sid);

    }    



    /**
     *  柱形图- 售卡平均 
     */
    
    public function ToCardFalt()
    {   
    	// 获取维修厂id
    	$sid = $this->ShopSid();
        if(empty($sid))
        	$this->result('',0,'参数错误');
        // 获取维修厂数量 计算平均值
        $count = $this->ShopSum();
        $list = $this->CardSums($sid,',count(id)/'.$count.' number');
        if(empty($list))
            $this->result('',0,'暂无数据');
        $list = mfile($list,'number');
        $this->result($list,1,'获取售卡平均成功');
    }


    /**
     *  柱形图- 金额平均 
     */
    
    public function ToPricFalt()
    {   
    	// 获取维修厂id
    	$sid = $this->ShopSid();
        if(empty($sid))
        	$this->result('',0,'参数错误');
        // 获取维修厂数量 计算平均值
        $count = $this->ShopSum();
        // dump($sid);die;
        $list = $this->CardSums($sid,',sum(card_price)/'.$count.' number');
        if(empty($list))
            $this->result('',0,'暂无数据');
        $list = mfile($list,'number');
        $this->result($list,1,'获取金额平均成功');
    }



    /**
     *  获取维修厂sid
     */
    
    public function ShopAll()
    {
    	// $ids = Db::table('cs_shop')->column('id');
    	$ids = input('post.sid');
        if($ids==false) 
              $this->result('',0,'参数错误');
        return $ids;
    }

    /**
     *  获取该省维修厂 sid
     */
    
    public function ShopSid()
    {
    	// 获取省名称
    	$area = input('post.area');
    	if(empty($area))
    		$this->result('',0,'地区参数错误');
    	$count = Db::table('cs_shop_set')->where('province',$area)->column('sid');
    	if(empty($count)) 
    		$this->result('',0,'该地区暂无维修厂');
    	return $count;
    }


    /**
     *  获取该省维修厂数量
     */
    
    public function ShopSum()
    {   
    	// 获取省名称
    	$area = input('post.area');
    	if(empty($area))
    		$this->result('',0,'地区参数错误');
    	$count = Db::table('cs_shop_set')->where('province',$area)->count();
    	if(empty($count)) 
    		$this->result('',0,'该地区暂无维修厂');
    	return $count;
    }


    /**
     *  售卡总额 or 售卡总数
     */
    
    public function CardSums($sid,$field){
        $list = Db::table('u_card')
                ->where([
                    ['sid','in',$sid],
                    ['pay_status','=',1]
                ])
                ->field("DATE_FORMAT(sale_time,'%c') as month".$field)
                ->where("DATE_FORMAT(sale_time,'%Y') = YEAR(CurDate())")
                ->group('month')
                ->select();
        if(empty($list))
           $this->result('',0,'暂无数据');
        return $list; 

    }
    /**
     *  根据地区获取维修厂列表 
     */
    
    public function Wlist()
    {
        $area = input('post.area');

        if(empty($area)) $this->result('',0,'获取失败');
        $area = Db::table('co_china_data')->where('id',$area)->value('name');
        $data = Db::table('cs_shop_set a')->join('cs_shop b','a.sid = b.id')->where('a.province',$area)->field('b.id aid ,b.company')->select();
        array_unshift($data, ['company'=>'全部公司']);

        $this->result($data,1,'获取成功');
    }

    /*
     * 根据地区获取维修厂列表  --- 搜索
     */
    
    public function ccc()
    {
        $data = input('post.');
        $data['area'] = $data['id'];
        if(!isset($data['area']) || !isset($data['search'])) $this->result('',0,'获取失败！参数错误');
        $area = Db::table('co_china_data')->where('id',$data['id'])->value('name');
        $data = Db::table('cs_shop_set a')->join('cs_shop b','a.sid = b.id')
                ->where('a.province',$area)
                ->whereLike('b.company',$data['search'].'%')
                ->field('b.id aid ,b.company')
                ->select();
        array_unshift($data, ['company'=>'全部公司']);

        $this->result($data,1,'获取成功');
    }

}