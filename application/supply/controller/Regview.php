<?php 
namespace app\supply\controller;
use app\base\controller\supply;
use think\Db;

/**
 *  区域汇总
 * 
 */
class Regview extends supply
{

	/**
	 *  交易金额
	 * 
	 */
	
	public function CooList()
	{   

      $data = $this->jPrice();
      // $data = mfile($data,'price');
      $this->result($data,1,'获取成功');
	}

	/**
	 *  售卡总数
	 * 
	 */   
   public function SaleCard()
   {   

    $data = DB::table('ca_agent a')
            ->leftjoin('cs_shop b','a.aid = b.aid')
            ->leftjoin('u_card c','b.id = c.sid')
            ->field("DATE_FORMAT(sale_time,'%c') month,count(c.id) as rows")
            ->where("DATE_FORMAT(sale_time,'%Y')=Year(CurDate())")
            ->where(['a.gid'=>$this->gid,'c.pay_status'=>1])
            ->group('month')
            // ->order('month ASC')
            ->select();
     $data = mfile($data,'rows');
     
     if(empty($data)) $this->result('',0,'数据为空');
     $this->result($data,1,'获取成功');
   }
  
  /**
   *  售卡总额
   * 
   */
  
  public function cardPrice()
  {
    $this->sPrice();
    $this->result($data,1,'获取成功');        
  }




  /**
   *  金额平均
   * 
   */
  
  public function MaxPrice()
  {
    $jarr = $this->jPrice();
    $sarr = $this->sPrice();

    $arr = array_merge($jarr,$sarr);
    
    $data = [];
    foreach ($arr as $key => $value) {
        
         if(!isset($data[$value['month']])){
               $data[$value['month']] = 0;
         } 
         $data[$value['month']] += $value['price'];
                   
    }

    if(empty($data)) $this->result('',0,'数据为空');
    $this->result($data,1,'获取成功');
 
  }
  

 	/**
	 *  维修厂数量
	 * 
	 */     
    
    public function FactorySize()
    {   
   
      $data = DB::table('ca_agent a')
              ->leftjoin('cs_shop b','a.aid = b.aid')
              ->where("DATE_FORMAT(FROM_UNIXTIME(b.audit_time,'%Y-%m-%d %H:%i:%S'),'%Y')=Year(CurDate())")
              ->where('a.gid',$this->gid)
              ->field("DATE_FORMAT(FROM_UNIXTIME(b.audit_time,'%Y-%m-%d %H:%i:%S'),'%c') month,count(b.id) rows")
              ->group('month')
              ->select();  
        if(empty($data)) $this->result('',0,'数据为空');
        $data = mfile($data,'rows');
        $this->result($data,1,'获取成功');    	
    }
   
  /**
   *   业务排名 搜索
   * 
   */   
   public function SaleSearch()
   {   
    
    $aid = input('post.aid');

    if(empty($aid)) $this->result('',0,'参数错误');
    $data = DB::table('ca_agent a')
            ->leftjoin('cs_shop b','a.aid = b.aid')
            ->leftjoin('u_card c','b.id = c.sid')
            ->field("DATE_FORMAT(sale_time,'%c') month,count(c.id) as rows")
            ->where("DATE_FORMAT(sale_time,'%Y')=Year(CurDate())")
            ->where(['a.aid'=>$aid,'c.pay_status'=>1,'a.gid'=>$this->gid])
            ->group('month')
            ->select();

     if(empty($data)) $this->result('',0,'数据为空');
     $data = mfile($data,'rows');
     $this->result($data,1,'获取成功');


   }  



 	/**
	 *  复购数 
	 * 
	 */ 
    
    public function RepeatSize()
    {

      $data = Db::table('ca_agent a')
              ->leftjoin('cs_shop b','a.aid = b.aid')
              ->leftjoin('u_card c','b.id   = c.sid')
              ->where("DATE_FORMAT(c.sale_time,'%Y')=Year(CurDate())")
              ->where(['a.gid'=>$this->gid,'c.pay_status'=>1])
              ->field("DATE_FORMAT(c.sale_time,'%c') month,(count(uid) - count(distinct uid)) rows") 
              ->group('month,c.uid')
              ->having('(count(c.uid)-1)>0')
              // ->distinct(true)
              ->select();
       
      if(empty($data)) $this->result('',0,'数据为空');
      $data = mfile($data,'rows');       
    	if(empty($data)) $this->result('',0,'数据为空');
        $this->result($data,1,'获取成功');  
    }

