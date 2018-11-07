<?php 

/**
	 * 物料预警
	 */
	public function mateTips()
	{
		//添加 判断是否有未处理的物料申请订单   如有订单未处理 则不提示物料补充,提示有待处理的物料申请订单
		$msg = Db::table('cs_apply_materiel')
				->where('sid',$this->sid)
				->where('audit_status',0)
				->count();  // 未处理订单条数
	    if ($msg == 0){  // 当 订单数为 0
			$count = $this->mateCount();
			if($count >0){
	 			$this->result('',0,'有物料库存不足，请及时补充');
			}else{
	  			$this->result('',1,'物料库存充足');
			}
	    }else{   //  未处理订单不为 0
		  $this->result('',2,'您有待处理的物料申请订单');
	    }
	}

 ?>