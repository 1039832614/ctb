<?php
namespace app\admin\controller;
use app\base\controller\Admin;
// use think\Controller;
use think\Db;

use pinyin\pinyin;
class Bangcar extends Admin
{
   

    public function brand_delete()
    {
       $id = input('post.id');
       if($id){
        Db::startTrans();
          $res = Db::table('co_car_menu')->delete($id);
          $a =  DB::table('co_car_cate')->where('brand',$id)->column('id');
          
          if($a){

              $res2 = Db::table('co_car_cate')->where('brand',$id)->delete();
              $res3 = DB::table('co_bang_data')->delete($a);

              if(!$res || !$res2 || !$res3 || !$a){
                DB::rollback();
                 $this->result('',0,'删除失败');
              }else{
                // 日志写入
                $name = DB::table('co_car_menu')->where('id',$id)->value('name');
                $GLOBALS['err'] = $this->ifName().'删除了'.$name.'品牌'; 
                $this->estruct();
                Db::commit();
                 $this->result('',1,'删除成功');
              }

          }else{
              if($res){
                 Db::commit();
                 $this->result('',1,'删除成功');
              }else{
                 DB::rollback();
                 $this->result('',0,'删除失败');
              }
                 
          }
          

       
        }
    }
    /**
		 * 品牌搜索
		 * @param  
		 * @return 
	  */
     
    public function CarBrand()
    {        
             $kt = input('post.kt');     
             if($kt)
             {
                 $res = Db::table('co_car_menu')->whereLike('ename',"$kt%")->select();
                 if (empty($res)) {       
                         $this->result('',0,'此选项没有数据');
                    }else{  
                         $this->result($res,1,'请求成功');
                    }   
             }else{  
                  $this->result('',0,'参数为空');
             }
    }


    
   /**
		 * 车型列表
		 * @param  ID  page
		 * @return 
	*/
   
    public function CarPages()
    { 
        
         $id   = input('post.id');
         $page = input('post.page')? :1;
         
         if ($id) {
            
            $count = DB::table('co_car_menu a')
                  ->join('co_car_cate b','a.id = b.brand')
                  ->join('co_bang_data c','b.id = c.cid')
                  ->where('a.id',$id)
                  ->count();   
          $max = ceil($count/10);
          
          
          $data = DB::table('co_car_menu a')
                  ->join('co_car_cate b','a.id = b.brand')
                  ->join('co_bang_data c','b.id = c.cid')
                  ->where('a.id',$id)
                  ->page($page,10)
                  ->select();         
 


            $this->result(['list'=>$data,'rows'=>$max],1,'获取列表成功');    
 
         }else{
         	$this->result('',0,'参数为空');
         }

    }
   
   public function ddd()
   {
    $id = input('post.id');
    if($id){
        $data = Db::table('co_car_menu')->where('id',$id)->field('id,name')->find();
        if($data){
          $this->result($data,1,"获取成功");
        }else{
          $this->result('',0,"false");
        }
    }else{
     $this->result('',0,"false"); 
    }
   }

   /**
		 * 车辆品牌添加
		 * @param  
		 * @return 
	*/

    public function CarBrandAdd()
    {

          $name = input('post.name');
  
          if (empty($name))  $this->result('',0,'参数为空');
            
          $d = Db::table('co_car_menu')->where('name',$name)->select();
          if ($d!=null) {
              $this->result('',0,'此品牌已添加');
          }

          $foo = new PinYin();

          $arr = [
             'name'  => $name,
             'ename' => $foo->pinyin($name),
             'abbr'  => $foo->addrs($name)
          ];
          $res = Db::table('co_car_menu')->strict(false)->insert($arr);
          
          if ($res) {
              // 日志写入
            $GLOBALS['err'] = $this->ifName().'添加了'.$name.'品牌'; 
            $this->estruct();
          	 $this->result($name,1,'添加成功');
          }else{
          	 $this->result('',0,'失败');
          }
           
    }
    


    /**
		 * 车辆品牌修改
		 * @param  id name
		 * @return 
	*/

