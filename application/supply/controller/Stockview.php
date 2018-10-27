<?php 
namespace app\supply\controller;
use app\base\controller\supply;
use think\Db;

/**
 *  物料库存
 * 
 */
class Stockview extends supply
{
    
    


    /**
     *  供应库存
     * 
     */
    
    public function GStock()
    {
       
        // 获取物料 id 名称   库存  （升）
        $data =  $this->RaStock();

        // 获取期初配给(件)
        $pdata = $this->EarTer();

        $data  = $this->MaterMerage($data,$pdata,'rations','sum','materiel_id');

        // 获取配送物料
        $pdata = $this->Pmater('cg_apply_materiel','detail');
     
        // 合并配送物料
        $data  = $this->MaterMerage($data,$pdata,'psize','sum','materiel_id');

        // 获取物料补充(件)
        $mdata = $this->Mlement();  

        // 合并物料补充
        $data  = $this->MaterMerage($data,$mdata,'bsize','sum','materiel_id');
    
        // 获取增加配给物料(件)
        $zdata = $this->MRation();
        
        // 合并 增加配给物料
        $data  = $this->MaterMerage($data,$zdata,'jsize','sum','materiel_id');
        
        if(empty($data)) $this->result('',0,'暂无供应库存');
        $this->result($data,1,'获取供应库存成功');

    }

    /**
     *  期初配给
     * 
     */
    
    public function StageStock()
    {  
       
       $page = input('post.page')? :1;
       $count = DB::table('cg_increase')
                ->where(['gid'=>$this->gid,'audit_status'=>1])
                ->count();

       if($count==0) $this->result('',0,'暂无期初配给');
       $rows = ceil($count/8);
       $data = Db::table('cg_increase')
               ->where(['gid'=>$this->gid,'audit_status'=>1])
               ->json(['details'])
               ->field('FROM_UNIXTIME(audit_time) time,area,details')
               ->page($page,8)
               ->select();

       // 获取地区
       $data = $this->SUpparea($data);
       // 重构数组
       $data = $this->EarRebort($data);

       // 修改键名
       $data = $this->SuppKey($data);
       $this->result(['rows'=>$rows,'list'=>$data],1,'获取期初配给成功');
    }

    /**
     *  期初配给 -- 获取地区
     * 
     */
    
    public function Supparea($data,$arr=[])
    {   
        // 获取地区id
        $area = array_column($data, 'area');
        
        foreach ($area as $key => $value) {
             // $arr[]  = explode(',',$value);
             $result = Db::table('co_china_data')->whereIn('id',explode(',',$value))->column('name');
             $result = implode(' ',$result);
             $data[$key]['area'] = $result;
        }
       // print_r($data);exit;
        return $data;
    }    

	/**
	 *  物料配送
	 * 
	 */
    
    public function SuppStock()
    {   
        $page = input('post.page')? :1;
        $count = Db::table('cg_apply_materiel')
                ->where("DATE_FORMAT(FROM_UNIXTIME(audit_time),'%Y')=Year(CurDate())")
    	          ->where(['gid'=>$this->gid])
                ->whereIn('audit_status',[1,4])
    	          ->count();
    	        // print_r($count);exit;
        if($count==0) $this->result('',0,'数据为空');
        $rows = ceil($count/8);
    	$data = DB::table('cg_apply_materiel a')
    	        ->join('ca_agent b','a.aid = b.aid')
    	        ->json(['detail'])
    	        ->field('a.create_time,a.audit_time,b.company,detail')
    	        ->where("DATE_FORMAT(FROM_UNIXTIME(a.audit_time),'%Y')=Year(CurDate())")
    	        ->where(['a.gid'=>$this->gid])
              ->whereIn('a.audit_status',[1,4])
    	        ->page($page,8)
    	        ->select();

    	if($data===false) $this->result('',0,'获取数据失败');
        // 重构数组
      $data = $this->conRebort($data);
      $data = $this->SuppKey($data);  
    	$this->result(['rows'=>$rows,'list'=>$data],1,'获取数据成功');
    }

