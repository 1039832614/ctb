<?php 
namespace app\supply\controller;
use app\base\controller\supply;
use think\Db;

/**
* 授信库存
*/
class Cerdits extends supply{
	
    /**
     * 授信物料确认
     * @return json
     */ 

    public function CerditAgree()
    {
      $this->CerditSave(3);
    }

    /**
     * 授信物料取消
     * @return json
     */ 
    
    public function CerditCancel()
    {
    	$this->CerditSave(4);
    }

    /**
     * 授信物料可选列表
     * @return json
     */
    
    public function CerditCheck()
    {
    	$data = $this->GetCerdit();
    	$this->result($data['list'],1,'获取成功');
    }    
    
    /**
     * 授信物料确定操作
     * @return json
     */    
    
    public function CerditResult()
    {
    	$data = input('post.');
        // 模拟数据
    	// $arr = array(['id'=>1,'name'=>'物料1','set_price'=>800,'cover'=>'https://cc.ctbls.com/uploads/bby/ryx02.jpg','default_num'=>20],['id'=>2,'name'=>'物料2','set_price'=>900,'cover'=>'https://cc.ctbls.com/uploads/bby/ryx02.jpg','default_num'=>10]);
    	// DB::table('cg_credit_order')->where(['id'=>16])->update(['data'=>json_encode($arr)]);die;
    	// $data['list'] = array(['id'=>1,'size'=>10,'remarks'=>'备注'],['id'=>2,'size'=>10,'remarks'=>'备注']); 
    	$this->isNull($data);
    	// 验证是否重复提交
    	$this->isRepeat($data['id']);
    	// 获取数据库授信数据
    	$list = $this->GetCerdit();
    	// 修正并提交数据
    	$this->CerditCorrect($data,$list);
        
    }

    /**
     * 授信物料 未审核列表
     * @return json
     */
    
    public function  CreditWait()
    {
        $this->CerditAudit(5);
    }


    /**
     * 授信物料 已通过列表
     * @return json
     */
    
    public function  CreditAdopt()
    {
        $this->CerditAudit(2);
    }

    /**
     * 授信物料 详情
     * @return json
     */
    
    public function CreditDetails()
    {
    	$id = input('post.id');
    	$this->isNull($id);
    	$data = Db::table('cg_credit_order')->where(['id'=>$id,'gid'=>$this->gid])->field('details,price')->find();
    	$arr = json_decode($data['details'],true);

    	$this->result(['list'=>$arr,'price'=>$data['price']],1,'获取成功');
    }    


    /**
     * 验证是否重复提交
     * @param  $data 用户提交数据  $list 后台添加数据
     * @return json
     */ 
    
    public function isRepeat($id)
    {
        $status = DB::table('cg_credit_order')->where(['id'=>$id,'gid'=>$this->gid])->value('status');
        if($status!=3) $this->result('',0,'状态码错误');
        return true;   
    }

    /**
     * 如果 有一类物料未被选择 ， id 切勿传输
     * 授信物料修正数据
     * @param  $data 用户提交数据  $list 后台添加数据  $price 总价格  $money 单价
     * @return json
     */   
    
    public function CerditCorrect($data,$list,$price=0)
    {
         foreach ($data['list'] as $key => $value) {
         	 
         	 foreach ($list['list'] as $k => $v) {
                // 判断是否对应
         	 	if($value['id']==$v['id']){
         	 	   // 计算总金额
                   $price += $data['list'][$k]['size']*$list['list'][$k]['set_price']; 
                   // 插入价格
                   $list['list'][$k]['price'] = $data['list'][$k]['size']*$list['list'][$k]['set_price'];
                   // 插入数量 
                   $list['list'][$k]['size'] = $data['list'][$k]['size'];
                   // 插入备注
                   $list['list'][$k]['remarks']  = $data['list'][$k]['remarks'];
         	 	}
         	 }
         }
         // 判断价格是否超出授信额度
         if($price>$list['price']) $this->result('',0,'超出最大授信额度');
         $json = json_encode($list['list']);
         // 修改订单 状态改为申请中
         $res = DB::table('cg_credit_order')->where(['id'=>$data['id'],'gid'=>$this->gid])->update(['details'=>$json,'price'=>$price,'status'=>5]);

         if(!$res) $this->result('',0,'操作失败');
         $this->result('',1,'操作成功');
       

    }

    /**
     * 授信物料列表
     * @param  $id  订单id
     * @return 物料详情 最大金额
     */ 
    
    public function GetCerdit()
    {
    	$id = input('post.id');
    	$data = DB::table('cg_credit_order')->where(['id'=>$id,'gid'=>$this->gid,'status'=>3])->field('data,max')->find();
    	$list = json_decode($data['data'],true);
    	if($list)  return ['list'=>$list,'price'=>$data['max']];
    }     


    /**
     * 授信物料修改
     * @param  $status  0未确认 1已通过 3已确认 4已取消 5申请中 6驳回
     * @return json
     */ 
    
    public function CerditSave($status)
    {
    	$id = input('post.id');
    	$this->isNull($id);
    	$res = Db::table('cg_credit_order')->where(['id'=>$id,'gid'=>$this->gid,'status'=>0])->update(['status'=>$status]);
        if(!$res) $this->result('',0,'操作失败');
        $this->result('',1,'操作成功');
    }
   
    /**
     * 审核列表
     * @param  $status  0未确认 1已通过 3已确认 4已取消 5申请中 6驳回
     * @return json
     */ 
    
    public function CerditAudit($status)
    {   
    	$page = input('post.page')? :1;
        $count = DB::table('cg_credit_order')->where(['gid'=>$this->gid,'status'=>$status])->count();
        $this->isNull($count);
        $rows = ceil($count/8);
    	$data = DB::table('cg_credit_order')
    	->where(['gid'=>$this->gid,'status'=>$status])
    	->field('id,order_number,max,create_time')
        ->page($page,8)
        ->select();
    	
    	$this->result(['list'=>$data,'rows'=>$count],1,'获取成功');

    }
}