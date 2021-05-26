<?php
/*
//H5支付
$pay = new Pay([
	"p12"=>"p12文件地址",
	"pem"=>"pem文件地址",
	"merchantNo"=>"商户号",
	"passwd"=>"证书密钥",
]);
$params = [
    'order_sn'=>'订单号', //订单号
    'order_amount'=>0.01, //订单金额
    'request_date'=>date('Y-m-d H:i:s'), //下单日期
    'subject'=>'测试h5订单支付', //订单信息，在用户账单中展示
    'goods_info'=>'测试商品', //交易订单包含的商品信息，仅记录用
    'notify_url'=>'notify.html', //支付结果异步通知地址
    'trade_desc'=>'测试商品支付', //必填，订单描述，展示在收银台页面上
    'merchant_front_url'=>'info.html', //必填，支付结果前端通知地址
];
return $pay->h5Pay($params);
 */
class Pay
{

    private $h5gateway='https://mapi.bestpay.com.cn/mapi/form/cashier/H5/pay';
    private $url='https://mapi.bestpay.com.cn/mapi/sdkRequest?BESTPAY_MAPI_VERSION=1.0';
    private $merchantNo;
    private $passwd;
    private $p12;
    private $pem;

    /**
     * PayHelper constructor.
     * @param array $options
     */
    public function __construct($options=[])
    {
        $this->p12 = $options['p12'];
        $this->pem = $options['pem'];
        $this->merchantNo = $options['merchantNo'];
        $this->passwd = $options['passwd'];
    }

    /**
     * H5支付
     * @param $params
     * @param array $appendParams  追加参数,适配翼支付其他非必填参数
     * @return string
     * @throws \Exception
     */
    public function h5Pay($params,$appendParams=[]){
        $proCreateOrder = $this->proCreateOrder($params,$appendParams);
        if($proCreateOrder['tradeStatus']=='FAIL'){
            throw new \Exception("{$proCreateOrder['errorCode']}:{$proCreateOrder['errorMsg']}");
        }
        $formData =  [
            'merchantNo'=>$this->merchantNo,
            'institutionCode'=>$this->merchantNo,
            'institutionType'=>'MERCHANT',
            'signType'=>'CA',
            'platform'=>$params['platform'] ?? 'H5_4.0_route',
            'tradeType'=>'acquiring',
            'outTradeNo'=>$params['order_sn'],
            'tradeNo'=>$proCreateOrder['tradeNo'], //翼支付交易单号
            'tradeAmt'=>$params['order_amount'] * 100,
            'tradeDesc'=>$params['trade_desc'],
            'merchantFrontUrl'=>$params['merchant_front_url'],
        ];
        $formData['sign'] = $this->createSign($formData);
        $html = "<form id='payform' name='payform' action='{$this->h5gateway}' method='post'>";
        foreach($formData as $key=>$value){
            $html .= "<input type='hidden' name='{$key}' value='{$value}'/>";
        }
        $html .= "<input type='submit' value='ok' style='display:none;'></form>";
        return "{$html}<script>document.forms['payform'].submit();</script>";
    }

    /**
     * 下单
     * @param $params
     * @param array $appendParams 追加参数,适配翼支付其他非必填参数
     * @return array
     * @throws \Exception
     */
    public function proCreateOrder($params,$appendParams=[])
    {
        $postData = [
            'merchantNo' => $this->merchantNo,
            'outTradeNo' => $params['order_sn'],
            'tradeAmt' => $params['order_amount'] * 100,
            'ccy' => '156',
            'requestDate' => $params['request_date'] ?? date('Y-m-d H:i:s'),
            "tradeChannel" => $params['trade_channel'] ?? 'H5',
            "accessCode" => "CASHIER",
            "mediumType" => "WIRELESS",
            "subject" => $params['subject'],
            "goodsInfo" => $params['goods_info'],
            "operator" => $this->merchantNo,
            "notifyUrl" => $params['notify_url'],
        ];
        //未设置风控参数自动补全风控参数
        if (!isset($params['riskControlInfo'])) {
            $postData['riskControlInfo'] = json_encode([[
                "service_identify" => $params['service_identify'] ?? '10000001',
                "subject" => $params['subject'],
                "product_type" => $params['product_type'] ?? 3,
                "boby" => $params['body'] ?? $params['subject'],
                "goods_count" => $params['goods_count'] ?? 1,
                "service_cardno" => $params['service_cardno'] ?? '',
            ]],JSON_UNESCAPED_UNICODE);
        }
        if(!empty($appendParams)) $postData = array_merge($postData,$appendParams);
        return $this->postApi('/uniformReceipt/proCreateOrder', $postData);
    }

