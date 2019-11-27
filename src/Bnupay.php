<?php
namespace wangshunda\bnupay;
/* *
 * 功能：电脑网站支付
 * 版本：2.0
 * 修改日期：2017-05-01
 * 说明：
 * 以下代码只是为了方便测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
 */

require_once dirname(dirname(dirname ( __FILE__ ))).'/bnupay/src/BnupayTradePagePayRequest.php';
require_once dirname(dirname(dirname ( __FILE__ ))).'/bnupay/src/AopClient.php';

class Bnupay {

	//网关地址
	public $gateway_url; 

	//公钥
	public $bnupay_public_key;

	//商户私钥
	public $private_key;

	//编码格式
	public $charset;

	public $token = NULL;
	
	//返回数据格式
	public $format = "json";

	//签名方式
    private $orderDate;

    // 订单标题，粗略描述用户的支付目的。
    private $xmpch;
    
	private $sign;

    // 商户订单号.
    private $orderNo;

    // (推荐使用，相对时间) 该笔订单允许的最晚付款时间，逾期将关闭交易。取值范围：1m～15d。m-分钟，h-小时，d-天，1c-当天（1c-当天的情况下，无论交易何时创建，都在0点关闭）。 该参数数值不接受小数点， 如 1.5h，可转换为 90m
    // (推荐使用，相对时间) 该笔订单允许的最晚付款时间，逾期将关闭交易。取值范围：1m～15d。m-分钟，h-小时，d-天，1c-当天（1c-当天的情况下，无论交易何时创建，都在0点关闭）。 该参数数值不接受小数点， 如 1.5h，可转换为 90m
    private $timeExpress;

    // 订单总金额，整形，此处单位为元，精确到小数点后2位，不能超过1亿元
    private $amount;

    // 产品标示码，固定值：QUICK_WAP_PAY
    private $productCode;

    private $bizContentarr = array();

    private $bizContent = NULL;

	function __construct($bnupay_config){
		$this->gateway_url = $bnupay_config['gatewayUrl'];
		$this->private_key = $bnupay_config['merchant_private_key'];
		$this->bnupay_public_key = $bnupay_config['bnupay_public_key'];
		$this->charset = $bnupay_config['charset'];
		$this->signtype=$bnupay_config['sign_type'];
        $this->bizContentarr['product_code'] = "FAST_INSTANT_TRADE_PAY";

		
		if(empty($this->private_key)||trim($this->private_key)==""){
			throw new Exception("private_key should not be NULL!");
		}
		if(empty($this->bnupay_public_key)||trim($this->bnupay_public_key)==""){
			throw new Exception("bnupay_public_key should not be NULL!");
		}
		if(empty($this->charset)||trim($this->charset)==""){
			throw new Exception("charset should not be NULL!");
		}
		if(empty($this->gateway_url)||trim($this->gateway_url)==""){
			throw new Exception("gateway_url should not be NULL!");
		}

	}
    public function getBizContent()
    {
        if(!empty($this->bizContentarr)){
            $this->bizContent = json_encode($this->bizContentarr,JSON_UNESCAPED_UNICODE);
        }
        return $this->bizContent;
    }

    public function getOrderdate()
    {
        return $this->orderDate;
    }

    public function setOrderdate($orderDate)
    {
        $this->orderDate = $orderDate;
        $this->bizContentarr['orderDate'] = $orderDate;
    }

    public function setXmpch($xmpch)
    {
        $this->xmpch = $xmpch;
        $this->bizContentarr['xmpch'] = $xmpch;
    }


    public function getXmpch()
    {
        return $this->xmpch;
    }


    public function setSign($sign)
    {
        $this->sign = $sign;
        $this->bizContentarr['sign'] = $sign;
    }

    public function getSign()
    {
        return $this->sign;
    }

    public function getOrderNo()
    {
        return $this->orderNo;
    }

    public function setOrderNo($orderNo)
    {
        $this->orderNo = $orderNo;
        $this->bizContentarr['orderNo'] = $orderNo;
    }

    public function setTimeExpress($timeExpress)
    {
        $this->timeExpress = $timeExpress;
        $this->bizContentarr['timeout_express'] = $timeExpress;
    }

    public function getTimeExpress()
    {
        return $this->timeExpress;
    }

    public function setTotalAmount($amount)
    {
        $this->amount = $amount;
        $this->bizContentarr['amount'] = $amount;
    }

    public function getTotalAmount()
    {
        return $this->amount;
    }
	/**
	 * bnupay.trade.page.pay
	 * @param $builder 业务参数，使用buildmodel中的对象生成。
	 * @param $return_url 同步跳转地址，公网可以访问
	 * @param $notify_url 异步通知地址，公网可以访问
	 * @return $response 支付宝返回的信息
 	*/
	function pagePay($return_url,$notify_url) {
	
		$biz_content=$this->getBizContent();
		//打印业务参数
		$this->writeLog($biz_content);
	
		$request = new BnupayTradePagePayRequest();
	
		$request->setNotifyUrl($notify_url);
		$request->setReturnUrl($return_url);
		$request->setBizContent ( $biz_content );
	
		// 首先调用支付api
		$response = $this->aopclientRequestExecute ($request,true);
		// $response = $response->bnupay_trade_wap_pay_response;
		return $response;
	}

