<?php
namespace Jy\Log\Contract;

abstract class Main  implements MainInterface, PsrLoggerInterface {

    private $_delimiter = " | ";//一行内的消息块，分隔符

    private $_msgFormat = "";//自定义日志格式
    //日志格式 ： 请求ID|日期时间|client-IP|进程ID|脚本文件名|类/方法|  XXX自定义信息  (rid|dt||cip|pid|tr )
    private $_formatRule = array("rid","dt",'cip','pid','tr');
    //日志格式中，日期时间的格式
    private $_msgFormatDatetime = "Y-m-d H:i:s";

    private $_filter = "";//可以过滤掉 内容 中的一些特定字符 ，如：换行符
    private $_deepTrace = 0;//追踪回溯层级,0:全部

    private $_showScreen = 0;//输出到屏幕

    private $_replaceDelimiterLeft = "{";
    private $_replaceDelimiterRight = "}";


    function __construct(){

    }
    abstract function flush($info);


    function formatMsg($message ,array $context = array()){
        if(!$message){
            throw new \Exception("message is null.");
        }
        $formatInfo = $this->placeholder($message,$context);
        $formatInfo = json_encode( $this->replaceFormatMsg($formatInfo));

        if($this->_showScreen == 1){
            echo $formatInfo . "\r\n";
        }

        return $formatInfo;
    }

    //调试信息
    function emergency($message ,array $context = array()){
        return $this->formatMsg($message,$context);
    }
    //框架级日志
    function alert($message ,array $context = array()){
        return $this->formatMsg($message,$context);
    }
    //日常记录
    function critical($message ,array $context = array()){
        return $this->formatMsg($message,$context);
    }
    //警告
    function error($message ,array $context = array()){
        return $this->formatMsg($message,$context);
    }
    //致命
    function warning($message ,array $context = array()){
        return $this->formatMsg($message,$context);
    }
    //以上均不满足，自定义
    function notice($message ,array $context = array()){
        return $this->formatMsg($message,$context);
    }

    function info($message ,array $context = array()){
        return $this->formatMsg($message,$context);
    }

    function debug($message ,array $context = array()){
        return $this->formatMsg($message,$context);
    }

    function log($level,$message ,array $context = array()){
        $formatInfo = $this->placeholder($message,$context);
        $formatInfo = $this->replaceFormatMsg($formatInfo,$context);

        $formatInfo = $level . $this->_delimiter .$formatInfo;
        return $formatInfo;
    }

    //===============================================
    function setMsgFormat($info){
        $this->_msgFormat = $info;
    }

    function setDelimiter($str){
        $this->_delimiter = $str;
    }
    function setMsgFormatDatetime($str){
        $this->_msgFormatDatetime = $str;
    }

    function setFilter($str){
        $this->_filter = $str;
    }

    function setDeepTrace($str){
        $this->_deepTrace = $str;
    }

    function setShowScreen($show){
        $this->_showScreen =  $show;
    }

    //===============================以上是对外开放的接口
    function makeRequestId(){
        return uniqid(uniqid(time()));
    }

    function replaceFormatMsg($message){
        $info = "";
        if(!$this->_msgFormat){
            $format = $this->_formatRule;
        }else{
            $format = explode("|",$this->_msgFormat);
//            var_dump($format);exit;
        }

        foreach ($format as $k=>$rule){
            $rule = trim($rule);
            switch ($rule){
//                case 'rid':
//                    $info .= $this->makeRequestId() . $this->_delimiter;
//                    break;
                case 'dt':
                    $mtimestamp = sprintf("%.3f", microtime(true)); // 带毫秒的时间戳
                    $timestamp = floor($mtimestamp); // 时间戳
                    $milliseconds = round(($mtimestamp - $timestamp) * 1000); // 毫秒

                    $info .= date($this->_msgFormatDatetime,time()). " ". $milliseconds .  $this->_delimiter;
                    break;
                case 'pid':
                    $info .= getmypid(). $this->_delimiter;
                    break;

                case "cip":
                    $info .= $this->getClientIp(). $this->_delimiter;
                    break;
                case 'tr':
                    if(!$this->_deepTrace){
                        break;
                    }
                    $trace = debug_backtrace();
                    foreach ($trace as $k =>$v){
                        if($this->_deepTrace && $k > $this->_deepTrace){
                            break;
                        }
                        $info .= ($v['class'] . "-" . $v['function'] ."#");
                    }
                    break;
            }
        }

//        exit;

//        var_dump($info);exit;
        if(!$info){
            throw new \Exception("message format type value is error.");
        }

        $info .= $this->_delimiter . $message ;
        return $info;
    }
    // 获取客户端IP地址
    function getClientIp() {
        static $ip = NULL;
        if ($ip !== NULL) return $ip;
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos =  array_search('unknown',$arr);
            if(false !== $pos) unset($arr[$pos]);
            $ip   =  trim($arr[0]);
        }elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        // IP地址合法验证
        $ip = (false !== ip2long($ip)) ? $ip : '0.0.0.0';
        return $ip;
    }

    function placeholder($message, array $context = array()){
        if(is_object($message)){
            throw new \Exception("message type error: is object.");
        }
        $message = json_encode($message);
        if(!$context){
            return $message;
        }

        foreach ($context as $key => $v) {
//            var_dump($search);exit;
//            var_dump($search);
            $message = str_replace($this->_replaceDelimiterLeft . $key . $this->_replaceDelimiterRight,$v,$message);
        }

        return $message;
    }
}