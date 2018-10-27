<?php 
namespace app\supply\controller;
use app\base\controller\supply;
use think\Db;

/**
 *  供应商申请物料
 */
class Stocks extends supply{
    
    /*
     *  根据地区获取供应商列表
     * 
     */
    
    public function Asupply()
    {
        $area = input('post.area');
        if(empty($area)) $this->result('',0,'参数错误');
        $data = Db::table('cg_supply')->where('province',$area)->field('gid,company')->select();
        if(empty($data)) $this->result('',0,'此地区暂无供应商');
        $this->result($data,1,'获取成功');
    }

    /*
     *  根据地区获取供应商列表搜索
     * 
     */    
    
    public function seaSupply()
    {
       
       $company = input('post.search');
       if(empty($company)) $this->result('',0,'参数错误');
       $data = Db::table('cg_supply')->where('company',$company)->field('gid,company')->find();
       if(empty($data)) $this->result('',0,'搜索结果为空');
       $this->result($data,1,'获取成功');

    }


    /**
     * 物料库存  已修改
     * @return json
     */
    public function SuppStock()
    {   
       // 获取物料升数
       $data = $this->StChe();
       
       if(!$data) $this->result('',0,'获取库存失败');
       $this->result($data,1,'获取成功');

    } 
    

    /**
     * 申请物料默认值 已修改
     * @return json
     */
                                                                                                                                                                    
      public function supplyCheck()
      {  
                                 
         $data = $this->suCheck();


         $this->result($data,1,'获取成功');
      }

  
    /**
     * 申请物料确认操作 已修改
     * @return json
     */
    
    public function supplyStock()
    {   
        $datas = input('post.');
       
        //  判断是否存在订单
        $this->isStock();
        //  获取申请物料件数
        $data = $this->suCheck();
    
        // 获取备注
         
        $remarks = $datas['remarks'];
        $id = $datas['id'];
         
        // 合并数组
        $arrs = [];

        foreach ($remarks as $key => $value) {
            $arrs[$key]['id'] = $id[$key];
            $arrs[$key]['remarks'] = $value;
        }

        if(empty($remarks)) $this->result('',0,'物料备注不能为空');

                                
        // 添加备注
        foreach ($data as $key => $value) {

             foreach ($arrs as $k => $v) {
                
                 if($value['id']==$v['id'])
                 {
                   $data[$key]['remarks'] = $v['remarks'];
                 }

             }
             
        }

      
        // 填充数组
        $arr['gid']     = $this->gid;
        $arr['number']  = build_only_sn();
        $arr['details'] = json_encode($data);
        $arr['size']    = $this->SuppSize($data);

        // 入库操作
        $res = Db::table('cg_company')->insert($arr);
        if($res) $this->result('',1,'申请成功，请等待总后台审核');
        $this->result('',0,'申请失败，网络繁忙');
          
    }

      
    /**
     * 申请物料列表  未审核
     * @return json
     */
    
       public function MattSupply()
       { 

         $this->SupRoot(0);
       } 


    /**
     * 申请物料列表  送达中
     * @return json
     */
    
       public function MattStop()
       { 

         $this->SupRoot(1,',to_time');
       } 


    /**
     * 申请物料列表  已完成
     * @return json
     */
    
       public function MattCom()
       { 

         $this->SupRoot(4,',over_time');
       } 



    /**
     * 申请物料列表 驳回
     * @return json
     */
    
       public function MattReject()
       { 

         $this->SupRoot(2,',to_time,Auditor,Origin');
       }        



    /**
     * 申请物料列表 详情
     * @return json
     */
    
      public function MattDetails()
      {
         $id = input('post.id');
         $this->isNull($id);
         $data = Db::table('cg_company')->where(['id'=>$id,'gid'=>$this->gid])->value('details');
                                                 
         if(empty($data)) $this->result('',0,'数据为空');
         $this->result(json_decode($data,true),1,'获取成功');
      } 

    /**
     * 申请物料列表 确认收货
     * @return json
     */
     
