<?php 
namespace app\supply\controller;
use app\base\controller\supply;
use think\Db;

 /**
  *  运营商物料申请
  */
class Matter extends supply{

  /**
   * 运营商列表
   * @param   page  text
   * @return  json
   */  
  
  public function OperatorList()
  { 
     
    $text = input('post.text')?'%'.input('post.text').'%':'%';
     // print_r($text);die;
    $count = DB::table('ca_agent')->where('gid',$this->gid)->whereLike('company',$text)->count();
    if(empty($count)) $this->result('',0,'暂无数据');
    $page = input('post.page')? :1;
    $rows = ceil($count/8);
    $data = Db::table('ca_agent')->field('aid,company,leader,phone,close_end_time,sale_card,login')
            ->whereLike('company',$text)
            ->page($page,8)
            ->where('gid',$this->gid)
            ->select();
// dump($data);die;

     foreach ($data as $key => $value) {
      // print_r(strtotime($value['close_end_time']));die;
        if( strtotime($value['close_end_time']) >0){
           $data[$key]['close_end_time'] = Date("Y-m-d",strtotime($value['close_end_time']));
         }else{
           $data[$key]['close_end_time'] = '---';
         }
       
     }

    $this->result(['rows'=>$rows,'list'=>$data],1,'获取成功');
  }
  

  /**
   * 运营商详情
   * @param   id
   * @return  json
   */  
  
  public function OperaDetails()
  {
     $id = input('post.aid');
     if(empty($id)) $this->result('',0,'参数错误');
     $data = Db::table('ca_agent a')->join('ca_agent_set b','a.aid = b.aid')
             ->field('a.company,a.leader,a.province,a.city,a.county,a.phone,a.open_shop,b.complain_count,a.license,a.login')
             ->where(['a.aid'=>$id,'a.gid'=>$this->gid])
             ->find();

     // print_r( Db::table('ca_agent')->getlastsql() );die;
     if(empty($data)) $this->result('',0,'获取失败');
     $this->result($data,1,'获取成功');        
  }

  /**
   * 运营商搜索
   * @param   
   * @return  json
   */ 
 
  public function OperaSearch()
  {
      $this->OperatorList();

  }

  /**
   * 物料申请--申请中
   * @param   page 
   * @return  json
   */  
   
   public function MatterApp()
   {
      $page = input('post.page')? :1; 
      $this->MatterList($this->gid,0,$page); 
   }


  /**
   * 物料申请--已审核
   * @return  json
   */  
   
   public function MatterWork()
   {
      $page = input('post.page')? :1; 
      $this->MatterList($this->gid,1,$page,',a.audit_time'); 
   }

  /**
   * 物料申请--已通过
   * @return  json
   */  
   
   public function MatterEnd()
   {
      $page = input('post.page')? :1; 
      $this->MatterList($this->gid,2,$page,',a.end_time'); 
   }

  /**
   * 物料申请--驳回
   * @return  json
   */
  
  public function MatterReject()
  { 
    die;
    $id = input('post.id');
    $text = input('post.text');
    if(!$id || !$text) $this->result('',0,'参数错误');
    $res = Db::table('cg_apply_materiel')->where(['id'=>$id,'audit_status'=>0,'gid'=>$this->gid])->update(['audit_time'=>date('Y-m-d H:i:s',time()),'audit_status'=>3,'reason'=>$text]);
    if(!$res) $this->result('',0,'驳回失败');
    
    $data = Db::table('cg_apply_materiel a')
            ->join('ca_agent b','a.aid = b.aid')
            ->where('a.id',$id)
            ->field('b.phone,a.create_time')
            ->find();
    $phone = $data['phone'];
    $content = "您于【".$data['create_time']."】申请的物料,因【".$text."】被驳回，请完成修订后重新提交。";
    $this->smsVerify($phone,$content);

    $this->result('',1,'驳回成功');
  } 
   
  /**
   * 物料申请--详情
   * @return  json
   */     
   
   public function MDetails()
   {
     
      $id = input('post.id');
      if(empty($id)) $this->result('',0,'参数错误');
      $data = Db::table('cg_apply_materiel')->where(['gid'=>$this->gid,'id'=>$id])->field('detail,create_time')->json(['detail'])->find();
      $data['create_time'] =strtotime($data['create_time']."+3 day");

      $this->result($data,1,'获取成功');
   }

  /**
   * 物料申请--申请中 确定操作
   * @return  json
   */
  
  public function MatterFor()
  {

      // $res = Db::table('cg_stock')->where('gid',$this->gid)->whereIn('id',[17,18,19,20])->update(['materiel_stock'=>[66,77,88,99]]);
      //  dump($res);
      // die;
      $id = input('post.id');
      // 获取物料信息
      $data = DB::table('cg_apply_materiel')->where(['id'=>$id,'audit_status'=>0,'gid'=>$this->gid])->field('price,detail')->json(['detail'])->find();
      if(empty($data)) $this->result('',0,'请勿重复提交');
      // print_r($data);die;
      // 计算库存 返回需要减去 的物料id 和 升数
      $stock = $this->IsStock($data['detail']); 

      // 获取金额 和利润
      // $price = $this->IsPro($data['detail']);
      // $arr['price'] = $price['pro'];
      // $arr['max']   = $price['price'];
      // $arr['size']  = $price['num'];
      // $arr['audit_status'] = 1;
       
   // print_r($stock);exit();
       // Db::startTrans();

       // 修改库存
       foreach ($stock as $key => $value) {
         $res = Db::table('cg_stock')->where(['gid'=>$this->gid,'materiel'=>$value['materiel']])->setDec('materiel_stock',$value['materiel_stock']);
         if(!$res){
           DB::rollback();
           $this->result('',0,'确认失败');
         }
       }

       // 修改订单状态
       $res  = Db::table('cg_apply_materiel')->where(['id'=>$id,'gid'=>$this->gid])->update(['audit_status'=>1,'audit_time'=>time()]);
       // 增加供应商总收入
       $result = Db::table('cg_supply')->where('gid',$this->gid)->setInc('balance',$data['price']);
       //获取运营商手机号 
       $phone = Db::table('cg_apply_materiel a')
                ->join('ca_agent b','a.aid = b.aid')
                ->where('a.id',$id)
                ->value('b.phone');
        // 上线后注释掉测试手机号
        // $phone = 18831282055;
        $content = "您申请的货物已发出，请注意查收。";
        $this->smsVerify($phone,$content);
       
       if(!$res){
          DB::rollback();
          $this->result('',0,'确认失败');
       }
       Db::commit();
       $this->result('',1,'确认成功');        



  }

