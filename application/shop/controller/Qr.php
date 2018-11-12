<?php 
namespace app\shop\controller;
use app\base\controller\Shop;
use Wx\WxPay;
use Wx\WxPayConfig;
use Wx\WxPayApi;
use think\Db;

/**
* 支付系统使用费
*/

class Qr extends Shop
{
	/**
	 * 进程初始化
	 */
	public function initialize()
	{
		$origin = isset($_SERVER["HTTP_ORIGIN"]) ? $_SERVER["HTTP_ORIGIN"] : '*';
		header('Access-Control-Allow-Headers:x-requested-with'); 
        header('Access-Control-Allow-Origin:*'); 
        header('Access-Control-Allow-Credentials:true');
        header('Access-Control-Allow-Methods:GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers:Origin, No-Cache, X-Requested-With, If-Modified-Since, Pragma, Last-Modified, Cache-Control, Expires, Content-Type, X-E4M-With');   
	}

	/**
	 * 进行二维码模式二的支付
	 */
	public function pay()
	{
		$uid = input('get.sid');
		if($uid < 1) {$this->result('uid信息有误');}
		$trade_no = $this->getTradeNo();
		$api = new WxPayApi();
		$input = new WxPay();
		$input->SetBody("维修厂系统使用费");
		$input->SetAttach($uid);
		$input->SetOut_trade_no($trade_no);
		$input->SetTotal_fee("200000");
		$input->SetNotify_url("https://cc.ctbls.com/shop/qr/notify");
		$input->SetTrade_type("NATIVE");
		$input->SetProduct_id("996688525");
		$result = $api->GetPayUrl($input);
		$url = $result["code_url"];
		$src = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=".$url;
		$this->result(['src'=>$src],1,'获取成功');
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
	public function notify()
	{
		$xml =  file_get_contents("php://input");
		$data = $this->xmlToArray($xml);
		if($data['return_code'] == 'SUCCESS' && $data['result_code'] == 'SUCCESS' && $data['cash_fee'] == '200000'){
			// 更新维修厂状态
			$save = Db::table('cs_shop')->where('id',$data['attach'])->setField('audit_status',0);
			// 构建支付记录数组
			$arr = [
				'uid'	=> $data['attach'],
				'pay_type' => 1,
				'trade_no' => $data['out_trade_no'],
				'total_fee'	=> $data['total_fee']/100,
				'time_end'	=> $data['time_end'],	
				'transaction_id' => $data['transaction_id']
			];
			// 添加支付记录
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
		$uid=input('get.sid');
		if($uid){
			$audit_status = Db::table('cs_shop')->where('id',$uid)->value('audit_status');
			$this->result(['audit_status' => $audit_status],1,'获取状态成功');
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