<?php
// 类文件，小程序微信支付


class WexinPay{

  protected $appid;
  protected $mch_id;
  protected $key;
  protected $openid;
  protected $out_trade_no;
  protected $body;
  protected $total_fee;

  function __construct($appid, $openid, $mch_id, $key, $out_trade_no, $body, $total_fee){
    $this->appid = $appid;
    $this->openid = $openid;
    $this->mch_id = $mch_id;
    $this->key = $key;
    $this->out_trade_no = $out_trade_no;
    $this->body = $body;
    $this->total_fee = $total_fee;
  }

  public function pay(){
    $return = $this->weixinapp();
    return $return;
  }

  //接口
  private function unifiedOrder(){
    $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
    $parameters = array(
      'appid' => $this->appid,  //小程序id
      'mch_id' => $this->mch_id,   //商户号
      'nonce_str' => $this->createNoncestr(),   //随机字符串
      'body' => $this->body,     //商品描述
      'out_trade_no' => $this->out_trade_no,    //商户订单号
      'total_free' => $this->total_fee,     //金额
      'spbill_create_ip' => $_SERVER['REMOTE_ADDR'],   //终端ip
      'notify_url' => 'http://www.weixin.qq.com/wxpay/pay.php',  //通讯地址
      'openid' => $this->openid,    //用户id
      'trade_type' => 'JSAPI',      //交易类型
    );
    //签名
    $parameters['sign'] = $this->getSign($parameters);
    $xmlData = $this->arrayToXml($parameters);
    $return = $this->xmlToArray($this->postXmlCurl($xmlData, $url, 60));
    return $return;
  }

  private static function postXmlCurl($xml, $url, $second = 30){
    $ch = curl_init();
    //设置超时
    curl_setopt($ch, CURLOPT_TIMEOUT, $second);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);   //校验
    // 设施header
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    //输出
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    //POST 提交
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);

    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
    curl_setopt($ch, CURLOPT_TIMEOUT, 40);
    set_time_limit(0);

    // 运行curl
    $data = curl_exec($ch);
    //返回结果
    if($data){
      curl_close($ch);
      return $data;
    }else{
      $error = curl_errno($ch);
      curl_close($ch);
      throw new WxPayException("curl出错，错误代码：$error");
    }
  }

  // array => xml
  private function arrayToXml($arr){
    $xml = "<root>";
    foreach ($arr as $key => $value) {
      if(is_array($value)){
        $xml .= "<" .$key. ">" .arrayToXml($value). "</" .$key. ">";
      }else{
        $xml .= "<" .$key. ">" .$value. "</" .$key. ">";
      }
    }
    $xml .= "</root>";
    return $xml;
  }

  // xml => array
  private function xmlToArray($xml){
    // 禁止引用外部xml
    libxml_disable_entity_loader(true);
    $xmlstring = simplexml_load_string($xml, 'SimpleXmlElement', LIBXML_NOCDATA);
    $val = json_decode(json_decode($xmlstring), true);
    return $val;
  }

  // 微信接口
  private function weixinapp(){
      $unifiedorder = $this->unifiedOrder();
      //print_r($unifiedorder);
      $parameters = array(
        'appid' => $this->appid,   //id
        'timeStamp' => '' . time() . '',  //时间戳
        'nonceStr' => $this->createNoncestr(),  //随机串
        'package' => 'prepay_id=' . $unifiedorder['prepay_id'],
        'signType' => 'MD5'   //签名方式
      );
      //签名
      $parameters['paySign'] = $this->getSign($parameters);
      return $parameters;
  }
  // 生成随机字符串
  private function createNoncestr($length = 32){
    $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
    $str = "";
    for($i = 0; $i < $length; $i++){
      $str .= substr($char, mt_rand(0,strlen($char) - 1), 1);
    }
    return $str;
  }
  // 生成签名
  private function getSign($Obj){
    foreach($Obj as $key => $val){
      $parameters[$key] = $val;
    }
    // 按字典序排序参数
    ksort($parameters);
    $String = $this->formatBizQueryParaMap($parameters, false);
    // 在string后面添加key
    $String = $String . "&key" .$this->key;
    // MD5加密
    $String = msd5($String);
    // 字符转为大写
    $result = strtoupper($String);
    return $result;
  }
  // 签名过程中需要格式化参数，
  private function formatBizQueryParaMap($paraMap, $urlencode){
    $buff = "";
    ksort($paraMap);
    foreach ($paraMap as $key => $value) {
        if($urlencode){
          $value = urlencode($v);
        }
        $buff .= $key ."=" . $value . "&";
    }
    $rePar;
    if(strlen($buff) > 0){
      $rePar = substr($buff, 0, strlen($buff) - 1);
    }
    return $rePar;
  }

}








 ?>
