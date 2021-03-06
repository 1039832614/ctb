<?php 
namespace app\merchant\controller;
use app\base\controller\Agent;
use Wx\WxPay;
use Wx\WxPayConfig;
use Wx\WxPayApi;
use think\Db;
/**
 * 进程初始化
 */
class SystemFee extends merchant
{
	/**
	 * 进程初始化
	 * @return [type] [description]
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
	 * 进行二维码支付
	 * @return [type] [description]
	 */
	public function pay()
	{
		$uid = input('get.id');
		$trade_no = $this->getTradeNo();
		$api = new WxPayApi();
		$input = new WxPay();
		$input->SetBody("供应商系统使用费");
		$input->SetAttach($uid);
		$input->SetOut_trade_no($trade_no);
		$input->SetTotal_fee("200000");
		$input->SetNotify_url("https://ceshi.ctbls.com/merchant/SystemFee/not");
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
	 * @return [type] [description]
	 */
	public function not()
	{
		$xml = file_get_contents("php://input");
		$data = $this->xmlToArray($xml);
		if($data['return_code'] == 'SUCCESS' && $data['result_code'] == 'SUCCESS' && $data['cash_fee'] == '200000'){
			//更新供应商状态为已支付状态
			$res = Db::table('cp_merchant')
					->where('id',$data['attach'])
					->detField('status',3);
			//构建支付记录数组
			$arr = [
				'uid' => $data['attach'],
				'pay_type' => 3,
				'trade_no' => $data['out_trade_no'],
				'total_fee' => $data['total_fee']/100,
				'time_end' => $data['time_end'],
				'transaction_id' => $data['transaction_id']
			];
			$add = Db::table('co_system_fee')->insert($arr);
			if($add && $save !== false){
				echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
			}
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