  /**
   *  消耗物料
   * 
   */ 
  
  public function Consume()
  { 
    $data = Db::table('cg_apply_materiel')
            ->where("DATE_FORMAT(FROM_UNIXTIME(audit_time),'%Y')=Year(CurDate())")
            ->where(['gid'=>$this->gid])
            ->whereIn('audit_status',[1,4])
            ->field("DATE_FORMAT(FROM_UNIXTIME(audit_time),'%c') as month,sum(size) rows")
            ->group('month')
            ->select();

    if(empty($data)) $this->result('',0,'数据为空');
    $data = mfile($data,'rows');       
    $this->result($data,1,'获取成功'); 
  }

  /**
   *  服务次数
   * 
   */ 
  
  public function Service()
  {
    $data = Db::table('ca_agent a')
            ->leftjoin('cs_shop b','a.aid = b.aid')
            ->leftjoin('cs_income c','b.id = c.sid')
            ->where("DATE_FORMAT(c.create_time,'%Y')=Year(CurDate())")
            ->where('a.gid',$this->gid)
            ->field("DATE_FORMAT(c.create_time,'%c') month,count(c.id) rows")
            ->group('month')
            ->select();                            

    if(empty($data)) $this->result('',0,'数据为空');
    $data = mfile($data,'rows');       
    $this->result($data,1,'获取成功');    
  }

  /**
   *  好评次数
   * 
   */ 
  
  public function GoodSize()
  {
    $data = DB::table('ca_agent a')
            ->leftjoin('cs_shop b','a.aid = b.aid')
            ->leftjoin('u_comment c','b.id = c.sid')
            ->where("DATE_FORMAT(c.create_time,'%Y')=Year(CurDate())")
            ->where('a.gid',$this->gid)
            ->field("DATE_FORMAT(c.create_time,'%c') month,count(c.id) rows")
            ->group('month')
            ->select(); 

    if(empty($data)) $this->result('',0,'数据为空');
    $data = mfile($data,'rows');       
    $this->result($data,1,'获取成功');
  }


  /**
   *  根据省市获取运营商列表
   *  根据aid 合并 发挥 array
   */
  
  public function suppArea()
  { 
     $area = input('post.area');
     if(empty($area)) $this->result('',0,'请求失败！参数错误');
     $data = DB::table('cg_area a')->join('cg_supply b' ,'a.gid = b.gid')->where('a.area',$area)->field('gid,company')->select();
     if(empty($data)) $this->result('',0,'此地区暂无数据');
     $this->result($data,1,'获取成功');
  }

  /**
   *  根据省市获取运营商列表  搜索功能
   *  
   */  
  
  public  function suppSer()
  {
     $data = input('post.');
     if(empty($data)) $this->result('',0,'请求失败！参数错误');
     $data = DB::table('cg_area a')->join('cg_supply b' ,'a.gid = b.gid')->where('a.area',$data['area'])->whereLike('b.company',$data['search'])->field('gid,company')->select();
     if(empty($data)) $this->result('',0,'此地区暂无数据');
     $this->result($data,1,'获取成功');
  }
 


  /**
   *  运营商汇总
   * 
   */ 
  
  public function OperatorList($size=0)
  {  
   

    // 获取 公司名称 交易金额 售卡总数 物料消耗 服务次数
     $data = $this->OperSumA();  
  
    // 获取 复购数
     $fg   = $this->OperSumB();

    // 获取 好评
     $good = $this->OperSumC();
    
     // 数组合并
     $arr =  $this->Polym($data,$fg,'rows');
     $arr =  $this->Polym($arr,$good,'goods');  
    
                        
    if(empty($arr)) $this->result('',0,'数据为空');
    $size = $size!=0?$size:50;

    $this->PolPage($arr,1,$size);
    $this->result($arr,1,'获取成功');

  }
 
