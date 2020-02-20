<?php
namespace Jy\Common\MsgQueue\Facades;
use Jy\Common\MsgQueue\MsgQueue\RabbitmqBean;
class MsgQueue {
    private static $instance = null;
    private $eInstantce = null;

//    private static $sProvide = "rabbitmq";

    public static function getInstance($provider = "rabbitmq"){
        if(self::$instance){
            return self::$instance;
        }
        if($provider == 'rabbitmq'){
            $self =  new  RabbitmqBean();
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



    private $eProvide = "rabbitmq";
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