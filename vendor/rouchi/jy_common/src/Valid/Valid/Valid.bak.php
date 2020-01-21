<?php
namespace Jy\Common\Valid\Valid;

use Jy\Common\Valid\Contract\FilterInterface;
use Jy\Common\Valid\Contract\ValidInterface;
use Jy\Common\Valid\Valid\Filter;


class Valid implements ValidInterface,FilterInterface {
    public $_scalarType = array('int','string','float','bool');
    public $_debug = 2;//1只做跟踪，统一返回错误信息，2 包含1的同时还输出到屏幕上
    public $_traceInfo = "";
    private $_filter = null;

    function __construct(){
        $this->_filter = new Filter();
    }

    function setMessage($message){
        $this->_filter->setMessage($message);
    }

    function setDelimiter($delimiter){
        $this->_filter->setDelimiter($delimiter);
    }

    function setRangeDelimit($rangeDelimiter){
        $this->_filter->setRangeDelimit($rangeDelimiter);
    }

    function getMessage($rule)
    {
        // TODO: Implement getMessage() method.
    }

    function setDebug($debug){
        $this->_debug = $debug;
        $this->_filter->setDebug($debug);
    }

    function out($info){
        if($this->_debug ){
            $info .= "\n";
            $this->_traceInfo .= $info;
            if($this->_debug == 2)
                echo $info;
        }
    }

    function matchLength($value, $rule){
        $this->_filter->matchLength($value,$rule);
    }

    //入口文件
    function match($data,$rules){
        try{
            if(!$data){
                $this->throwException("para:<data> is null.(in func:valid)");
            }

            if(!$rules){
                $this->throwException("para:<rules> is null.(in func:valid)");
            }

            $rules = json_decode($rules,true);
            if(!$rules){
                $this->throwException("json 格式 错误");
            }

            $this->recursion($data,$rules,1);
        }catch (Exception $e){
            var_dump($e->getMessage());exit;
            $msg = $this->_traceInfo ."|".$e->getMessage();
            var_dump($msg);exit;
        }
        return true;
    }

    //统一抛异常
    function throwException($info){
        throw new \Exception($info);
    }
    //数组中的KEY是否定义，防止出现NOTICE
    function  arrKeyIssetAndExist($arr,$key){
        if(isset($arr[$key]) && $arr[$key]){
            return true;
        }
        return false;
    }
    //递归 验证
    function recursion($data,$rules,$layer){
        if(!$data){
            $this->throwException("para:<data> is null.(in func:recursion)");
        }

        if(!$rules){
            $this->throwException("para:<rules> is null.(in func:recursion)");
        }

//        $this->out("layer:".$layer . " , data:".json_encode($data) . " rules:".json_encode($rules));
        $this->out("layer:".$layer . " rules:".json_encode($rules));
        foreach ($rules as $k=>$v) {
            $this->out( "top loop key:".$k . " ,v:".json_encode($v));
            if(!is_array($v)){
                $this->throwException("每个rule必须为数组类型");
            }

            foreach ($v as $key=>$oneRule) {
                $this->out("second loop k:$key , rule : ".json_encode($oneRule));
                if((string)$key == "hash_config"){
                    continue;
                }

                if(is_array($oneRule) && $key == 'array'){
                    $this->out("  in array case");
                    $arrKeyType = "";
                    foreach ($oneRule as $k2=>$v2) {
                        if((string)$v2 == 'key_number' ){
                            $arrKeyType = "key_number";
                            break;
                        }elseif((string)$v2 == "key_hash"){
                            $arrKeyType = "key_hash";
                            break;
                        }
                    }

                    if(!$arrKeyType){
                        $this->throwException("array类型必须包含key_number|key_hash 子元素");
                    }
                    if($arrKeyType == 'key_number' ){
                        $this->out("   array key type : number");
                        foreach ($oneRule as $k2=>$v2) {
                            if( (string)$v2 == $arrKeyType){
                                unset($oneRule[$k2]);
                                break;
                            }
                        }
//                        var_dump($oneRule);exit;
                        $this->out("    value_type is :recursion start");
                        $this->recursion($data[$k],array($oneRule),$layer + 1);
                        $this->out("    value_type is :recursion end");
                    }else{
                        $this->out("   array key type : hash");
                        $this->recursion($data[$k],$oneRule['hash_config'],$layer + 1);
                        $this->out("    value_type is :recursion end");
                    }
                    continue;
                }
                $oneRule = trim($oneRule);
//                $value = "";
//                $this->out("value:".json_encode($oneRule));
                if( ( !isset($data[$k])|| !$data[$k] ) && $oneRule != 'bool' ){
                    if($oneRule != 'require'){
                        $this->out(" return ,value is null");
                        continue;
                    }
                }

                $value = $data[$k];
                $this->out("value: ".json_encode($value). ", rule:  $oneRule");

                $preg = $this->_filter->match($value,$oneRule);
                $this->out("filter rs:$preg");
                if(!$preg){
                    $this->throwException($this->_filter->getMessage($oneRule));
                }
            }

        }
    }
    //是否为<标量>类型
    function isScalarType($info){
        foreach ($this->_scalarType as $k=>$v) {
            if($v == $info){
                return true;
            }
        }
        return false;
    }
}