    public function CarBrandSave()
    {
          
          $name = input('post.name');
          $id = input('post.id');
          $d = Db::table('co_car_menu')->where('name',$name)->select();
          if ($d!=null) {
              $this->result('',0,'此品牌已存在');
          }
          if ($name && $id) {
          	 
	          $foo = new PinYin();
	          $arr = [
	             'name'  => $name,
	             'ename' => $foo->pinyin($name),
	             'abbr'  => $foo->addrs($name)
	          ];
	          $res = Db::table('co_car_menu')->where('id',$id)->strict(false)->update($arr);
	           
	          if ($res) {
              // 日志写入
                $lname = Db::table('co_car_menu')->where('id',$id)->value('name');
                $GLOBALS['err'] = $this->ifName().'将品牌'.$lname.'修改成'.$name; 
                $this->estruct();
	          	 $this->result($name,1,'修改成功');
	          }else{
	          	 $this->result('',0,'失败');
	          }

          }else{
          	 $this->result('',0,'参数为空');
          }
           
    }



    /**
		 * 车辆型号添加
		 * @param  id data
		 * @return 
	*/

    public function CarModel()
    {
        $data = input('post.');
        $validate = validate('Carb');

        if ($validate->check($data)) {
     
            Db::startTrans();

            $type = DB::table('co_car_cate')->strict(false)->insertGetId($data);
            $data['cid'] = $type;
            $con = DB::table('co_bang_data')->strict(false)->insert($data);

            if ($type && $con) {
                // 日志写入
                $GLOBALS['err'] = $this->ifName().'添加了'.$data['type'].'车型'; 
                $this->estruct();
               DB::commit();
               $this->result('',1,'添加成功');
            }else{
            	Db::rallback();
              $this->result('',0,'添加失败');
            }
           
        }else{
          $this->result('',0,$validate->getError());
        }


    }
    
 //    /**
	// 	 * 车辆信息修改
	// 	 * @param  
	// 	 * @return 
	// */

 //    public function CarSave()
 //    {
 //         $data = input('post.');

 //         $validate = validate('Carbsave');

 //         if ($validate->check($data)) {
         	 
 //           // echo $data['key'];die;
 //         	$res = Db::table('co_car_cate a')
 //                 ->where('a.id',$data['id'])->update(['b.oil'=>'ddd'])->join('co_bang_data b','a.id,b.cid');

 //            if ($res) {
               
 //               $this->result('',1,'修改成功');

 //            }else{

 //                $this->result('',0,'失败');

 //            }

 //         }else{


 //           $this->result('',0,$validate->getError());


 //         }
 

 //    }

  /**
   * 车辆信息删除
   * @param  
   * @return 
  */
 public function de()
 {
  // echo 1;die;
  $id = input('post.id');

  if($id){
    Db::startTrans();
     $res = Db::table('co_car_cate')->where('id',$id)->delete();
     $res2 = Db::table('co_bang_data')->where('cid',$id)->delete();
     
     if($res && $res2){
        // 日志写入
        $name = Db::table('co_car_cate')->where('id',$id)->value('type');
        $GLOBALS['err'] = $this->ifName().'删除了'.$name.'车型'; 
        $this->estruct();
        DB::commit();
        $this->result('',1,'删除成功');
     }else{
        DB::rallback();
        $this->result('',0,'删除失败');
     }
 }
} 
    
  /**
     * 车辆信息修改--获取默认值
     * @param  
     * @return 
  */
  public function csave_v()
  {
       $id = input('post.id');

       if($id){

       $data =  DB::table('co_car_menu a')
                  ->join('co_car_cate b','a.id = b.brand')
                  ->join('co_bang_data c','b.id = c.cid')
                  ->where('b.id',$id)
                  ->find(); 
            if($data){
               $this->result($data,1,'获取成功');

            }
            $this->result($data,0,'获取失败');

       }
  } 

  public function csave()
  {

         $data = input('post.');
         $validate = validate('Carb');
         if(!$validate) $this->result('',1,$validate->getError());
         if($data['id']){
            DB::startTrans();
             $res = Db::table('co_car_cate')->where('id',$data['id'])->strict(false)->update($data);
             $res2 = DB::table('co_bang_data')->where('cid',$data['id'])->strict(false)->update($data);
            
             if($res!==false && $res2!==false)
             {
              // 日志写入
              $name = Db::table('co_car_cate')->where('id',$data['id'])->value('type');
              $GLOBALS['err'] = $this->ifName().'修改了'.$name.'车型'; 
              $this->estruct();
              Db::commit();
               $this->result($data,1,'修改成功');
             }else{

              Db::rollback();
              $this->result($data,0,'修改失败');
             }

         }
 

  }
 



}
