<?php

namespace Jy;

use Jy\Facade\Log;
use Jy\Common\RequestContext\RequestContext;
use Jy\Facade\Trace;

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
        $protocol = $request->getProtocol();
        $version = $request->getVersion();

        $dir = $protocol == "cli" ? "Console" : "Controller";

        $namespace = "Rouchi\\{$dir}" .
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

        Trace::setServiceSendTrace(is_object($result) ? $result->getData() : ['none']);

        Log::info("action end");
        Log::buffFlushFile();

        RequestContext::destroy();

        exit();
    }
}
