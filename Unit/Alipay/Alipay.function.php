<?php
/** 
* 支付宝手机支付
*/  

//::==========================================================================================
//::  单元名称：	createLinkstring($para)
//::  参数描述：	$para 需要拼接的数组
//::  返 回 值：	拼接完成以后的字符串
//::  作用描述：	把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
//::  单元依赖：	
//::  作者描述：	Venice
//::  编辑时间：	2015-10-06 17:32
//::==========================================================================================
function createLinkstring($para) {
	$arg  = "";
	while (list ($key, $val) = each ($para)) {
		$arg.=$key."=".$val."&";
	}
	//去掉最后一个&字符
	$arg = substr($arg,0,count($arg)-2);
	//如果存在转义字符，那么去掉转义
	if(get_magic_quotes_gpc()){$arg = stripslashes($arg);}
	return $arg;
}
//::==========================================================================================
//::  单元名称：	createLinkstringUrlencode($para)
//::  参数描述：	$para 需要拼接的数组
//::  返 回 值：	拼接完成以后的字符串
//::  作用描述：	把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串，并对字符串做urlencode编码
//::  单元依赖：	
//::  作者描述：	Venice
//::  编辑时间：	2015-10-06 17:33
//::==========================================================================================
function createLinkstringUrlencode($para) {
	$arg  = "";
	while (list ($key, $val) = each ($para)) {
		$arg.=$key."=".urlencode($val)."&";
	}
	//去掉最后一个&字符
	$arg = substr($arg,0,count($arg)-2);
	//如果存在转义字符，那么去掉转义
	if(get_magic_quotes_gpc()){$arg = stripslashes($arg);}
	return $arg;
}
//::==========================================================================================
//::  单元名称：	paraFilter($para)
//::  参数描述：	$para 需要拼接的数组
//::  返 回 值：	去掉空值与签名参数后的新签名参数组
//::  作用描述：	除去数组中的空值和签名参数
//::  单元依赖：	
//::  作者描述：	Venice
//::  编辑时间：	2015-10-06 17:36
//::==========================================================================================
function paraFilter($para) {
	$para_filter = array();
	while (list ($key, $val) = each ($para)) {
		if($key == "sign" || $key == "sign_type" || $val == "")continue;
		else	$para_filter[$key] = $para[$key];
	}
	return $para_filter;
}
//::==========================================================================================
//::  单元名称：	argSort($para)
//::  参数描述：	$para 需要拼接的数组
//::  返 回 值：	排序后的数组
//::  作用描述：	对数组排序
//::  单元依赖：	
//::  作者描述：	Venice
//::  编辑时间：	2015-10-06 17:37
//::==========================================================================================
function argSort($para) {
	ksort($para);
	reset($para);
	return $para;
}
//::==========================================================================================
//::  单元名称：	logResult($word)
//::  参数描述：	$word 要写入日志里的文本内容 默认值：空值
//::  返 回 值：	排序后的数组
//::  作用描述：	写日志，方便测试（看网站需求，也可以改成把记录存入数据库）
//::  注    意：	服务器需要开通fopen配置
//::  单元依赖：	
//::  作者描述：	Venice
//::  编辑时间：	2015-10-06 17:38
//::==========================================================================================
function logResult($word='') {
	$fp = fopen("log.txt","a");
	flock($fp, LOCK_EX) ;
	fwrite($fp,"执行日期：".strftime("%Y%m%d%H%M%S",time())."\n".$word."\n");
	flock($fp, LOCK_UN);
	fclose($fp);
}
//::==========================================================================================
//::  单元名称：	getHttpResponsePOST($url, $cacert_url, $para, $input_charset)
//::  参数描述：	$url 指定URL完整路径地址
//::  				$cacert_url 指定当前工作目录绝对路径
//::  				$para 请求的数据
//::  				$input_charset 编码格式。默认值：空值
//::  返 回 值：	远程输出的数据
//::  作用描述：	远程获取数据，POST模式
//::  注    意：	1.使用Crul需要修改服务器中php.ini文件的设置，找到php_curl.dll去掉前面的";"就行了
//::  				2.文件夹中cacert.pem是SSL证书请保证其路径有效，目前默认路径是：getcwd().'\\cacert.pem'
//::  单元依赖：	
//::  作者描述：	Venice
//::  编辑时间：	2015-10-06 17:40
//::==========================================================================================
function getHttpResponsePOST($url, $cacert_url, $para, $input_charset = '') {
	if (trim($input_charset) != '') {
		$url = $url."_input_charset=".$input_charset;
	}
	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);//SSL证书认证
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);//严格认证
	curl_setopt($curl, CURLOPT_CAINFO,$cacert_url);//证书地址
	curl_setopt($curl, CURLOPT_HEADER, 0 ); // 过滤HTTP头
	curl_setopt($curl,CURLOPT_RETURNTRANSFER, 1);// 显示输出结果
	curl_setopt($curl,CURLOPT_POST,true); // post传输数据
	curl_setopt($curl,CURLOPT_POSTFIELDS,$para);// post传输数据
	$responseText = curl_exec($curl);
	//var_dump( curl_error($curl) );//如果执行curl过程中出现异常，可打开此开关，以便查看异常内容
	curl_close($curl);
	return $responseText;
}
//::==========================================================================================
//::  单元名称：	getHttpResponseGET($url, $cacert_url)
//::  参数描述：	$url 指定URL完整路径地址
//::  				$cacert_url 指定当前工作目录绝对路径
//::  返 回 值：	远程输出的数据
//::  作用描述：	远程获取数据，GET模式
//::  注    意：	1.使用Crul需要修改服务器中php.ini文件的设置，找到php_curl.dll去掉前面的";"就行了
//::  				2.文件夹中cacert.pem是SSL证书请保证其路径有效，目前默认路径是：getcwd().'\\cacert.pem'
//::  单元依赖：	
//::  作者描述：	Venice
//::  编辑时间：	2015-10-06 17:42
//::==========================================================================================
function getHttpResponseGET($url, $cacert_url) {
	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_HEADER, 0 ); // 过滤HTTP头
	curl_setopt($curl,CURLOPT_RETURNTRANSFER, 1);// 显示输出结果
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);//SSL证书认证
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);//严格认证
	curl_setopt($curl, CURLOPT_CAINFO,$cacert_url);//证书地址
	$responseText = curl_exec($curl);
	//var_dump( curl_error($curl) );//如果执行curl过程中出现异常，可打开此开关，以便查看异常内容
	curl_close($curl);
	return $responseText;
}
//::==========================================================================================
//::  单元名称：	charsetEncode($input, $_output_charset, $_input_charset)
//::  参数描述：	$input 需要编码的字符串
//::  				$_output_charset 输出的编码格式
//::  				$_input_charset 输入的编码格式
//::  返 回 值：	编码后的字符串
//::  作用描述：	实现多种字符编码方式
//::  单元依赖：	
//::  作者描述：	Venice
//::  编辑时间：	2015-10-06 17:43
//::==========================================================================================
function charsetEncode($input, $_output_charset, $_input_charset) {
	$output = "";
	if(!isset($_output_charset))$_output_charset = $_input_charset;
	if($_input_charset == $_output_charset || $input == null ) {
		$output = $input;
	} elseif (function_exists("mb_convert_encoding")) {
		$output = mb_convert_encoding($input,$_output_charset,$_input_charset);
	} elseif(function_exists("iconv")) {
		$output = iconv($_input_charset,$_output_charset,$input);
	} else die("sorry, you have no libs support for charset change.");
	return $output;
}
//::==========================================================================================
//::  单元名称：	charsetDecode($input, $_input_charset, $_output_charset)
//::  参数描述：	$input 需要编码的字符串
//::  				$_output_charset 输出的编码格式
//::  				$_input_charset 输入的编码格式
//::  返 回 值：	解码后的字符串
//::  作用描述：	实现多种字符解码方式
//::  单元依赖：	
//::  作者描述：	Venice
//::  编辑时间：	2015-10-06 17:45
//::==========================================================================================
function charsetDecode($input, $_input_charset, $_output_charset) {
	$output = "";
	if(!isset($_input_charset))$_input_charset = $_input_charset ;
	if($_input_charset == $_output_charset || $input == null ) {
		$output = $input;
	} elseif (function_exists("mb_convert_encoding")) {
		$output = mb_convert_encoding($input,$_output_charset,$_input_charset);
	} elseif(function_exists("iconv")) {
		$output = iconv($_input_charset,$_output_charset,$input);
	} else die("sorry, you have no libs support for charset changes.");
	return $output;
}


