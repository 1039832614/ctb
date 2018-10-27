<?php 
namespace app\agent\controller;
use app\base\controller\Agent;
use Wx\WxPay;
use Wx\WxPayConfig;
use Wx\WxPayApi;
use think\Db;
/**
* 支付系统使用费
*/

class SysFee extends Agent
{
	/**
	 * 进程初始化
	 */
	public function initialize()
	{
		$origin = isset($_SERVER["HTTP_ORIGIN"]) ? $_SERVER["HTTP_ORIGIN"] : '*';
		header('Access-Control-Allow-Headers:x-requested-with'); 
        header('Access-Control-Allow-Origin:'.$origin); 
        header('Access-Control-Allow-Credentials:true');
        header('Access-Control-Allow-Methods:GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers:Origin, No-Cache, X-Requested-With, If-Modified-Since, Pragma, Last-Modified, Cache-Control, Expires, Content-Type, X-E4M-With');   
	}

	/**
	 * 进行二维码模式二的支付
	 */
	public function pay()
	{
		$uid = input('get.aid');
		$number = input('get.number');
		$money = 500000*$number;
		$trade_no = $this->getTradeNo();
		$api = new WxPayApi();
		$input = new WxPay();
		$input->SetBody("运营商系统使用费");
		$input->SetAttach($uid);
		$input->SetOut_trade_no($trade_no);
		$input->SetTotal_fee($money);
		$input->SetNotify_url("https://cc.ctbls.com/agent/SysFee/not");
		$input->SetTrade_type("NATIVE");
		$input->SetProduct_id("996688525");
		$result = $api->GetPayUrl($input);
		$url = $result["code_url"];
		$url = 'http://paysdk.weixin.qq.com/qrcode.php?data='.urlencode($url);
		return $url;
	}

	/**
	 * 生成唯一订单号
	 */
	public function getTradeNo()
	{
		return  (strtotime(date('YmdHis', time()))) . substr(microtime(), 2, 6) . sprintf('%03d', rand(0, 999));
	}

	/**
	 * 支付完成后的回调
	 */
	public function not()
	{
		$xml =  file_get_contents("php://input");
		$data = $this->xmlToArray($xml);
		if($data['return_code'] == 'SUCCESS' && $data['result_code'] == 'SUCCESS'){
			$this->setPay($data['attach']);
			// 构建支付记录数组
			$arr = [
				'uid'	=> $data['attach'],
				'pay_type' => 2,
				'trade_no' => $data['out_trade_no'],
				'total_fee'	=> $data['total_fee']/100,
				'time_end'	=> $data['time_end'],	
				'mold' => 2,
				'transaction_id' => $data['transaction_id']
			];
			// 添加支付记录
			$add = Db::table('co_system_fee')->strict(false)->insert($arr);
			if($add){
				echo  '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
			}
		} else {
			echo '<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[签名失败]]></return_msg></xml>';
		}
	}

	/**
	 * 设置这个运营商在ca_increase 表中对应的最大id的支付状态
	 */
	public function setPay($id){
		//当前运营商在ca_increase 表中最大的ID
		$max_id = Db::table('ca_increase')
				->where('aid',$id)
				->order('id desc')
				->limit(1)
				->value('id');
		$re = Db::table('ca_increase')
				->where('id',$max_id)
				->setField('pay_status',1);
		if($re !==false){
			return 1;
		} else {
			return 0;
		}
	}


	/**
	 * 进行状态查询
	 */
	public function getStatus()
	{
		// 获取提交过来的数据
		$aid=input('get.aid');
		if($aid){
			$audit_status = Db::table('ca_agent')->where('aid',$aid)->value('status');
			$this->result(['status' => $audit_status],1,'获取状态成功');
		}else{
			$this->result('',0,'用户信息有误');
		}
	}



	/**
     * xml转换成数组  
     */
    public function xmlToArray($xml) {  
        //禁止引用外部xml实体   
        libxml_disable_entity_loader(true);  
        $xmlstring = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);  
        $val = json_decode(json_encode($xmlstring), true);  
        return $val;  
    }

}