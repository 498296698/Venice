<?php
/* ------- 格式转换工具 ------- */
class Convert{

	public static function en($type, $data, $decode){
		if ($type == 'MD5' || $type == 'sha1' || $type == 'json_encode' || $type == 'json_decode' || $type == 'serialize' || $type == 'unserialize' || $type == 'urlencode' || $type == 'urldecode') {
			$result = $type($data);
		} elseif ($type == 'object') {
			$result = (object)$data;
		} elseif ($type == 'array') {
			$result = self::object_to_array($data);
		} elseif ($type == 'iconv') {
			$encode = mb_detect_encoding($data,array("ASCII","UTF-8","GB2312","GBK","BIG5"));
			$result = iconv($encode,$decode."//IGNORE",$data);
		}
		return $result;
	}

	protected static function object_to_array($array){ 
	  	if (is_object($array)) {
	    	$result = (array)$array;
	  	} elseif (is_array($array)) {
	  		foreach($array as $key=>$value){
	    		$result[$key] = self::object_to_array($value);
	    	}
	  	}
	  	return $result;
	} 



    /**
     * url转换(保留中文字符)
     * @param string $data 待转换数据
     * @return string
     */
    public static function UrlConvert($data){
        return urldecode(json_encode(self::UrlsEncode($data)));
    }

    protected static function UrlsEncode($data){
        if(is_array($data)){
            foreach($data as $key => $value){
                $data[urlencode($key)] = self::UrlsEncode($value);
            }
        }else{
            $data = urlencode($data);
        }
        return $data;
    }
}