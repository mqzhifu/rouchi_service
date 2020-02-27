<?php
namespace Jy\Common\MsgQueue\Facades;
use Jy\Common\MsgQueue\MsgQueue\RabbitmqBean;
use Jy\Facade\Config;

class MsgQueue {
    private $eProvide = "rabbitmq";
    private static $instance = null;
    private $eInstantce = null;

//    private static $sProvide = "rabbitmq";

    public static function getInstance($provider = "rabbitmq",$debug = 0){
        if(self::$instance){
            return self::$instance;
        }


        $conf = Config::get("rabbitmq",'rabbitmq');
        if(!$provider || $provider == 'rabbitmq'){
            $self =  new  RabbitmqBean($conf,$debug);
        }

        self::$instance = $self;
        return self::$instance;
    }

    public static function __callStatic($name, $args)
    {
        if (is_callable(static::getInstance(), $name)) {
            throw new \Exception("method name :  ". $name. " not exists");
        }

        return static::getInstance()->$name(...$args);
    }



    public function getEInstance(){
        if($this->eInstantce){
            return $this->eInstantce;
        }
        if($this->eProvide == 'rabbitmq'){
            $self =  new  RabbitmqBean();
        }

        $this->eInstantce = $self;
        return $this->eInstantce;
    }

    public function __call($name, $args)
    {
        if (is_callable($this->getEInstance(), $name)) {
            throw new \Exception("method name :  ". $name. " not exists");
        }

        return $this->getEInstance()->$name(...$args);
    }

}