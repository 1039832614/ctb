<?php 
namespace app\supply\controller;
use app\base\controller\supply;
use think\Db;

/**
* 授信库存
*/
class Cerdit extends supply{

    /**
     * 授信物料确认
     * @return json
     */  
    public function CerditAgree()
    {
        $id = input('post.id');
        $this->isNull($id);
        $this->CreditSave($id,3);        
    } 

    /**
     * 授信物料取消
     * @return json
     */  
    public function CerditCancel()
    {
        $id = input('post.id');
        $this->isNull($id);
        $this->CreditSave($id,4);        
    } 
	 
    /**
	 * 授信物料列表
	 * @return json
	 */    

     public function CerditList()
     {  
               
       $id = input('post.id');
       $inc_id = $this->IdIncid($id);
       $data = $this->CerditJson($inc_id);
       $this->result($data,1,'获取成功');
     } 
    
    /**
     * 授信物料确认
     * @return json
     */ 
    
     public function CreditConfirm()
     {  

        $data = input('post.');
        //模拟数据
        // $list = array(['name'=>'金士顿','size'=>10,'remarks'=>'备注']);

        // $data = (['token'=>'token','inc_id'=>34,'list'=>$list]);

        // 参数验证
        $validate = validate('Credit');
        if(!$validate->check($data['list'][0])) $this->result('',0,$validate->getError());
        $this->isNull($data['inc_id']);

        // 判断价钱是否超额
        $price = $this->isPrice($data['list'],$data['inc_id']);
         

        // 判断是否具备条件
        $inc_id = $this->IdIncid($data['inc_id']);
        $this->CreditIf($inc_id);

        // 插入数据
        DB::startTrans();
        try{
           // 向订单表插入数据
           Db::table('cg_credit_order')->strict(false)->insert(['gid'=>$this->gid,'order_number'=>build_order_sn(),'inc_id'=>$data['inc_id'],'price'=>$price['price'],'details'=>$price['data']]);

           //修改申请地区状态 ， 改为申请中
           Db::table('cg_increase')->where(['id'=>$data['inc_id'],'gid'=>$this->gid])->update(['credit_status'=>2]);

           // 修改授信状态 申请中
           Db::table('cg_credit_order')->where(['id'=>$data['inc_id','gid'=>$this->gid]])->update(['status'=>5]);
           DB::commit();
        } catch (\Exception $e){

          DB::rollback();
          $this->result('',0,'产生错误事务回滚');
        }
        
        $this->result('',1,'确认成功');

     }

    
   /**
     * 授信物料列表--审核中
     * @return json
     */ 
    public function  CreditShowApply()
    {
        $this->CreditShow(0);
    }

    /**
     * 授信物料列表--已审核
     * @return json
     */ 
    public function  CreditShowAdopt()
    {
        $this->CreditShow(1);
    }   

    /**
     * 授信物料列表详情
     * @return json
     */ 
    
    public function ShowDetails()
    {
        $id = input('post.id');

        $data = Db::table('cg_credit_order')->where(['id'=>$id,'gid'=>$this->gid])->value('details');
        
        $this->isNull($data);       
        $this->result(json_decode($data,true),1,'获取成功');


    }



    

    /**
	 * 授信物料列表
	 * @return json
	 */ 
	
	public function CreditShow($status)
	{   
		$page = input('post.page')? :1;
	    $count = Db::table('cg_credit_order')->where(['gid'=>$this->gid,'status'=>$status])->count();
	    if($count==null) $this->result('',0,'暂无数据');
	    $count = ceil($count/8);
	    $data = Db::table('cg_credit_order')
	            ->where(['gid'=>$this->gid,'status'=>$status])
	            ->field('id,order_number,end_time')
                ->page($page,8)
	            ->select();
        
        $this->result(['row'=>$count,'list'=>$data],1,'获取成功');
	}
    
 
    /**
	 * 判断授信物料确认是否具备条件
	 * @param  status     0 未审核    1已审核
	 * @param  credit_status 0 可用  1不可用
	 * @return json
	 */ 
    public function CreditIf($inc_id)
    {   
    	// 判断是否重复提交
    	// $res = Db::table('cg_credit_order')->where(['gid'=>$this->gid,'status'=>0])->select();
     //  	if($res)  $this->result('',0,'已存在未审核订单');
        
        // 判断是否申请物料可用 
     	$res = Db::table('cg_increase')->where(['id'=>$inc_id,'gid'=>$this->gid,'credit_status'=>1,'credit_status'=>2])->find();
        if($res)  $this->result('',0,'同个地区只能申请一次');

        return true;
    }


    /**
	 * 判断权限
	 * @return  true
	 */ 
    public function isIncid($id)
    { 

        $res = Db::table('cg_increase')->where(['gid'=>$this->gid,'id'=>$id,'audit_status'=>1])->find();
        $this->isNull($res);
    }

    /**
	 * 判断金额
	 * @return  json数
	 */ 
    public function isPrice($list,$inc_id,$price=0)
    {
        $data = $this->CerditJson($inc_id);
         
        // 把一维数组 转换为二维数组
        if(count($data['data']) == count($data['data'],1)) $data['data'] = array($data['data']);
        // 判断提交数据是否符合 后台填写数据
        if(count($data['data'])!==count($list)) $this->result('',0,'请正确提交数据');
        

	 	foreach($list as $k=>$v){
            foreach($data['data'] as $key=>$val){

                 if($v['name']==$val['name']){
                 	$price+=$val['set_price']*$v['size'];
                 	$data['data'][$key]['money'] = $val['set_price']*$v['size'];
                 }
            }     
	 	}
	  
	 	if($price>$data['price']) $this->result('',0,'物料总额不能超过'.$data['price']);
    
        return ['data'=>json_encode($data),'price'=>$price];
    }
    
    /**
     * 获取json
     * @return json
     */   
    public function CerditJson($inc_id)
    {
       
       //判断申请地区id 是否合法
       $this->isIncid($inc_id);
       
       //获取json数据
       $data = Db::table('cg_credit_stock')->where(['gid'=>$this->gid,'inc_id'=>$inc_id])->field('data,price')->find();

       $this->isNull($data);
       
       $data['data'] = json_decode($data['data'],true); 
       return $data;  
    }
    
   /**
     * 获取json
     * @return json
     */ 
    
    public function CreditSave($id,$status)
    {
        $res = Db::table('cg_credit_order')->where(['id'=>$id,'gid'=>$this->gid,'status'=>0])->update(['status'=>$status]);
        if($res) $this->result('',1,'操作成功');
        $this->result('',0,'操作失败');
    }

   /**
     * 获取json
     * @return json
     */ 
    
    public function IdIncid($id)
    {
        $id = DB::table('cg_credit_order')->where('id',$id)->value('inc_id');
        return $id;
    }
}