<?php
/** 
* 支付宝手机支付
*/  
require_once("Alipay.function.php");

class Alipay{
	var $alipay_config;
	var $alipay_gateway_new = 'https://mapi.alipay.com/gateway.do?';						//:: 支付宝网关地址（新）
	var $https_verify_url = 'https://mapi.alipay.com/gateway.do?service=notify_verify&';	//:: HTTPS形式消息验证地址
	var $http_verify_url = 'http://notify.alipay.com/trade/notify_query.do?';				//:: HTTP形式消息验证地址
	//:: php5构造函数
	function __construct($alipay_config){
		$this->alipay_config = $alipay_config;
	}
	//:: php4构造函数
	function Alipay($alipay_config){
		$this->__construct($alipay_config);
	}
	//::==========================================================================================
	//::  单元名称：	buildRequestMysign($para_sort)
	//::  参数描述：	$para_sort 已排序要签名的数组
	//::  返 回 值：	签名结果字符串
	//::  作用描述：	生成签名结果
	//::  单元依赖：	
	//::  作者描述：	Venice
	//::  编辑时间：	2015-10-06 17:04
	//::==========================================================================================
	function buildRequestMysign($para_sort) {
		//把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
		$prestr = createLinkstring($para_sort);
		
		$mysign = "";
		switch (strtoupper(trim($this->alipay_config['sign_type']))) {
			case "RSA" :
				$mysign = rsaSign($prestr, $this->alipay_config['private_key_path']);
				break;
			default :
				$mysign = "";
		}
		return $mysign;
	}
	//::==========================================================================================
	//::  单元名称：	buildRequestPara($para_temp)
	//::  参数描述：	$para_temp 请求前的参数数组
	//::  返 回 值：	要请求的参数数组
	//::  作用描述：	生成要请求给支付宝的参数数组
	//::  单元依赖：	
	//::  作者描述：	Venice
	//::  编辑时间：	2015-10-06 17:07
	//::==========================================================================================
	function buildRequestPara($para_temp) {
		//除去待签名参数数组中的空值和签名参数
		$para_filter = paraFilter($para_temp);
		return $para_filter;
		//对待签名参数数组排序
		$para_sort = argSort($para_filter);
		//生成签名结果
		$mysign = $this->buildRequestMysign($para_sort);
		//签名结果与签名方式加入请求提交参数组中
		$para_sort['sign'] = $mysign;
		$para_sort['sign_type'] = strtoupper(trim($this->alipay_config['sign_type']));
		return $para_sort;
	}
	//::==========================================================================================
	//::  单元名称：	buildRequestParaToString($para_temp)
	//::  参数描述：	$para_temp 请求前的参数数组
	//::  返 回 值：	要请求的参数数组字符串
	//::  作用描述：	生成要请求给支付宝的参数数组
	//::  单元依赖：	
	//::  作者描述：	Venice
	//::  编辑时间：	2015-10-06 17:07
	//::==========================================================================================
	function buildRequestParaToString($para_temp) {
		//待请求参数数组
		$para = $this->buildRequestPara($para_temp);
		//把参数组中所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串，并对字符串做urlencode编码
		$request_data = createLinkstringUrlencode($para);
		return $request_data;
	}
	//::==========================================================================================
	//::  单元名称：	buildRequestForm($para_temp, $method, $button_name)
	//::  参数描述：	$para_temp 请求前的参数数组
	//::  				$method 提交方式。两个值可选：post、get
	//::  				$button_name 确认按钮显示文字
	//::  返 回 值：	提交表单HTML文本
	//::  作用描述：	建立请求，以表单HTML形式构造（默认）
	//::  单元依赖：	
	//::  作者描述：	Venice
	//::  编辑时间：	2015-10-06 17:08
	//::==========================================================================================
	function buildRequestForm($para_temp, $method, $button_name) {
		//待请求参数数组
		$para = $this->buildRequestPara($para_temp);
		$sHtml = "<form id='alipaysubmit' name='alipaysubmit' action='".$this->alipay_gateway_new."_input_charset=".trim(strtolower($this->alipay_config['input_charset']))."' method='".$method."'>";
		while (list ($key, $val) = each ($para)) {
            $sHtml.= "<input type='hidden' name='".$key."' value='".$val."'/>";
        }
		//submit按钮控件请不要含有name属性
        $sHtml = $sHtml."<input type='submit' value='".$button_name."'></form>";
		$sHtml = $sHtml."<script>document.forms['alipaysubmit'].submit();</script>";
		return $sHtml;
	}
	//::==========================================================================================
	//::  单元名称：	buildRequestHttp($para_temp)
	//::  参数描述：	$para_temp 请求前的参数数组
	//::  返 回 值：	支付宝处理结果
	//::  作用描述：	建立请求，以模拟远程HTTP的POST请求方式构造并获取支付宝的处理结果
	//::  单元依赖：	
	//::  作者描述：	Venice
	//::  编辑时间：	2015-10-06 17:10
	//::==========================================================================================
	function buildRequestHttp($para_temp) {
		$sResult = '';
		//待请求参数数组字符串
		$request_data = $this->buildRequestPara($para_temp);
		//远程获取数据
		$sResult = getHttpResponsePOST($this->alipay_gateway_new, $this->alipay_config['cacert'],$request_data,trim(strtolower($this->alipay_config['input_charset'])));
		return $sResult;
	}
	//::==========================================================================================
	//::  单元名称：	buildRequestHttpInFile($para_temp, $file_para_name, $file_name)
	//::  参数描述：	$para_temp 请求前的参数数组
	//::  				$file_para_name 文件类型的参数名
	//::  				$file_name 文件完整绝对路径
	//::  返 回 值：	支付宝返回处理结果
	//::  作用描述：	建立请求，以模拟远程HTTP的POST请求方式构造并获取支付宝的处理结果，带文件上传功能
	//::  单元依赖：	
	//::  作者描述：	Venice
	//::  编辑时间：	2015-10-06 17:10
	//::==========================================================================================
	function buildRequestHttpInFile($para_temp, $file_para_name, $file_name) {
		//待请求参数数组
		$para = $this->buildRequestPara($para_temp);
		$para[$file_para_name] = "@".$file_name;
		//远程获取数据
		$sResult = getHttpResponsePOST($this->alipay_gateway_new, $this->alipay_config['cacert'],$para,trim(strtolower($this->alipay_config['input_charset'])));
		return $sResult;
	}
	//::==========================================================================================
	//::  单元名称：	query_timestamp()
	//::  参数描述：	无
	//::  返 回 值：	时间戳字符串
	//::  作用描述：	用于防钓鱼，调用接口query_timestamp来获取时间戳的处理函数
	//::  注    意：	该功能PHP5环境及以上支持，因此必须服务器、本地电脑中装有支持DOMDocument、SSL的PHP配置环境。建议本地调试时使用PHP开发软件
	//::  单元依赖：	
	//::  作者描述：	Venice
	//::  编辑时间：	2015-10-06 17:10
	//::==========================================================================================
	function query_timestamp() {
		$url = $this->alipay_gateway_new."service=query_timestamp&partner=".trim(strtolower($this->alipay_config['partner']))."&_input_charset=".trim(strtolower($this->alipay_config['input_charset']));
		$encrypt_key = "";		
		$doc = new DOMDocument();
		$doc->load($url);
		$itemEncrypt_key = $doc->getElementsByTagName( "encrypt_key" );
		$encrypt_key = $itemEncrypt_key->item(0)->nodeValue;
		return $encrypt_key;
	}
	//::==========================================================================================
	//::  单元名称：	verifyNotify()
	//::  参数描述：	无
	//::  返 回 值：	验证结果
	//::  作用描述：	针对notify_url验证消息是否是支付宝发出的合法消息
	//::  单元依赖：	
	//::  作者描述：	Venice
	//::  编辑时间：	2015-10-06 17:15
	//::==========================================================================================
	function verifyNotify(){
		if(empty($_POST)) {//判断POST来的数组是否为空
			return false;
		} else {
			//生成签名结果
			$isSign = $this->getSignVeryfy($_POST, $_POST["sign"]);
			//获取支付宝远程服务器ATN结果（验证是否是支付宝发来的消息）
			$responseTxt = 'true';
			if (! empty($_POST["notify_id"])) {$responseTxt = $this->getResponse($_POST["notify_id"]);}
			//写日志记录
			/*if ($isSign) {
				$isSignStr = 'true';
			} else {
				$isSignStr = 'false';
			}
			$log_text = "responseTxt=".$responseTxt."\n notify_url_log:isSign=".$isSignStr.",";
			$log_text = $log_text.createLinkString($_POST);
			logResult($log_text);*/
			
			//验证
			//$responsetTxt的结果不是true，与服务器设置问题、合作身份者ID、notify_id一分钟失效有关
			//isSign的结果不是true，与安全校验码、请求时的参数格式（如：带自定义参数等）、编码格式有关
			if (preg_match("/true$/i",$responseTxt) && $isSign) {
				return true;
			} else {
				return false;
			}
		}
	}
	//::==========================================================================================
	//::  单元名称：	verifyReturn()
	//::  参数描述：	无
	//::  返 回 值：	验证结果
	//::  作用描述：	针对return_url验证消息是否是支付宝发出的合法消息
	//::  单元依赖：	
	//::  作者描述：	Venice
	//::  编辑时间：	2015-10-06 17:18
	//::==========================================================================================
	function verifyReturn(){
		if(empty($_GET)) {//判断POST来的数组是否为空
			return false;
		}
		else {
			//生成签名结果
			$isSign = $this->getSignVeryfy($_GET, $_GET["sign"]);
			//获取支付宝远程服务器ATN结果（验证是否是支付宝发来的消息）
			$responseTxt = 'true';
			if (! empty($_GET["notify_id"])) {$responseTxt = $this->getResponse($_GET["notify_id"]);}
			
			//写日志记录
			/*if ($isSign) {
				$isSignStr = 'true';
			}
			else {
				$isSignStr = 'false';
			}
			$log_text = "responseTxt=".$responseTxt."\n return_url_log:isSign=".$isSignStr.",";
			$log_text = $log_text.createLinkString($_GET);
			logResult($log_text);*/
			
			//验证
			//$responsetTxt的结果不是true，与服务器设置问题、合作身份者ID、notify_id一分钟失效有关
			//isSign的结果不是true，与安全校验码、请求时的参数格式（如：带自定义参数等）、编码格式有关
			if (preg_match("/true$/i",$responseTxt) && $isSign) {
				return true;
			} else {
				return false;
			}
		}
	}
	//::==========================================================================================
	//::  单元名称：	getSignVeryfy($para_temp, $sign)
	//::  参数描述：	$para_temp 通知返回来的参数数组
	//::  				$sign 返回的签名结果
	//::  返 回 值：	签名验证结果
	//::  作用描述：	获取返回时的签名验证结果
	//::  单元依赖：	
	//::  作者描述：	Venice
	//::  编辑时间：	2015-10-06 17:20
	//::==========================================================================================
	function getSignVeryfy($para_temp, $sign) {
		//除去待签名参数数组中的空值和签名参数
		$para_filter = paraFilter($para_temp);
		//对待签名参数数组排序
		$para_sort = argSort($para_filter);
		//把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
		$prestr = createLinkstring($para_sort);
		$isSgin = false;
		switch (strtoupper(trim($this->alipay_config['sign_type']))) {
			case "RSA" :
				$isSgin = rsaVerify($prestr, trim($this->alipay_config['ali_public_key_path']), $sign);
				break;
			default :
				$isSgin = false;
		}
		return $isSgin;
	}
	//::==========================================================================================
	//::  单元名称：	getResponse($notify_id)
	//::  参数描述：	$notify_id 通知校验ID
	//::  返 回 值：	服务器ATN结果
    //::  				invalid 命令参数不对，出现这个错误，请检测返回处理中partner和key是否为空 
    //::  				true 返回正确信息
    //::  				false 请检查防火墙或者是服务器阻止端口问题以及验证时间是否超过一分钟
	//::  作用描述：	获取远程服务器ATN结果,验证返回URL
	//::  单元依赖：	
	//::  作者描述：	Venice
	//::  编辑时间：	2015-10-06 17:23
	//::==========================================================================================
	function getResponse($notify_id) {
		$transport = strtolower(trim($this->alipay_config['transport']));
		$partner = trim($this->alipay_config['partner']);
		$veryfy_url = '';
		if($transport == 'https') {
			$veryfy_url = $this->https_verify_url;
		}
		else {
			$veryfy_url = $this->http_verify_url;
		}
		$veryfy_url = $veryfy_url."partner=" . $partner . "&notify_id=" . $notify_id;
		$responseTxt = getHttpResponseGET($veryfy_url, $this->alipay_config['cacert']);
		return $responseTxt;
	}
}
?>