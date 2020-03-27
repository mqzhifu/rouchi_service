<?php
namespace Jy\Common\MsgQueue\Facades;
use Jy\Common\MsgQueue\MsgQueue\RabbitmqBase;
use Jy\Facade\Config;
//use Jy\Log\Facades\Log;

class MsgQueue {
    private static $instance = null;
//    private static $sProvide = "rabbitmq";

    public static function getInstance($provider = "rabbitmq",$conf = [],$debugFlag = 0,$extType = 2){
        if(self::$instance){
            return self::$instance;
        }

        if(!$conf){
            $conf = self::getConf();
        }

        if(!$provider || $provider == 'rabbitmq'){
            $self =  new  RabbitmqBase($conf);
        }

        $self->setConf($conf);

        if($debugFlag){
            $self->setDebug($debugFlag);
        }

        if($extType){
            $self->setExtType($extType);
        }

        if(isset($conf['exchange_name']) && $conf['exchange_name']){
            $self->setTopicName($conf['exchange_name']);
        }

        $self->init($conf);

        self::$instance = $self;
        return self::$instance;
    }

    public static function __callStatic($name, $args){
        if (is_callable(static::getInstance(), $name))
            throw new \Exception("method name :  ". $name. " not exists");

        return static::getInstance()->$name(...$args);
    }

    public function __call($name, $args)
    {
        if (is_callable($this->getEInstance(), $name)) {
            throw new \Exception("method name :  ". $name. " not exists");
        }

        return $this->getEInstance()->$name(...$args);
    }

    static function getConf(){
        if(defined("PHP_BASE_MQ")){
            $conf = require_once self::getConfFile('rabbitqueue.php', 'php_base');
        }else{
            $conf = require_once getJyConfFile('rabbitqueue.php', 'php_base');
        }
        return $conf;
    }

    static function getConfFile($sFileUrl, $sAppName = "")
    {
        if (empty($sAppName)) $sAppName = getJyAppName();

        $sConfDirName = 'rouchi_conf';
        $sDir = explode(DIRECTORY_SEPARATOR, $sFileUrl);
        $sFile = array_pop($sDir);

        $sApproot = defined('ROUCHI_ROOT_PATH') ? ROUCHI_ROOT_PATH . '/..' : dirname(__FILE__) . '/../../../../../../../..';

        $sFileAddress = $sApproot . DIRECTORY_SEPARATOR . $sConfDirName . DIRECTORY_SEPARATOR . $sAppName . DIRECTORY_SEPARATOR . $sFile;

        if (is_file($sFileAddress)) {
            return $sFileAddress;
        } else {
            return FALSE;
        }
    }

}