  /**
   * 物料申请确定  计算库存是否充足
   * $data 数据   $ids 物料id  $price 物料总价钱 $pro 利润
   * @return  json
   */
  
  public function IsStock($data,$arr=null)
  {  

     // 获取库存件数
     $stock = Db::table('cg_stock')->where('gid',$this->gid)->field('id,materiel,materiel_stock sum')->select();
     $stock = $this->giftsum($stock);

     // 判断库存是否充足 ， 充足的话返回 物料id 和修改后的数量
     foreach ($data as $key => $value) {
          
         foreach($stock as $k => $v) {
             if($value['materiel_id']==$v['materiel'])
             {
               $sum = $v['num']-$value['num'];
               if($sum<0){
                 $this->result('',5,'库存不足');
               }else{

                 // 物料id
                 $arr[$k]['materiel'] = $v['materiel'];
                 // 物料数量( 件 ) 
                 if($v['materiel'] == 7)
                 {
                   $arr[$k]['materiel_stock'] = $value['num']; 
                 }else{
                   $arr[$k]['materiel_stock'] = $value['num']*12; 
                 }
                 
                 
               }

             }

         }

     }
 
    return $arr;

  }
  
  // 大礼包不计算 件数
    public function giftsum($data)
    {
     
  
          foreach ($data as $key => $value) {
              if($value['materiel'] != 7)
              {
                $data[$key]['num'] = floor($value['sum']/12);
                // $data[$key]['materiel_stock'] = floor($value['materiel_stock']/12);
              }else{
                $data[$key]['num'] = $value['sum'];
              }
             
          } 

          return $data;
    }

  /**
   * 物料申请确定  计算金额
   * $data 数据   $ids 物料id  $price 物料总价钱 $pro 利润
   * @return  json
   */
  
  public function IsPro($data,$ids=null,$price=0,$pro=0,$sum=0)
  {   
     // 获取物料 id               
     foreach ($data as $key => $value) {
    
          $ids[]  = $value['materiel_id'];
     }
     
     // 获取物料价钱(每件)
     $psonm = Db::table('co_bang_cate')->whereIn('id',$ids)->field('id,set_price')->select();
     
     foreach ($data as $k => $v) {
          
          foreach ($psonm as $key => $value) {
               
               if($v['materiel_id']==$value['id'])
               { 
                 // 计算物料金额
                 $data[$k]['price'] = $v['num']*$value['set_price'];
                 // 计算总金额
                 $price += $v['num']*$value['set_price'];
                 // 计算总件数
                 $sum += $v['num'];
               }


          }
     }     
     // 获取利率 计算利润
     $pro = Db::table('cg_supply_set')->where('gid',$this->gid)->value('profit');
     $pro = $price*($pro/100);
     // 添加总金额
     DB::table('cg_supply')->where('gid',$this->gid)->setInc('balance',$Pro);
     return ['price'=>$price,'pro'=>$pro,'num'=>$sum];

  }





  /**
   * 物料申请数据  公共
   * @param   $status 0申请中 1发货中 2已通过 3驳回
   * @return  json
   */      
   
   public function MatterList($gid,$status,$page=1,$field=null)
   {   
       $text = input('post.text')?'%'.input('post.text').'%':'%';

       $size = 8;
// 搜索去除分页
if(input('post.text'))
{
  $size = 333;
}       
       $count = DB::table('cg_apply_materiel')->where(['gid'=>$this->gid,'audit_status'=>$status])->count();
       $row = ceil($count/$size);
       $this->isNull($row);
       $data = DB::table('cg_apply_materiel a')
               ->join('ca_agent b','a.aid = b.aid')
               ->where(['a.gid'=>$gid,'a.audit_status'=>$status])
               ->field('a.id,b.company,b.leader,b.phone,a.create_time,a.audit_status'.$field)
               ->whereLike('b.company',$text)
               ->page($page,$size)
               ->select();
if(isset($data[0]['create_time']))
{
foreach ($data as $k => $v) {

       $data[$k]['djs'] = Date("Y-m-d H:i:s",strtotime($v['create_time']."+3 day")); 
}

}


if(input('post.text'))
{ 
  $this->result(['list'=>$data],1,'获取成功');
}        
$this->result(['rows'=>$row,'list'=>$data],1,'获取成功'); 

   }

  /**
   * 获取 订单物料详情 公共
   * @param   $status 0申请中 1发货中 2已通过 3驳回
   * @return  array
   */ 
  
  public function MatterDetails($id,$status)
  {
    
    $data = Db::table('cg_apply_materiel')
            ->where(['id'=>$id,'gid'=>$this->gid])
            ->whereIn('audit_status',$status)
            ->value('detail');
    
    if($data==null) return null;
    $data = json_decode($data,true);
    
    if(isset($data['list'])) return $data['list'];

    return $data;
  }










}