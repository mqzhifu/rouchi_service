<?php

namespace Jy;

use Jy\Facade\Log;

class Dispatcher
{

    private $router;

    public function __construct()
    {
        $this->router = \Jy\App::$app->router;
    }

    public function dispatcher()
    {
        // init event

        return $this->execute();

    }

    public function execute()
    {
        //..
        $requests = \Jy\App::$app->request;
//        var_dump($requests);exit;
//        Log::getInstance()->setSysBaseInfo("bbbb");
        // .. hook
        $result = $this->call($requests);
        return $result;
    }

    function logAdapter($requests){
        $arr = array(
            'method'=>$requests->method,
        );
    }


    private function call(Request $request)
    {
        $module = $request->getModule();
        $action = $request->getAction();
        $args = $request->getArgs();
        $version = $this->router->getRequestVersion();

        $namespace = "Rouchi\\Controller" .
            "\\". ($version) . "\\" .
            str_ireplace("/", "\\\\", $module);

        if (!class_exists($namespace)) {
            throw new \Exception('controller : '. $namespace .' not exists');
        }

        $controller = \Jy\App::$app->di->getClassInstance($namespace);

        if (!method_exists($controller, $action)) {
            throw new \Exception('action : '. $namespace .':'. $action .' not exists');
        }

        Log::info("ctrl:$namespace, action:$action");
        // heredoc
        $annotation = \Jy\App::$app->reflect->resolveClass($namespace, $action, "method");
        if($annotation && isset($annotation['valid']) && $annotation['valid']){
            \Jy\Common\Valid\Facades\Valid::match(\Jy\App::$app->request->getArgs(),$annotation['valid']);
        }


        $para = \Jy\App::$app->di->initMethod($namespace,$action,$controller);
        // hook
        $result = call_user_func_array([$controller, $action], $para ?? []);
        // hook

        echo $result;

        Log::info("action end");
        exit();
    }
}