	/**
	 *  物料补充
	 * 
	 */    
    
    public function Lement()
    {
    	$page = input('post.page')? :1;
    	$count = Db::table('cg_company')->where("DATE_FORMAT(create_time,'%Y')=Year(CurDate())")
    	         ->where(['gid'=>$this->gid,'status'=>1])->count();
    	if($count==0) $this->result('',0,'数据为空');
      $rows = ceil($count/8);
    	$data = Db::table('cg_company')
    	         ->where("DATE_FORMAT(create_time,'%Y')=Year(CurDate())")
    	         ->where(['gid'=>$this->gid,'status'=>1])
    	         ->json(['details'])
    	         ->field('details,number,to_time')
    	         ->page($page,8)
    	         ->select();
    	if($data===false) $this->result('',0,'获取数据失败');
        // 重构数组
        $data = $this->suRebort($data); 
        $data = $this->SuppKey($data); 
        foreach ($data as $key => $value) {
            $data[$key]['create_time'] = Date("m",$value['to_time']);
         } 
    	$this->result(['rows'=>$rows,'list'=>$data],1,'获取数据成功');    	
    }


	/**
	 *  物料库存
	 * 
	 */
    
    public function wStock()
    {

       $data = $this->RaStock();
       // $arr = [];
       $key = array_column($data,'id');
       $value = array_column($data,'materiel_stock');
       $arr = array_combine($key,$value);

       foreach ($arr as $key => $value) {
 
            $arr['smhalf']  = $arr[2];
            $arr['smclose'] = $arr[3];
            $arr['smwhole'] = $arr[4];
            $arr['snfat']   = $arr[2];
            $arr['gift']    = $arr[7];
       }

       $this->result(['rows'=>1,'list'=>$arr],1,'获取物料库存成功');

    }	

	/**
	 *  地区增加  增加配给
	 * 
	 */
	
    public function IconS()
    {   
        $page = input('post.page')? :1;

        $count = Db::table('cg_increase')
                 ->where(['gid'=>$this->gid,'audit_status'=>1])
                 ->where("DATE_FORMAT(create_time,'%Y')=Year(CurDate())")
                 ->count();

        if(empty($count)) $this->result('',0,'数据为空');    
        $rows  = ceil($count/8);
            
        $data  = DB::table('cg_increase')
                 ->where(['gid'=>$this->gid,'audit_status'=>1])
                 ->where("DATE_FORMAT(create_time,'%Y')=Year(CurDate())")
                 ->page($page,8)
                 ->field('id,area,regions,price,audit_time,details')->json(['details'])
                 ->select();

    	if($data===false) $this->result('',0,'获取数据失败');
    
        // 修改数组格式
        $data = $this->Rebort($data);    
        $data = $this->SuppKey($data);  
    	$this->result(['rows'=>$rows,'list'=>$data],1,'获取数据成功'); 


    }

    /**
     *  地区增加  地区详情
     * 
     */
    
    public function inCon()
    {
       $id = input('post.id');
       if(empty($id)) $this->result('',0,'参数为空');

       $ids = Db::table('cg_increase')->where('id',$id)->value('area');

       $data = explode(',', $ids);
       
       $data = DB::table('co_china_data')->whereIn('id',$data)->column('name,pid');
       $data2 = DB::table('co_china_data')->whereIn('id',array_values($data))->value('name');
       
       foreach ($data as $key => $value) {
        
           $data[$key] = $data2.$key;
       }
       $data = array_values($data);
       
       $this->result($data,1,'获取成功');

    }    


    /*
     *   增加配给数组重构
     *  
     * 
     */
    
    public function Rebort($data)
    {  
      
       $array = [];

       // 构造行数组
       foreach ($data as $key => $value) {  
          if(!empty($value['details'])){
           foreach($value['details'] as $k=>$v)
           {
             $array[$key][$v['materiel_id']]    = $v['num']; 
             $array[$key]['regions']    = $value['regions'];
             $array[$key]['price']      = $value['price'];
             $array[$key]['id'] = $value['id'];
             if(!empty($value['audit_time']))
             {
                $array[$key]['audit_time'] = Date('Y/m/d',$value['audit_time']);
             }else{
                $array[$key]['audit_time'] = 0;             
             
             }
            }
            }
       }
     
       $array = $this->Mfiles($array);
       return $array;

    }   


