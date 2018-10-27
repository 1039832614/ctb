<?php 
namespace app\supply\controller;
use app\base\controller\supply;
use think\File;
use think\Db;

/**
* 资金管理 --
*/
class Wealth extends supply{
    
    /*
     * 获取驳回条数
     */
    public function priceReject()
    {
        $this->PutSize(2);
    }

    /*
     * 获取通过条数
     */
    public function priceAdopt()
    {
        $this->PutSize(1);
    }

    /**
     * 收入明细 卖货收入
     * @return json
     */    
    
    public function Detailed()
    {  
       $page = input('post.page')? :1;
       $size = 8;
       $count = DB::table('cg_apply_materiel')->where(['gid'=>$this->gid])->whereIn('audit_status',[1,4])->count();
       if( empty($count) ) $this->result('',0,'暂无数据'); 
       $rows = ceil($count/$size);
       $data = DB::table('cg_apply_materiel a')
               ->join('ca_agent b','a.aid = b.aid')
               ->where(['a.gid'=>$this->gid])
               ->whereIn('audit_status',[1,4])
               ->page($page,$size)
               ->field('a.id,b.company,b.phone,FROM_UNIXTIME(a.audit_time) end_time,a.odd_number,a.price')
               ->select();
       $this->result(['rows'=>$rows,'list'=>$data],1,'获取成功');
    }

    /**
     * 收入明细 邦保养收入
     * @return json
     */ 
    
    public function WBang()
    {
  
      // 获取旗下维修厂
      $ids = DB::table('cs_shop a')
             ->join('ca_agent b','a.aid = b.aid')
             ->where('b.gid',$this->gid)
             ->column('a.id'); 
      // 获取页数
      $page = input('post.page')?:1;
      // 获取总条数
      $count = Db::table('u_card a')
                ->join('cs_shop b','a.sid = b.id')
                ->join('ca_agent c','c.aid = b.aid')
                ->join('ca_area d','c.aid = d.aid')
                ->join('co_china_data e','d.area = e.id')
                ->whereIn('a.sid',$ids)
                ->count();
      if(empty($count)) $this->result('',0,'暂无数据');   

      $rows = ceil($count/8); 
      // 获取市级id / 卡id / 售卡金额
      $card = Db::table('u_card a')
                ->join('cs_shop b','a.sid = b.id')
                ->join('ca_agent c','c.aid = b.aid')
                ->join('ca_area d','c.aid = d.aid')
                ->join('co_china_data e','d.area = e.id')
                ->whereIn('a.sid',$ids)
                // ->where('c.gid',$this->gid) 
                ->page($page,8)
                ->field('e.pid,a.id,a.card_price,a.card_number odd_number,c.company,c.leader,a.sale_time create_time')
                ->select();
      // 单独取出 市级id
      $area = array_column($card,'pid');
      // 获取已经设置利率的地区
      $data = DB::table('cg_increase')->field('area,pro')->select();
      // 存储利率 和 地区
      $arr = [];
      foreach ($data as $k => $v) {
         // 切割数组
         $data[$k]['areas'] = explode(',', $v['area']);
         foreach ($area as $ke => $va) {

              // 判断 该市是否存在市级代理
              if(in_array($va,$data[$k]['areas']))
              {   
                  $arr[$ke]['area'] = $va;
                  if(empty($v['pro']))
                  {
                    $arr[$ke]['pro'] = 0;
                  }else{
                    $arr[$ke]['pro'] = $v['pro'];   
                  }
                  
              }else{
                 // 不存在市级代理   利率 设置为 0 
                 if(!isset($arr[$ke]['area']))
                 {
                  $arr[$ke]['area'] = $va;
                 }
                 if(!isset($arr[$ke]['pro']))
                 {
                  $arr[$ke]['pro'] = 0;
                 }
              }

          } 
      }

      // 获取每张卡利润
      foreach ($arr as $ke => $va) {
          
          foreach ($card as $k => $v) {
             
             if($v['pid'] == $va['area'])
             {    
               
                 $card[$k]['pros'] = $v['card_price']*($va['pro']/100);
                 $card[$k]['por'] = $va['pro'];

             }

          }

      }
      
      $this->result(['rows'=>$rows,'list'=>$card],1,'获取邦保养收入成功');
     
    }

    /**
     * 收入明细 卖卡收入
     * @return json
     */ 
    
    // public function wbang()
    // {   
    //     // 获取地区
    //     $area = Db::table('u_card a')
    //             ->join('cs_shop b','a.sid = b.id')
    //             ->join('ca_agent c','c.aid = b.aid')
    //             ->join('ca_area d','c.aid = d.aid')
    //             ->value('area');
    //     print_R($area);
    // }

    
    /**
     * 收入详情
     * @return json
     */
    public function Detailed_details()
    {   

        $id = input('post.id');
        $this->isNull($id);

        $data = Db::table('cg_apply_materiel')->where(['gid'=>$this->gid,'id'=>$id])->json(['detail'])->field('detail,max')->find();
        if(empty($data)) $this->result('',0,'获取失败');

        $this->result(['price'=>$data['max'],'list'=>$data['detail']],1,'获取成功');
          
    }




