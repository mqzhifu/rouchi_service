<?php
namespace Jy\Common\MsgQueue\Test\Product;
class ProductSmsBean{
    public $_id = 1;
    public $_type = "";
    public $_msg = "";

    function getBeanName(){
        return __CLASS__;
    }
}