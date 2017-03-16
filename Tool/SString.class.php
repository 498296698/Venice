<?php
/* ------- 字符串处理 ------- */
class SString
{
    /**
     * 生成随机字符串
     * @access public
     * @param string $type 类型
     * @param int $length 长度
     * @param string $add_chars 附加字符
     * @return string
     */
	public static function set_random($type='default', $length=6, $add_chars='')
	{
	    switch ($type) {
	        case 'num':
	            $chars = '0123456789'.$add_chars;
	            break;
	        case 'default':
	            $chars = 'abcdefghijklmnopqrstuvwxyz0123456789'.$add_chars;
	            break;
	        case 'string':
	            $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'.$add_chars;
	            break;
	        case 'mix':
	            $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_ []{}<>~`+=,.;:/?|'.$add_chars;
	            break;
	    }
	    for ($i=0; $i<$length; $i++) {
	        $random .= $chars[mt_rand(0, strlen($chars)-1)];
	    }
	    return $random;
	}

    /**
     * 生成不重复的随机数
     * @access public
     * @param string $type 类型
     * @param int $length 长度，一般为8,16,32
     * @return string
     */
	public static function set_uniqid($type='default', $length=16)
	{
		$string = uniqid().rand(1, 100);

		switch ($type) {
			case 'default':
				break;
			case 'MD5':
				$string = MD5($string);
				break;
		}
		$le = strlen($string);

		if (16 > $le) {
			$this->set_uniqid($type, $length);
		}elseif (16 <= $le && $length == 8) {
			$string = substr($string, 8, $length);
		} elseif (24 <= $le && $length == 16) {
			$string = substr($string, 8, $length);
		} else {
			$string = substr($string, 0, $length);
		}

	    return $string;
	}

    /**
     * 生成UUID(通用唯一识别码) 单机使用
     * @access public
     * @return string
     */
    public static function uuid()
    {
        $charid = md5(uniqid(mt_rand(), true));
        $hyphen = chr(45);// "-"
        $uuid = chr(123)// "{"
               .substr($charid, 0, 8).$hyphen
               .substr($charid, 8, 4).$hyphen
               .substr($charid, 12, 4).$hyphen
               .substr($charid, 16, 4).$hyphen
               .substr($charid, 20, 12)
               .chr(125);// "}"
        return $uuid;
    }

    /**
     * 生成不重复字符串
     * @param int $len 长度
     * @return string
     */
    public static function noRepeatString($len=32)
    {
        $charid = '';
        for ($i=0; $i<ceil($len/32); $i++) {
            $charid .= strtoupper(md5(uniqid(mt_rand(), true)));
        }
        $uuid = substr($charid, 0, $len);
        return $uuid;
    }

    /**
     * 生成Guid主键
     * @return Boolean
     */
    public static function keyGen()
    {
        return str_replace('-', '', substr(self::uuid(), 1, -1));
    }

    /**
     * 获取一定范围内的随机数字 位数不足补零
     * @param integer $min 最小值
     * @param integer $max 最大值
     * @return string
     */
    public static function randNumber($min, $max)
    {
        return sprintf("%0".strlen($max)."d", mt_rand($min, $max));
    }

    /**
     * 自动转换字符集(支持字符串和数组)
     * @param string $string 需要转换的字符串
     * @param string $from 转换前格式
     * @param string $to 转换后格式
     * @return string
     */
    public static function autoCharset($string, $from = 'gbk', $to = 'utf-8')
    {
        $from = strtoupper($from) == 'UTF8' ? 'utf-8' : $from;
        $to = strtoupper($to) == 'UTF8' ? 'utf-8' : $to;
        if (strtoupper($from) === strtoupper($to) || empty($string) || (is_scalar($string) && !is_string($string))) {
            //如果编码相同或者非字符串标量则不转换
            return $string;
        }
        if (is_string($string)) {
            if (function_exists('mb_convert_encoding')) {
                return mb_convert_encoding($string, $to, $from);
            } elseif (function_exists('iconv')) {
                return iconv($from, $to, $string);
            } else {
                return $string;
            }
        } elseif (is_array($string)) {
            foreach ($string as $key => $val) {
                $_key = self::autoCharset($key, $from, $to);
                $string[$_key] = self::autoCharset($val, $from, $to);
                if ($key != $_key) {
                    unset($string[$key]);
                }
            }
            return $string;
        } else {
            return $string;
        }
    }

    /**
     * 检查字符串是否是UTF8编码
     * @param string $string 字符串
     * @return Boolean
     */
    public static function isUtf8($str)
    {
        $c=0;
        $b=0;
        $bits=0;
        $len=strlen($str);
        for ($i=0; $i<$len; $i++) {
            $c=ord($str[$i]);
            if ($c > 128) {
                if (($c >= 254)) {
                    return false;
                }
                elseif($c >= 252) $bits=6;
                elseif($c >= 248) $bits=5;
                elseif($c >= 240) $bits=4;
                elseif($c >= 224) $bits=3;
                elseif($c >= 192) $bits=2;
                else {
                    return false;
                }
                if (($i+$bits) > $len) {
                    return false;
                }
                while ($bits > 1) {
                    $i++;
                    $b=ord($str[$i]);
                    if ($b < 128 || $b > 191) {
                        return false;
                    }
                    $bits--;
                }
            }
        }
        return true;
    }

    /**
     * 判断是否手机号码
     * @param string $mobile 手机号码
     * @return boolean
     */
	public static function is_mobile($mobile)
	{
	    if (!is_numeric($mobile)) {
	        return false;
	    }
	    return preg_match('#^13[\d]{9}$|^14[5,7]{1}\d{8}$|^15[^4]{1}\d{8}$|^17[0,6,7,8]{1}\d{8}$|^18[\d]{9}$#', $mobile) ? true : false;
	}

    /**
     * 判断是否身份证号码
     * @param string $idcard 身份证号码
     * @return boolean
     */
	public static function is_idcard($idcard)
	{
	    if (strlen($idcard) != 18) {
	        return false;
	    }
	    $set = array(7,9,10,5,8,4,2,1,6,3,7,9,10,5,8,4,2);
	    $ver = array('1','0','x','9','8','7','6','5','4','3','2');
	    $arr = str_split($idcard);
	    $sum = 0;
	    for ($i = 0; $i < 17; $i++) {
	        if (!is_numeric($arr[$i])) {
	            return false;
	        }
	        $sum += $arr[$i] * $set[$i];
	    }
	    $mod = $sum % 11;
	    if (strcasecmp($ver[$mod], $arr[17]) != 0) {
	        return false;
	    }
	    return true;
	}

    /**
     * 判断是否电子邮箱
     * @param string $mail 电子邮箱
     * @return boolean
     */
	public static function is_mail(&$mail)
	{
	    $val = strtolower($mail);
	    $regexp = "/^([a-z0-9+_]|\\-|\\.)+@(([a-z0-9_]|\\-)+\\.)+[a-z]{2,5}\$/";
	    if (preg_match($regexp, $val)) {
	        $mail = $val;
	        return true;
	    }
	    return false;
	}

}