    /**
     * 下单查询
     * @param $params
     * @param array $appendParams
     * @return array
     * @throws \Exception
     */
    public function tradeQuery($params,$appendParams=[]){
        $postData = [
            'outTradeNo'=>$params['order_sn'],
            'merchantNo'=>$this->merchantNo,
            'tradeDate'=>$params['trade_date'],
        ];
        if(!empty($appendParams)) $postData = array_merge($postData,$appendParams);
        return $this->postApi('/uniformReceipt/tradeQuery', $postData);
    }

    /**
     * 退款
     * @param $params
     * @param $appendParams
     * @return array
     * @throws \Exception
     */
    public function tradeRefund($params,$appendParams=[]){
        $postData = [
            'merchantNo'=>$this->merchantNo,
            'outTradeNo'=>$params['order_sn'],
            'outRequestNo'=>$params['refund_sn'],
            'originalTradeDate'=>$params['original_trade_date'], //下单时间
            'refundAmt'=>$params['refund_amount'],
            'requestDate'=>date('Y-m-d H:i:s'),
            'tradeDate'=>$params['trade_date'],
            'operator'=>$this->merchantNo,
            'tradeChannel'=>$params['trade_channel'] ?? 'H5',
            'ccy'=>'156',
            "accessCode" => "CASHIER",
            "refundCause" => $params['refund_cause'] ?? '',
            "notifyUrl" => $params['notify_url'] ?? '',
            "remark" => $params['remark'] ?? '',
        ];
        if(!empty($appendParams)) $postData = array_merge($postData,$appendParams);
        return $this->postApi('/uniformReceipt/tradeRefund', $postData);
    }

    /**
     * 退款查询接口
     * @param $params
     * @param array $appendParams
     * @return array
     * @throws \Exception
     */
    public function refundOrderQuery($params,$appendParams=[]){
        $postData = [
            'outTradeNo'=>$params['refund_sn'],
            'merchantNo'=>$this->merchantNo,
            'tradeDate'=>$params['trade_date'], //下单时间
        ];
        if(!empty($appendParams)) $postData = array_merge($postData,$appendParams);
        return $this->postApi('/uniformReceipt/refundOrderQuery', $postData);
    }

    /**
     * 关闭订单
     * @param $params
     * @param array $appendParams
     * @return array
     * @throws \Exception
     */
    public function closeOrder($params,$appendParams=[]){
        $postData = [
            'merchantNo'=>$this->merchantNo,
            'operator'=>$this->merchantNo,
            'outTradeNo'=>$params['order_sn'],
            'tradeDate'=>$params['trade_date'],
            'closeReason'=>$params['close_reason'] ?? '',
        ];
        if(!empty($appendParams)) $postData = array_merge($postData,$appendParams);
        return $this->postApi('/tradeprod/closeOrder', $postData);
    }

