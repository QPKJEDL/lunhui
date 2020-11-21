<?php


/**无缓存的唯一订单号
 * @param $paycode 支付类型
 * @param $business_code 商户id
 * @param $tablesuf 订单表后缀
 * @return string
 */
function getOrderSn(){
    @date_default_timezone_set("PRC");
    $requestId  =	date("YmdHis").rand(11111111,99999999);
    return $requestId;
}
