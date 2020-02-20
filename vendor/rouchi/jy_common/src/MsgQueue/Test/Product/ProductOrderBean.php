<?php
namespace Jy\Common\MsgQueue\Test\Product;
class ProductOrderBean{
    public $_id = 1;
    public $_channel = "tencent";//来源渠道
    public $_price = 0.00;//金额
    public $_num = 0;//购买数量
    public $_uid = 0;//用户ID

    function getBeanName(){
        return __CLASS__;
    }
}