  /**
   *  运营商前三
   * 
   */ 
  
  public function OperatOrder()
  {  
    $this->OperatorList(3);

  } 

  /**
   *  获取 公司名称 交易金额 售卡总数 物料消耗 服务次数
   *  
   */
  
  public function OperSumA()
  {
      // 获取 公司名称 交易金额 售卡总数 物料消耗 服务次数
     $data = Db::table('ca_agent a')
             ->leftjoin('cg_apply_materiel b','a.aid = b.aid')
             ->field('a.aid,a.company,sum(b.max) price,a.sale_card,sum(b.size) size,a.service_time,a.aid')
             ->order('a.sale_card DESC')
             ->where('a.gid',$this->gid)
             ->group('a.company')
             // ->having()
             ->select();
// print_r($data);die;
     return $data;
  }
  /**
   *  获取 复购数
   *  
   */
  
  public function OperSumB()
  {
     $data = Db::table('ca_agent a')
             ->leftjoin('cs_shop b','a.aid = b.aid')
             ->leftjoin('u_card c','b.id = c.sid')
             ->field('a.aid,count(c.id)-1 rows')
             ->group('b.id')
             ->where('a.gid',$this->gid)
             ->having('count(c.id)-1>0')
             ->select();
      if(empty($data)) return 'rows';
      return $data;
  }
  /**
   *  获取 好评
   *  
   */
  
  public function OperSumC()
  {
    // 获取 好评
     $data = Db::table('ca_agent a')
             ->leftjoin('cs_shop b','a.aid = b.aid')
             ->leftjoin('u_comment c','b.id = c.sid')
             ->field('a.aid,count(c.id) rows')
             ->group('b.id')
             ->where('a.gid',$this->gid)
             ->select();
      if(empty($data)) return 'goods';
      return $data;
  }  
  /**
   *  运营商汇总合并
   *  根据aid 合并 发挥 array
   */
  
  public function Polym($data,$fg,$field,$c=null)
  {                       
     
     if(!is_array($fg)){
         
         foreach ($data as $key => $value) {
                 $data[$key][$field] = 0;
         }
        return $data;
     }     

     foreach ($data as $k => $v) {
      
         foreach ($fg as $key => $value) {
 
              if($v['aid']==$value['aid']){
                // print_r($value);
                if(!isset($data[$k][$field]))
                {
                  $data[$k][$field] = 0;
                }

                $data[$k][$field] += $value['rows'];
                // $c[] = array_merge($v,$value);
              }

         }

     }

     // print_r($data);die;
    return $data;

  }

  /**
   *  运营商汇总合并
   *  根据aid 合并 发挥 array
   */
  public function PolPage($arr,$page=1,$size=50)
  {
      $length = count($arr);
      
      $rows = ceil($length/$size);
      $page = $page<1? 1:$page;
      $page = $page>$rows?$rows:$page;

      $offsize = $page*$size;
      $arr = array_slice($arr,0,$offsize);
      
      array_multisort(array_column($arr,'sale_card'),SORT_DESC,$arr);
      $this->result(['list'=>$arr],1,'获取成功');
  }


  /**
   *  交易金额
   * 
   */
  
  public function jPrice()
  {

    $data = Db::table('cg_apply_materiel')
              ->where("DATE_FORMAT(create_time,'%Y')=Year(CurDate())")
              ->where(['gid'=>$this->gid])
              ->whereIn('audit_status',[1,4])
              ->field("DATE_FORMAT(create_time,'%c') as month,sum(max) as price")
              ->group('month')
              ->select();
    if(empty($data)) $this->result('',0,'数据为空');
    $data = mfile($data,'price');       
    return $data;
  }

  /**
   *  售卡总额
   * 
   */
  
  public function sPrice()
  {
    $data = Db::table('cg_income')->where('gid',$this->gid)
            ->where("DATE_FORMAT(create_time,'%Y')=Year(CurDate())")
            ->field("DATE_FORMAT(create_time,'%c') month,sum(money) price")
            ->group('month')
            ->select(); 
    if(empty($data)) $this->result('',0,'数据为空');
    $data = mfile($data,'price');       
    return $data;    
  }


}