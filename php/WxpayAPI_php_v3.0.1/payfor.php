<?php
// 小程序访问的地址文件



include 'pay.php';
$appid = '';
$openid = $_GET['openid'];
$mch_id = '';
$key = '';
$out_trade_no = $mch_id.time();
$total_fee = $GET['fee'];   //金额
if(empty($total_fee)){
  $body = '请充值';
  $total_fee = floatval(99*100);
}else{
  $body = "请付费";
  $total_fee = floatval($total_fee*100);
}
$weixinpay = new WexinPay($appid,$openid,$mch_id,$key,$out_trade_no,$body,$total_fee);
$return = $weixinpay->pay();

echo json_decode($return);





 ?>