	/**
	 * sdkClient
	 * @param $request 接口请求参数对象。
	 * @param $ispage  是否是页面接口，电脑网站支付是页面表单接口。
	 * @return $response 支付宝返回的信息
 	*/
	function aopclientRequestExecute($request,$ispage=false) {

		$aop = new AopClient ();
		$aop->gatewayUrl = $this->gateway_url;
		$aop->md5PrivateKey =  $this->private_key;
		$aop->bnupayrsaPublicKey = $this->bnupay_public_key;
		$aop->apiVersion ="1.0";
		$aop->postCharset = $this->charset;
		$aop->format= $this->format;
		$aop->signType=$this->signtype;
		// 开启页面信息输出
		$aop->debugInfo=true;
		if($ispage)
		{
			$result = $aop->pageExecute($request,"post");
			echo $result;
		}
		else 
		{
			$result = $aop->Execute($request);
		}
        
		//打开后，将报文写入log文件
		$this->writeLog("response: ".var_export($result,true));
		return $result;
	}

	/**
	 * bnupay.trade.query (统一收单线下交易查询)
	 * @param $builder 业务参数，使用buildmodel中的对象生成。
	 * @return $response 支付宝返回的信息
 	*/
	function Query($builder){
		$biz_content=$builder->getBizContent();
		//打印业务参数
		$this->writeLog($biz_content);
		$request = new BnupayTradeQueryRequest();
		$request->setBizContent ( $biz_content );

		$response = $this->aopclientRequestExecute ($request);
		$response = $response->bnupay_trade_query_response;
		return $response;
	}
	
	/**
	 * bnupay.trade.refund (统一收单交易退款接口)
	 * @param $builder 业务参数，使用buildmodel中的对象生成。
	 * @return $response 支付宝返回的信息
	 */
	function Refund($builder){
		$biz_content=$builder->getBizContent();
		//打印业务参数
		$this->writeLog($biz_content);
		$request = new BnupayTradeRefundRequest();
		$request->setBizContent ( $biz_content );
	
		$response = $this->aopclientRequestExecute ($request);
		$response = $response->bnupay_trade_refund_response;
		return $response;
	}

	/**
	 * bnupay.trade.close (统一收单交易关闭接口)
	 * @param $builder 业务参数，使用buildmodel中的对象生成。
	 * @return $response 支付宝返回的信息
	 */
	function Close($builder){
		$biz_content=$builder->getBizContent();
		//打印业务参数
		$this->writeLog($biz_content);
		$request = new BnupayTradeCloseRequest();
		$request->setBizContent ( $biz_content );
	
		$response = $this->aopclientRequestExecute ($request);
		$response = $response->bnupay_trade_close_response;
		return $response;
	}
	
	/**
	 * 退款查询   bnupay.trade.fastpay.refund.query (统一收单交易退款查询)
	 * @param $builder 业务参数，使用buildmodel中的对象生成。
	 * @return $response 支付宝返回的信息
	 */
	function refundQuery($builder){
		$biz_content=$builder->getBizContent();
		//打印业务参数
		$this->writeLog($biz_content);
		$request = new BnupayTradeFastpayRefundQueryRequest();
		$request->setBizContent ( $biz_content );
	
		$response = $this->aopclientRequestExecute ($request);
		return $response;
	}
	/**
	 * bnupay.data.dataservice.bill.downloadurl.query (查询对账单下载地址)
	 * @param $builder 业务参数，使用buildmodel中的对象生成。
	 * @return $response 支付宝返回的信息
	 */
	function downloadurlQuery($builder){
		$biz_content=$builder->getBizContent();
		//打印业务参数
		$this->writeLog($biz_content);
		$request = new bnupaydatadataservicebilldownloadurlqueryRequest();
		$request->setBizContent ( $biz_content );
	
		$response = $this->aopclientRequestExecute ($request);
		$response = $response->bnupay_data_dataservice_bill_downloadurl_query_response;
		return $response;
	}

	/**
	 * 验签方法
	 * @param $arr 验签支付宝返回的信息，使用支付宝公钥。
	 * @return boolean
	 */
	function check($arr){
		$aop = new AopClient();
		$aop->bnupayrsaPublicKey = $this->bnupay_public_key;
		$result = $aop->rsaCheckV1($arr, $this->bnupay_public_key, $this->signtype);

		return $result;
	}
	
	/**
	 * 请确保项目文件有可写权限，不然打印不了日志。
	 */
	function writeLog($text) {
		// $text=iconv("GBK", "UTF-8//IGNORE", $text);
		//$text = characet ( $text );
		file_put_contents ( dirname ( __FILE__ ).DIRECTORY_SEPARATOR."./../../log.txt", date ( "Y-m-d H:i:s" ) . "  " . $text . "\r\n", FILE_APPEND );
	}
}
