<?php 
namespace app\st\controller;
use app\base\controller\St;
use think\Controller;
class Upload extends Controller
{
	 /*
     * 商品图文详情图片接口
     */
    public function images(){
    	$origin = isset($_SERVER["HTTP_ORIGIN"]) ? $_SERVER["HTTP_ORIGIN"] : '*';
        // header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Headers:x-requested-with'); 
        header('Access-Control-Allow-Origin:'.$origin); 
        header('Access-Control-Allow-Credentials:true');
        header('Access-Control-Allow-Methods:GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers:Origin, No-Cache, X-Requested-With, If-Modified-Since, Pragma, Last-Modified, Cache-Control, Expires, Content-Type, X-E4M-With');
        $data = input('post.');
        return $data;die();
        //图片文件的生成
        return upload('file','shop/photo','https://cc.ctbls.com/');
    }
	
	
}