    /*
     *   物料补充数组重构
     *  
     * 
     */
    
    public function suRebort($data)
    {
        
        $array = [];

        // 重构数组
        
        foreach ($data as $key => $value) {
             
             foreach ($value['details'] as $k => $v) {
                  
                  $array[$key][$v['id']] = $v['size'];
                  $array[$key]['to_time']  = $value['to_time']; 
                  $array[$key]['number'] = $value['number'];       
             }            

        }

        return $this->Mfiles($array);


    }

    /*
     *   物料配送数组重构
     *  
     * 
     */
    
    public function conRebort($data)
    {
       
        $array = [];

        // 重构数组
        
        foreach ($data as $key => $value) {
             
             foreach ($value['detail'] as $k => $v) {
                  
                  $array[$key][$v['materiel_id']] = $v['num'];
                  $array[$key]['create_time']  = $value['create_time']; 
                  $array[$key]['audit_time']  = $value['audit_time'];   
                  $array[$key]['company']  = $value['company'];          
             }            

        }
        return $this->Mfiles($array);        

    }    

    /*
     *   查找 未配给的物料  填充为0
     *   
     * 
     */
    
    public function Mfiles($data)
    {
       $ids = Db::table('co_bang_cate')->where('pid','<>',0)->column('id');
       // 查找 未配给的物料  填充为0
       foreach ($data as $key => $value) {
          foreach ($ids as $k => $v) {
              if(!isset($value[$v]))
              {
                   $data[$key][$v] = 0;
              }
          }
       }  
       return $data;       
    }



    /**
     *  初期配给 (本年)
     *  @return  数组 或者 0  [<description>]
     */
    
    public function EarTer()
    {
      $data = DB::table('cg_increase')
      ->where("DATE_FORMAT(create_time,'%Y')=Year(CurDate())")
      ->where(['gid'=>$this->gid,'audit_status'=>1])->field('details')->json(['details'])->select();
      if(empty($data[0])) return 0;

      return $this->ResArray($data,'details','materiel_id','num');
    }


    /**
     *  获取供应库存
     *  return array
     */
    
    public function RaStock()
    {

        $data = Db::table('cg_stock a')
                ->join('co_bang_cate b','a.materiel = b.id')
                ->field('b.id,b.name,a.ration,a.materiel_stock')
                ->where('gid',$this->gid)
                ->order('b.id')
                ->select();       
        // 为大礼包时 ， 不做改动
        $data = $this->giftsum($data);

        if(empty($data)) $this->result('',0,'暂无供应库存');
        return $data; 
    }


    /**
     *  配送物料 (本年)
     *  @return  数组 或者 0  [<description>]
     */
    
    public function Pmater()
    {
       $data = Db::table('cg_apply_materiel')
               ->where("DATE_FORMAT(create_time,'%Y')=Year(CurDate())")
               ->where(['gid'=>$this->gid])->whereIn('audit_status',[1,4])->field('detail')->json(['detail'])->select();

       if(empty($data[0]['detail'])) return 0;

       // 重构数组
       return $this->ResArray($data,'detail','materiel_id','num');
        
    }

    /**
     *  物料补充 (本年)
     *  @return  数组 或者 0  [<description>]
     */
    
    public function Mlement()
    {
        $data = DB::table('cg_company')
                ->where("DATE_FORMAT(create_time,'%Y')=Year(CurDate())")
                ->where(['gid'=>$this->gid,'status'=>1])->field('details')->json(['details'])->select();

       if(empty($data[0]['details'])) return 0;
       // 重构数组
       return $this->ResArray($data,'details','id','size');
      
    }
     