    public function MattConfirm()
    {
        $id = input('post.id');
        if(empty($id)) $this->result('',0,'参数错误');
        $data = Db::table('cg_company')->where(['gid'=>$this->gid,'id'=>$id])->json(['details'])->field('gid,details')->find();
        if(empty($data)) $this->result('',0,'确认失败');
        
        // 启动事务
        Db::startTrans();
        try {
          
        
          foreach ($data['details'] as $key => $value) {
              
              if($value['id'] != 7){
                $sum = $value['size']/12;
              }

              Db::table('cg_stock')->where([ 'materiel'=>$value['id'],'gid'=>$data['gid'] ])->inc('materiel_stock',$sum);
              Db::table('cg_company')->where([ 'gid'=>$Data['gid'],'id'=>$id ])->update(['status'=>4]);
          }
          // 提交事务
          Db::commit();
          $this->result('',1,'确认成功');
        } catch (Exception $e) {
           // 回滚事务
           Db::rollback();        
           $this->result('',0,'确认失败');
        }
       

    } 





      /**
     * 申请物料列表
     * @param  $status 0 未审核 1已审核 2驳回
     * @return json
     */
    
    public function SupRoot($status,$field='')
    {    
       
          $page = input('post.page')? :1;
      
          $size = 8;

          $count = DB::table('cg_company')->where(['gid'=>$this->gid,'status'=>$status])->count();
          // $this->isNull($count);
          if(empty($count)) $this->result('',0,'暂无数据');
          $rows = ceil($count/$size);
          $data = Db::table('cg_company')->where(['gid'=>$this->gid,'status'=>$status])
                  ->field('id,number,create_time,status'.$field)
                  // ->whereLike('company',$text)
                  ->page($page,$size)
                  ->select();
               
          $this->result(['rows'=>$rows,'list'=>$data],1,'获取成功');
          


    }


    /**
     * 获取申请件数model  已修改
     * @return json
     */
    
    public function SuppSize($data,$sum=0)
    {
        
        foreach ($data as $key => $value) {
           if($value['id'] != 7)
           {
              $sum += $value['size'];
           }else{
              $sum += ceil($value['size']/2);
           }
           
        }
        
        if($sum<=0) $this->result('',0,'申请失败，网络繁忙');
        return $sum;
    }


     /**
      * 申请物料默认值 model
      * 
      * @return json
      */

      public function suCheck()
      {
         
         // 获取库存升数
         $data = $this->StChe(); 
         $arr = [];

         foreach ($data as $k => $v) {
            
            // $arr = 1;
            // 判断进入预警的物料
            $num = $v['warning']-$v['size'];
            if($num >= 0)
            {   

               // 获取差集( 升 / 台 )
               $sum = $v['ration'] - $v['size'];

               // 如果为 大礼包时 不做改变
               if($v['id'] != 7){
                 $sum = floor($sum/12);
               }
               
               // 构造入库数组
               $arr[$k]['id']   = $v['id'];     // 物料id
               $arr[$k]['name'] = $v['name'];   // 物料名称
               $arr[$k]['size'] = $sum;         // 物料件数
            }
                      
         }
         
         if(empty($arr)) $this->result('',0,'库存充足');
         return array_values($arr);
      }



    /**
     * 获取库存件数 model
     * @return json
     */

    public function StChe()
    {  

       $data = Db::table('cg_stock a')
               ->join('co_bang_cate b','a.materiel = b.id')
               ->field('b.id,b.name,a.materiel_stock size,a.warning,ration')
               ->where('a.gid',$this->gid)
               ->select();
        
        if(empty($data)) $this->result('',0,'暂无库存');
        return $data;
    }

    /**
     * 判断是否存在未审核订单
     * @return json
     */
    
    public function isStock()
    {
                                        
      $res = Db::table('cg_company')->where(['gid'=>$this->gid,'status'=>0])->value('gid');
      if($res) $this->result('',0,'存在未审核物资，请先等待总后台审核');
      $res = Db::table('cg_company')->where(['gid'=>$this->gid,'status'=>4])->value('gid');
      if($res) $this->result('',0,'您还有物料未送达，请等待');
    }

    /**
     *  为大礼包时 修改台数
     * 
     */  
    public function giftsum($data)
    {
     
     // print_r($data);die;
          foreach ($data as $key => $value) {
              if($value['id'] != 7)
              {
                $data[$key]['size'] = floor($value['size']/12);
                // $data[$key]['materiel_stock'] = floor($value['materiel_stock']/12);
              }
             
          } 

          return $data;
    }

}