    /**
	   * 获取余额 ， 提现账户
	   * @return json
	   */
	  
    public function get_forward()
    {
    	 $data = DB::table('cg_supply')->where('gid',$this->gid)->field('balance,account')->find();
    	 if($data){
            $this->result($data,1,'获取成功');
    	 }else{
    	 	$this->result('',0,'获取失败');
    	 }
    	 
    } 
    
     /**
	   * 申请提现
	   * @return json
	   */    
    
    public function Put_forward()
    {  
         
       
    	 $price = input('post.price');
    	 if($price>0){
             // 校验时间
             $this->PriceTime();
             // 获取提现账号 手续费 剩余余额  开户行code 开户名
             $arr = $this->price_check($price);
             
             $arr = [   
                'gid' => $this->gid,
                'number' => build_order_sn(),
                'putprice' => $price,
                'ActualPrice' => $price - $arr['fee'],
                'fee' => $arr['fee'],
                'account' => $arr['account'],
                'sur_amount' => $arr['sur_amount'],
                'bank_code' => $arr['bank'],
                'account_name' => $arr['bank_name'],
             ];
            $res = DB::table('cg_put')->insert($arr);

            if($res){
            	$this->result('',1,'申请成功');
            }else{
            	$this->result('',0,'申请失败');
            }

    	 }else{
        $this->result('',0,'金额不能为空');
       }
    }

     /**
	   * 提现明细
	   * @return json
	   */ 
    public function Put_detatils()
    { 
       $page = input('post.page')? :1;
       $size = 10;
       $max = Db::table('cg_put')->where('gid',$this->gid)->count();
       if( empty($max) ) $this->result('',0,'暂无数据'); 
       $wei = ceil($max/$size);
       $data = Db::table('cg_put')->where('gid',$this->gid)->page($page,$size)
               ->field('number,account,putprice,create_time,Trial_time,reject,status')->select();
       if(empty($data))  $this->result('',0,'数据为空');
       $this->result(['rows'=>$wei,'list'=>$data],1,'获取成功');
 
    }




    /**
     * 校验提现时间
     * @return 
     */
    public function PriceTime()
    {  

       $result = DB::table('cg_put')->where(['gid'=>$this->gid,'status'=>0])->find();
       if(!empty($result)) $this->result('',0,'已有待审核订单');

       $time = Db::table('cg_put')
       ->where(['gid'=>$this->gid,'status'=>1])->whereTime('create_time','-360 hours')
       ->order('create_time','desc')
       ->limit(1)
       ->value('create_time');
       if(empty($time)) return true;

       $com = Date("Y-m-d H:i:s",strtotime('+15 day',strtotime($time)));
       if(!empty($time)) $this->result($com,9,'下次申请时间为'.$com);
    }


     /**
	   * 校验提现余额 返回提现账户  手续费
	   * @return 
	   */
    public function price_check($price)
    {
        if(empty($price))  $this->result('',0,'提现金额不能为空');
        if($price%100!=0)  $this->result('',0,'提现金额必须为100的整数倍');
        // 获取收入 ， 提现账号
        $data = DB::table('cg_supply')->where('gid',$this->gid)->field('balance,account,bank,bank_name')->find();

        if(empty($data)) $this->result('',0,'获取数据失败');
        
        if($price+1000>$data['balance']){
           
           	$max = floor(($data['balance']-1000)/100)*100;
            // $max = floor($max);
            $max = $max<0? 0:$max;
            $this->result($max,0,'本次提现金额最大为'.$max);
           
        }
        // 手续费
        $fee = $price*(0.15/100);
        // 剩余金额
        $sur = $data['balance']-$price;
        // 减少余额
        $res = Db::table('cg_supply')->where('gid',$this->gid)->setdec('balance',$price);
        if(empty($res)) $this->result('',0,'网络繁忙，请稍候重试!');
        // 返回提现账号 手续费
        return ['account'=>$data['account'],'fee'=>$fee,'sur_amount'=>$sur,'bank'=>$data['bank'],'bank_name'=>$data['bank_name']];

    }

    /**
     * 获取提现条数
     * @param   $status 2 通过 3 驳回
     * @return 
     */
    
    public function PutSize($status){

        $count = Db::table('cg_put')->where(['status'=>$status,'gid'=>$this->gid])->count();
        // echo $count;die;
        if($count===false) $this->result('',0,'获取失败');
        $this->result($count,1,'获取成功');
    } 
  


   







}	 