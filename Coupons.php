<?php
/*
卡券类，未继续
*/
class Coupons
{
    private $pass = '';
    private $iv = '';
    private $signKey = '';

    /**
     * aes加密
     * @param $text
     * @return string
     */
    public function aesEncrypt($text): string
    {
        $ret = base64_encode(openssl_encrypt($text, 'AES-128-CBC', $this->pass, OPENSSL_RAW_DATA, $this->iv));
        $bytes = Bytes::getBytes($ret);
        $str = '';
        for ($i = 0; $i < count($bytes); $i++) {
            $str .= chr((($bytes[$i] >> 4) & 0xF) + ord('a'));
            $str .= chr((($bytes[$i]) & 0xF) + ord('a'));
        }
        return $str;
    }

    /**
     * aes解密
     * @param $text
     * @return string
     */
    public function aesDecrypt($text): string
    {
        $bytes = [];
        for ($i = 0; $i < strlen($text); $i += 2) {
            $c = $text[$i];
            $bytes[$i / 2] = (ord($c) - ord('a')) << 4;
            $c = $text[$i+1];
            $bytes[$i / 2] += (ord($c) - ord('a'));
        }
        $text = Bytes::toStr($bytes);
        return openssl_decrypt(base64_decode($text), 'AES-128-CBC', $this->pass, OPENSSL_RAW_DATA, $this->iv);
    }

}