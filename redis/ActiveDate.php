<?php
// 文章地址：http://blog.csdn.net/orangeholic/article/details/39025239

// 签到系统
class ActiveDate
{
    private static $redis = null;
    private static $redisConf = ['host'=>'localhost', 'port'=>6379];
    private static $prefix = 'user:active:';
    private static $redisSelect = 0;

    private static function getRedis()
    {
        if (!self::$redis) {
            $redis = new Redis();
            if (!$redis->connect(self::$redisConf['host'], self::$redisConf['port'])) {
                throw new Exception("Error Redis Connect", 100);
            }
            $redis->select(self::$redisSelect);
            self::$redis = $redis;
        }
        return self::$redis;
    }

    /**
     * 设置用户某天登录过
     */
    public static function setActiveDate($userId='', $time=null)
    {
        if (empty($time)) {
            $time = time();
        }

        $redis = self::getRedis();
        // 根据每个用户每个月缓存一条数据
        $redis->setBit(self::$prefix . $userId . ':' . date('Y-m', $time), intval(date('d', $time)) - 1, 1);
        return true;
    }

    /**
     * 得到用户本月登录天数
     * redis >= 2.6.0 才可以
     */
    public static function getActiveDatesCount($userId='', $time = null)
    {
        if (!$userId) {
            return false;
        }
        if (empty($time)) {
            $time = time();
        }

        $redis = self::getRedis();
        return $redis->bitCount(self::$prefix . $userId . ':' . date('Y-m', $time));
    }

    /**
     * 得到用户某月所有的登录过日期（默认读取当月）
     */
    public static function getActiveDates($userId, $time = null)
    {
        if (!$userId) {
            return false;
        }
        if (empty($time)) {
            $time = time();
        }

        $redis = self::getRedis();
        $strData = $redis->get(self::$prefix . $userId . ':' . date('Y-m', $time));

        $result = array();
        if (empty($strData)) {
            return $result;
        }
        $monthFirstDay = mktime(0, 0, 0, date("m", $time), 1, date("Y", $time));

        $maxDay = cal_days_in_month(CAL_GREGORIAN, date("m", $time), date("Y", $time)); // 返回某个历法中某年中某月的天数
        $charData = unpack("C*", $strData); // 从二进制中转换成数据
//        var_dump($charData);exit;

        for ( $index=1; $index<=count($charData); $index++ ) {
            for ( $bit=0; $bit<8; $bit++ ) {
                if ( $charData[$index] & 1 << $bit ) {
                    $intervalDay = $index * 8 - $bit;
                    if ( $intervalDay > $maxDay ) {
                        return $result;
                    }
                    $result[] = date('Y-m-d', $monthFirstDay + ($intervalDay-1) * 86400);
                }
            }
        }

        return $result;
    }
}

var_dump(ActiveDate::setActiveDate(1001,time()+86400));
var_dump(ActiveDate::setActiveDate(1001,time()+86400*2));
var_dump(ActiveDate::setActiveDate(1001,time()+86400*3));

var_dump(ActiveDate::getActiveDates(1001));

var_dump(ActiveDate::getActiveDatesCount(1001));