    /**
     * 订单生成二维码
     * @param $params
     * @param array $appendParams
     * @return array
     * @throws \Exception
     */
    public function createOrderQrCode($params,$appendParams=[]){
        $postData = [
            'merchantNo'=>$this->merchantNo,
            'outTradeNo'=>$params['order_sn'],
            'tradeAmt'=>$params['order_amount'],
            'ccy'=>'156',
            'requestDate'=>$params['request_date'] ?? date('YmdHis'),
            'goodsInfo'=>$params['goods_info'],
            'subject'=>$params['subject'],
            'operator'=>$this->merchantNo,
        ];
        //未设置风控参数自动补全风控参数
        if (!isset($params['riskControlInfo'])) {
            $postData['riskControlInfo'] = json_encode([[
                "service_identify" => $params['service_identify'] ?? '10000001',
                "subject" => $params['subject'],
                "product_type" => $params['product_type'] ?? 3,
                "boby" => $params['body'] ?? $params['subject'],
                "goods_count" => $params['goods_count'] ?? 1,
                "service_cardno" => $params['service_cardno'] ?? '',
            ]],JSON_UNESCAPED_UNICODE);
        }
        if(!empty($appendParams)) $postData = array_merge($postData,$appendParams);
        return $this->postApi('/createOrderQrCode', $postData);
    }

    /**
     * 以Post请求接口
     * @param string $path 请求地址
     * @param array $bizContent 业务内容
     * @return array|bool|string
     * @throws \Exception
     */
    public function postApi(string $path, array $bizContent)
    {
        $commonParams = ['institutionCode' => $this->merchantNo,'institutionType' => 'MERCHANT'];
        $postData = [
            'bizContent'=>json_encode($bizContent,JSON_UNESCAPED_UNICODE),
            'commonParams'=>json_encode($commonParams,JSON_UNESCAPED_UNICODE),
            'path'=>$path,
        ];
        $postData['sign'] = $this->createSign($postData);
        $postData['version'] = '1.0';
        $ret = $this->post($this->url, json_encode($postData,JSON_UNESCAPED_UNICODE));
        if($path=='/createOrderQrCode') return $ret; //二维码直接返回数据
        $ret = json_decode($ret,true);
        if($ret['success']===false) throw new \Exception("{$ret['errorCode']}:{$ret['errorMsg']}");
        //本地校验签名
        if(!$this->verifySign($ret)) throw new \Exception('签名校验失败');
        return $ret['result']; //该数据业务逻辑处还需要判断具体内容详情
    }

    /**
     * 排序参数
     * @param $params
     * @return string
     */
    public function sort($params){
        $paramsJoined = [];
        if (isset($params['sign'])) unset($params['sign']);
        ksort($params);
        foreach ($params as $k => $v) {
            if (is_array($v)) $v = json_encode($v, JSON_UNESCAPED_UNICODE);
            if(is_null($v)) $v='null';
            if(is_bool($v)) $v=$v=='true' ? 'true' : 'false';
            $paramsJoined[] = "{$k}={$v}";
        }
        return implode('&', $paramsJoined);
    }

    /**
     * 创建sign
     * @param $params
     * @return string
     */
    public function createSign($params)
    {
        $params = $this->sort($params);
        openssl_pkcs12_read(file_get_contents($this->p12), $certs, $this->passwd);//证书密码
        if(empty($certs)) return '';
        openssl_sign($params, $signMsg, $certs['pkey'], OPENSSL_ALGO_SHA256); //注册生成加密信息
        return $signMsg ? base64_encode($signMsg) : "";
    }

    /**
     * 校验签名
     * @param $params
     * @return bool
     */
    public function verifySign($params){
        if(!is_array($params)) $params = json_decode($params,true);
        if(isset($params['result'])){ //result排序
            ksort($params['result']);
            $params['result'] = json_encode($params['result'],JSON_UNESCAPED_UNICODE); //排序result
        }
        $sign = base64_decode($params['sign']);
        unset($params['sign']);
        $todoSign = str_replace('"null"','null',$this->sort($params));
        $key = openssl_pkey_get_public(file_get_contents($this->pem));
        return openssl_verify($todoSign, $sign, $key, OPENSSL_ALGO_SHA1);
    }

    /**
     * CURL请求
     * @param string $url 请求方法
     * @param $params
     * @return boolean|string
     */
    private function post(string $url, $params)
    {
        $curl = curl_init();
        $headers = [
            'Content-Type: application/json;charset=UTF-8'
        ];
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_TIMEOUT, 60);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        $content = curl_exec($curl);
        curl_close($curl);
        return $content;
    }

}