//::==========================================================================================
//::  单元名称：	rsaSign($data, $private_key_path)
//::  参数描述：	$data 待签名数据
//::  				$private_key_path 商户私钥文件路径
//::  返 回 值：	解码后的字符串
//::  作用描述：	RSA签名
//::  单元依赖：	
//::  作者描述：	Venice
//::  编辑时间：	2015-10-06 17:46
//::==========================================================================================
function rsaSign($data, $private_key_path) {
    $priKey = file_get_contents($private_key_path);
    $res = openssl_get_privatekey($priKey);
    openssl_sign($data, $sign, $res);
    openssl_free_key($res);
	//base64编码
    $sign = base64_encode($sign);
    return $sign;
}
//::==========================================================================================
//::  单元名称：	rsaVerify($data, $ali_public_key_path, $sign)
//::  参数描述：	$data 待签名数据
//::  				$ali_public_key_path 支付宝的公钥文件路径
//::  				$sign 要校对的的签名结果
//::  返 回 值：	验证结果
//::  作用描述：	RSA验签
//::  单元依赖：	
//::  作者描述：	Venice
//::  编辑时间：	2015-10-06 17:48
//::==========================================================================================
function rsaVerify($data, $ali_public_key_path, $sign)  {
	$pubKey = file_get_contents($ali_public_key_path);
    $res = openssl_get_publickey($pubKey);
    $result = (bool)openssl_verify($data, base64_decode($sign), $res);
    openssl_free_key($res);    
    return $result;
}
//::==========================================================================================
//::  单元名称：	rsaDecrypt($content, $private_key_path)
//::  参数描述：	$content 需要解密的内容，密文
//::  				$private_key_path 商户私钥文件路径
//::  返 回 值：	解密后内容，明文
//::  作用描述：	RSA解密
//::  单元依赖：	
//::  作者描述：	Venice
//::  编辑时间：	2015-10-06 17:51
//::==========================================================================================
function rsaDecrypt($content, $private_key_path) {
    $priKey = file_get_contents($private_key_path);
    $res = openssl_get_privatekey($priKey);
	//用base64将内容还原成二进制
    $content = base64_decode($content);
	//把需要解密的内容，按128位拆开解密
    $result = '';
    for($i = 0; $i < strlen($content)/128; $i++  ) {
        $data = substr($content, $i * 128, 128);
        openssl_private_decrypt($data, $decrypt, $res);
        $result .= $decrypt;
    }
    openssl_free_key($res);
    return $result;
}

?>