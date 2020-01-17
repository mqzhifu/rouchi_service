<?php

namespace Jy;

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

        // .. hook
        $result = $this->call($requests);
        return $result;
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

        $controller = new $namespace;

        if (!method_exists($controller, $action)) {
            throw new \Exception('action : '. $namespace .':'. $action .' not exists');
        }

        // heredoc
        //$annotation = \Jy\App::$app->reflect->resolveClass($namespace, $action, "method");
        //print_r($annotation);

        // hook
        $result = call_user_func_array([$controller, $action], $args);
        // hook

        echo $result;
        exit();
    }
}
