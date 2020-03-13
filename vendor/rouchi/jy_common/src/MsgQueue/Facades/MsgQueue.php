<?php
namespace Jy\Common\MsgQueue\Facades;
use Jy\Common\MsgQueue\MsgQueue\RabbitmqBean;
use Jy\Facade\Config;
use Jy\Log\Facades\Log;

class MsgQueue {
    private $eProvide = "rabbitmq";
    private static $instance = null;
    private $eInstantce = null;

//    private static $sProvide = "rabbitmq";

    public static function getInstance($provider = "rabbitmq",$conf = []){
        if(self::$instance){
            return self::$instance;
        }

        if(!$conf){
            $conf = Config::get("rabbitmq",'rabbitmq');
        }else{
            Log::getInstance()->init('_path', "./log");
        }

        if(!$provider || $provider == 'rabbitmq'){
            $self =  new  RabbitmqBean($conf);
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