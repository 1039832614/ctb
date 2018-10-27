<?php
namespace app\supply\controller;
use app\base\controller\supply;
use think\Db;
Class Followview extends supply
{
	/**
	 * 邦保养关注度  
	 * 
	 */
    
    public function ProFollow()
    {   
    	$count = DB::table('u_user')->count();
    	if($count===false) $this->result('',0,'获取失败');
	    $this->result($count,1,'获取成功');

    } 
    
	/**
	 * 邦保养参与度   本月
	 * 
	 */
	
	public function ProUse()
	{  
         $count = Db::table('ca_agent')->where('gid',$this->gid)->sum('sale_card');
         if($count===false) $this->result('',0,'获取失败');
         $this->result($count,1,'获取成功');
	} 

    /**
     * 资金
     * 
     */    
    
    public function PriceAll()
    {
       // 售卡总额
       $list['sale'] = $this->SalePrice();
       // 交易分成
       $list['rate'] = floor($list['sale']*($this->Rate()/100));
       // 配送收入
       $list['dis'] = $this->PriceExtract();    

       // 收入
       $list['income'] = $list['rate']+$list['dis'];           
       // 提现
       $arr['Put'] = $this->PutForward();
       // 余额
       $arr['balance'] = $this->balance();
       // 支出
       $arr['exp'] = 0;
       
       $arr = $this->Percent($arr);                
      
       $this->result(['list'=>$list,'data'=>$arr],1,'获取成功');
    }

    /**
     * 售卡详情
     * 
     */ 
    
    public function CardDetails()
    {
       $page = input('post.page')? :1;
       $count = Db::table('ca_agent')->where('gid',$this->gid)->count();
       if($count==0) $this->result('',0,'数据为空');
       $rows = ceil($count/8);
       $data = Db::table('ca_agent a')
               ->join('cs_shop b','a.aid = b.aid')
               ->join('u_card c','b.id = c.sid')
               ->where("DATE_FORMAT(c.sale_time,'%Y')=Year(CurDate())")
               ->where(['c.pay_status'=>1,'a.gid'=>$this->gid])
               ->field('a.aid,a.company,a.leader,a.phone,count(c.id) size,sum(c.card_price) price')
               ->group('b.aid')
               ->page($page,8)
               ->select();
       if($data===false) $this->result('',0,'获取失败');
       $this->result(['rows'=>$rows,'list'=>$data],1,'获取成功');

    }

    /**
     * 提现详情
     * 
     */ 
    
    public function PutDetails()
    {
       $page = input('post.page')? :1;
       $count = Db::table('cg_put')->where("DATE_FORMAT(FROM_UNIXTIME(Trial_time),'%Y')=Year(CurDate())")->where(['gid'=>$this->gid,'status'=>1])->count();
       if($count==0) $this->result('',0,'数据为空');
       $rows = ceil($count/8);
       $data = Db::table('cg_put')->field('FROM_UNIXTIME(Trial_time) Trial_time,putprice')
               ->where("DATE_FORMAT(FROM_UNIXTIME(Trial_time),'%Y')=Year(CurDate())")
               ->where(['status'=>1,'gid'=>$this->gid])
               ->page($page,8)
               ->select();
       if($data===false) $this->result('',0,'获取失败');
       $this->result(['rows'=>$rows,'list'=>$data],1,'获取成功');       
     
    } 



	/**
	 * 资金详情 -- 配送收入 
	 * @param  audit_status 申请状态  0申请中 1发货中 2已通过 3驳回
	 */
   
    public function PriceExtract()
    { 
        $balance = Db::table('cg_apply_materiel')->where(['gid'=>$this->gid])->whereIn('audit_status',[1,2])->value('sum(price)');        
        return $balance;
    }

	/**
	 * 资金详情 -- 提现 
	 * 
	 */
    
    public function PutForward()
    {
    	$price = DB::table('cg_put')
                 ->where(['gid'=>$this->gid,'status'=>1])
                 ->value('sum(putprice)');
        return $price;
    }

	/**
	 * 资金详情 -- 余额 
	 * 
	 */
    
    public function balance()
    {
    	$price = DB::table('cg_supply')->where('gid',$this->gid)->value('balance');
    	return $price;
    }   

    /**
     * 资金详情 -- 售卡总额 
     * @param  audit_status 申请状态  0申请中 1发货中 2已通过 3驳回
     */
    
    public function SalePrice()
    {

    $data = DB::table('ca_agent a')
            ->leftjoin('cs_shop b','a.aid = b.aid')
            ->leftjoin('u_card c','b.id = c.sid')
            ->where("DATE_FORMAT(sale_time,'%Y')=Year(CurDate())")
            ->where(['a.gid'=>$this->gid,'c.pay_status'=>1])
            ->value("sum(card_price)");

    return $data;
    }

    /**
     * 资金详情 -- 利率
     * @param  audit_status 申请状态  0申请中 1发货中 2已通过 3驳回
     */
    
    public function Rate()
    {
        $profit = Db::table('cg_supply_set')->where('gid',$this->gid)->value('profit');
     
        return $profit;
    }  

    /**
     * 资金详情 -- 计算百分比
     * @param  audit_status 申请状态  0申请中 1发货中 2已通过 3驳回
     */      
    
    public function Percent($arr,$price=0)
    { 

        foreach ($arr as $k => $v) {
                  
            $price+=$v;
        }
        
        if( empty($arr['Put']) )
        {
          $pro1 = 0;
        }else{
          $pro1 = floor($arr['Put']/$price*100);
        }

        if( empty($arr['balance']) )
        {
          $pro2 = 0;
        }else{
          $pro2 = floor($arr['balance']/$price*100);
        }         
        
       
        $data = array(['Put'=>$arr['Put'],'pro'=>$pro1],['balance'=>$arr['balance'],'pro'=>$pro2],['exp'=>0,'pro'=>0]);
        return $data;
        
        
        
    }
}