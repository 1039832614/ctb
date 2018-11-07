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
 [{"num":58,"materiel_id":2,"materiel":"SM半合成(邦保养1号)","remarks":"粘度30：29升，粘度40：29升"},{"num":9,"materiel_id":3,"materiel":"SN合成(邦保养2号)","remarks":"粘度30：5升，粘度40：4升"},{"num":419,"materiel_id":4,"materiel":"SN全合成(邦保养3号)","remarks":"粘度30：210升，粘度40：209升"},{"num":15,"materiel_id":5,"materiel":"SN脂类全合成(邦保养4号)","remarks":"粘度30：8升，粘度40：7升"}]