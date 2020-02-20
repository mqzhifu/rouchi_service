<?php
namespace Jy\Common\MsgQueue\Test\Product;
class ProductUserBean{
    public $_id = 1;
    public $_nickName = "";
    public $_realName = "";
    public $_regTime = 0;
    public $_birthday = 0;

    function getBeanName(){
        return __CLASS__;
    }
}