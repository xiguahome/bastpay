<?php

namespace helper;

class Bytes {

    /**
     * 转换一个string字符串为byte数组
     * @param $str
     * @return array
     */
    public static function getBytes($str) {
        $len = strlen($str);
        $bytes = array();
        for($i=0;$i<$len;$i++) {
            if(ord($str[$i]) >= 128){
                $byte = ord($str[$i]) - 256;
            }else{
                $byte = ord($str[$i]);
            }
            $bytes[] =  $byte ;
        }
        return $bytes;
    }

    /**
     * 将字节数组转化为string类型的数据
     * @param $bytes //字节数组
     * @return string
     */
    public static function toStr($bytes) {
        $str = '';
        foreach($bytes as $ch) {
            $str .= chr($ch);
        }
        return $str;
    }

    /**
     * 转换一个int为byte数组
     * @param $val //需要转换的字符串
     * @return array
     */
    public static function integer2bytes($val) {
        $byt = array();
        $byt[0] = ($val & 0xff);
        $byt[1] = ($val >> 8 & 0xff);
        $byt[2] = ($val >> 16 & 0xff);
        $byt[3] = ($val >> 24 & 0xff);
        return $byt;
    }

    /**
     * 从字节数组中指定的位置读取一个integer类型的数据
     * @param $bytes //字节数组
     * @param $position //指定的开始位置
     * @return int
     */
    public static function bytes2integer($bytes, $position) {
        $val = $bytes[$position + 3] & 0xff;
        $val <<= 8;
        $val |= $bytes[$position + 2] & 0xff;
        $val <<= 8;
        $val |= $bytes[$position + 1] & 0xff;
        $val <<= 8;
        $val |= $bytes[$position] & 0xff;
        return $val;
    }

    /**
     * 转换一个shor字符串为byte数组
     * @param $val //需要转换的字符串
     * @return array
     */
    public static function short2bytes($val) {
        $byt = array();
        $byt[0] = ($val & 0xff);
        $byt[1] = ($val >> 8 & 0xff);
        return $byt;
    }

    /**
     * 从字节数组中指定的位置读取一个short类型的数据
     * @param $bytes //字节数组
     * @param $position //指定的开始位置
     * @return int
     */
    public static function bytes2short($bytes, $position) {
        $val = $bytes[$position + 1] & 0xff;
        $val = $val << 8;
        $val |= $bytes[$position] & 0xff;
        return $val;
    }

}