    /**
     *  增加配给 (本年)
     *  @return  数组 或者 0  [<description>]
     */
    
    public function MRation()
    {
        $data = Db::table('cg_increase')->where(['gid'=>$this->gid,'audit_status'=>1])
                ->where("DATE_FORMAT(create_time,'%Y')=Year(CurDate())")
                ->field('details')->json(['details'])->select();
 
       if(empty($data[0]['details'])) return 0;
       // 重构数组
       return $this->ResArray($data,'details','materiel_id','num');               
    }

    /*
     *  数组重构
     *   $deatails json 字段名 ， $id 物料id名   $size 物料数量名
     * 
     */
    
    public function ResArray($data,$details,$id,$size)
    {
       
       // print_r($data);die;
        // 合并数组
        $arr = [];
        foreach ($data as $k => $v) {
            
            if(!empty($v[$details])){
            foreach ($v[$details] as $key => $value) {

                if(!isset($arr[$value[$id]])){
                    $arr[$value[$id]] = 0;
                }
                $arr[$value[$id]] +=$value[$size];

            }
          }
        }
        
        // 重构数组
        $array = [];
        foreach ($arr as $key => $value) {
            
            $array[$key]['materiel_id'] = $key;
            $array[$key]['sum'] = $value;
        }

        $array = array_values($array);
       
        return $array;           
           
     
    }
 


    /**
     *  合并数组
     *  现合并按库存走 ， 库存内不存在的物料 ， 就算申请物料内有， 也不会出现
     *  arr 要返回的主数组 。 arr1 要合并的数组
     *  field 要生成的key     field2 要增加的key  field3 要比较的value
     */
    
    public function MaterMerage($arr,$arr1,$field,$field2,$field3)
    {  

        // 数据为空 构造为0
        if(empty($arr1)){
            // dump($arr);die;
            foreach ($arr as $key => $value) {
                    $arr[$key][$field] =  0 ;
            }
            return $arr;
        }
  
  // dump($arr);die;
        // 数量相加
        foreach ($arr as $key => $value) {
            
           foreach ($arr1 as $k => $v) {

              if($value['id']==$v[$field3])
              {                

                 if(!isset($arr[$key][$field])){
                     $arr[$key][$field] = 0;
                 }
                 $arr[$key][$field] += $v[$field2];

              }else{
                if(!isset($arr[$key][$field])){
                     $arr[$key][$field] = 0;
                 }
              }

           }

        }
  
        return $arr;

    }


   /*
     *   期初配给 -- 数组重构
     *  
     * 
     */
    
    public function EarRebort($data)
    {
       
        $array = [];

        // 重构数组
        // print_r($data);exit;

        foreach ($data as $key => $value) {
             if(!empty($value['details'])){
             foreach ($value['details'] as $k => $v) {
                  
                  $array[$key][$v['materiel_id']] = $v['num'];
                  $array[$key]['area']  = $value['area']; 
                  $array[$key]['time']  = $value['time'];          
            	}            
             }
        }

        // print_r($array);exit;
        return $this->Mfiles($array);        

    } 



    /**
     *  更改键明
     * 
     */   
    public function SuppKey($data)
    {
        foreach ($data as $key => $value) {
            // dump($value[2]);
            $data[$key]['smhalf'] = $value[2];
            $data[$key]['smclose'] = $value[3];
            $data[$key]['smwhole'] = $value[4];
            $data[$key]['snfat'] = $value[2];
            $data[$key]['gift'] = $value[7];
            unset($data[$key][2]);
            unset($data[$key][3]);
            unset($data[$key][4]);
            unset($data[$key][2]);
            unset($data[$key][7]);
        }
      return $data;
    }
    
    /**
     *  为大礼包时 修改台数
     * 
     */  
    public function giftsum($data)
    {
 
          foreach ($data as $key => $value) {
              if($value['id'] != 7)
              {
                $data[$key]['ration'] = floor($value['ration']/12);
                $data[$key]['materiel_stock'] = floor($value['materiel_stock']/12);
              }
             
          } 

          return $data;
    }


}