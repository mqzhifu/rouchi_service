<?php
namespace Jy\Util;
class CheckFramework{
    static $inc = null;
    static function getInstance(){
        if(self::$inc){
            return self::$inc;
        }
        $inc = new self();
        self::$inc = $inc;
        return $inc;
    }
    function checkExt(){
        $arr = array('gd','curl','pdo','mbstring',"mysqli",'openssl');
        foreach ($arr as $k=>$v) {
            if(!extension_loaded($v)){
                throw new \Exception(1100001,"check ext err: no include $v  . include list:". json_encode($arr));
            }
        }
        return true;
    }

    function checkConst(){
        $constList = array('ROUCHI_ROOT_PATH','ROUCHI_CONF_PATH','ROUCHI_LOG_PATH','ROUCHI_APP_NAME');
        foreach ($constList as $k=>$v) {
            if(!defined($v)){
                throw new \Exception(1100002,"check const err: no include $v  . include list:". json_encode($constList));
            }
        }

        return true;
    }

    function checkPHPVersion(){
        $version = substr(PHP_VERSION,0,3);
        if($version < "7.2"){
            throw new \Exception(1100003,"PHP VERSION last:7.2.0");
        }
    }

    function check(){
        $this->checkExt();
        $this->checkConst();
        $this->checkPHPVersion();
    }
}