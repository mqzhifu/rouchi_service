<?php
namespace Jy\Common\Valid\Valid;

use Jy\Common\Valid\Contract\FilterInterface;
use Jy\Common\Valid\Contract\ValidInterface;
use Jy\Common\Valid\Valid\Filter;


class Valid implements ValidInterface,FilterInterface {
    public $_scalarType = array('int','string','float','bool');
    public $_debug = 1;//1只做跟踪，统一返回错误信息，2 包含1的同时还输出到屏幕上
    public $_traceInfo = "";
    private $_filter = null;

    function __construct(){
        $this->_filter = new Filter();
    }

    function out($info){
        if($this->_debug ){
            $info .= "\n";
            $this->_traceInfo .= $info;
            if($this->_debug == 2){
                echo $info;
            }
        }
    }
    //验证 数据值 长度
    function matchLength($value, $rule){
        $this->_filter->matchLength($value,$rule);
    }
    //验证 数据 格式及类型是否正确
    function match($data,$rules){
//        $this->throwException("aaa");
//        try{
        if(!$data){
            $this->throwException("para:<data> is null.(in func:valid)");
        }

        if(!$rules){
            $this->throwException("para:<rules> is null.(in func:valid)");
        }
//            $rules = json_decode($rules,true);
//            if(!$rules){
//                $this->throwException("json 格式 错误");
//            }
        $this->arrayMakeRequireFlag($rules);
        $this->explodeMapKey();
//            var_dump($this->map);exit;
        $this->recursion($data,$rules,1);
//        }catch (Exception $e){
//            $msg = $this->_traceInfo ."|".$e->getMessage();
//            var_dump($e->getMessage());exit;
//        }
//        return true;
    }

    function explodeMapKey(){
//        if(!$this->map){
//            return false;
//        }
////        var_dump($this->map);
//        $newMap = null;
//        foreach ($this->map as $k=>$v) {
//            $keys = explode("|",$k);
//            foreach ($keys as $k3=>$v3) {
//                $newMap[$v3] = $v;
//            }
//        }
//        $this->map = $newMap;
////        var_dump($newMap);exit;
    }

    //统一抛异常
    function throwException($info){
        $info = $this->_traceInfo . "\n".$info;
//        var_dump($info);exit;
        throw new \Exception($info);
    }
    //数组中的KEY是否定义，防止出现NOTICE
    function  arrKeyIssetAndExist($arr,$key){
        if(isset($arr[$key]) && $arr[$key]){
            return true;
        }
        return false;
    }
    function testBreakExit($k){
        if((string)$k == 'dataArrOneStr'){
            exit("000-testBreakExit");
        }
    }
    //给数组打上:require标记
    function arrayMakeRequireFlag($rules){
        if(!is_array($rules)){
            $this->testBreakExit("只有数组才行");
        }
        $this->arrayMakeRequireFlagRecursion($rules);
    }
    public $map = null;
    function arrayMakeRequireFlagRecursion($rules,$parents = ""  ){
//        $this->out(" new rules:".json_encode($rules). " parents:$parents ");
        foreach ($rules as $k=>$rule) {
//            $this->out(" arrayMakeRequireFlagRecursion top k:$k .rule:".json_encode($rule));
            if(!is_array($rule)){
//                $this->out(" is scalar");
                if(!$parents){//过滤掉所有非数组的 根元素
                    continue;
                }
                $explodeRule = explode("|",$rule);
                foreach ($explodeRule as $k2=>$oneRule) {
                    if($oneRule == 'require'){
                        $this->map[$parents] = 1;
                        return 1;
                    }
                }
            }else{
//                $this->out(" is array");
                if(!$parents){
                    $info = (string)$k;
                }else{
                    $info = (string)$parents."|".(string)$k;
                }
                $this->arrayMakeRequireFlagRecursion($rule,$info);
            }
        }
    }
    //递归 验证
    function  recursion($data,$rules,$layer = 0,$parents = ""){
//        $this->out("new recursion .  parents:$parents layer:".$layer . " rules:".json_encode($rules));
        foreach ($rules as $k=>$v) {
//            if((string)$k == 'dataArrTwoStr'){
//                exit("000");
//            }
            $this->out( " loop key:".$k . " ,v:".json_encode($v));
            if(!is_array($v)){
                $explodeRule = explode("|",$v);
                if(!$explodeRule || !is_array($explodeRule)){
                    $this->throwException("explode rule is err.");
                }
                //把require提到最前
                $finalExplodeRule = $this->moveRuleRequireFirst($explodeRule);
                $this->out("    finalExplodeRule:".json_encode($finalExplodeRule));
                foreach ($finalExplodeRule as $k2=>$rule) {
                    $info = "    sub loop k:$k2 .v:";
                    if(isset($data[$k])){
                        $info .= json_encode($data[$k]);
                    }
                    $this->out($info);
                    //$rule验证，交给filter类
                    $oneRule = trim($rule);
                    //非require必填项，是可以为空的
                    if( ( !isset($data[$k])|| !$data[$k] ) && $oneRule != 'bool' ){
                        if($oneRule != 'require'){
                            $this->out("   return ,value is null");
                            continue;
                        }else{
                            $this->out(" not isset");
                            $this->throwException($k ."-".$oneRule . " ". $this->getMessage($oneRule));
                        }
                    }
                    $preg = $this->_filter->match($data[$k],$oneRule);
                    $this->out("     filter rs:$preg");
                    if(!$preg){
                        $this->throwException($this->getMessage($oneRule));
                    }
                }
            }else{
                $this->out(" in array");
                $layer++;
                if(isset($v[0]) && is_string($v[0])){//1组数字KEY
                    $this->checkArrRequire($parents,$k,$data);
                    if(!isset($data[$k]) || !$data[$k]){
                        $this->out("   return ,value is null");
                        continue;
                    }

                    foreach ($data[$k] as $k4=>$v4) {
                        $this->recursion(array($v4),$v,$layer,$k);
                    }

                }elseif(isset($v[0]) && is_array($v[0])){//2维+
                    $this->checkArrRequire($parents,$k,$data);
                    foreach ($data[$k] as $k4=>$v4) {
                        $this->recursion(array($v4),$v,$layer,$k);
                    }
                }else{//一组字符串KEY
                    $this->checkArrRequire($parents,$k,$data);
                    $this->recursion( $data[$k],$v,$layer,$k);
                }
            }
        }
    }

    function moveRuleRequireFirst($explodeRule){
        $finalExplodeRule = [];
        //把require提到最前
        if($explodeRule[0] == 'require'){
            return $explodeRule;
        }
        $f = 0;
        foreach ($explodeRule as $k=>$v) {
            if($v == 'require'){
                $f = 1;
                break;
            }
        }

        if(!$f)
            return $explodeRule;

        $finalExplodeRule = array("require");
        foreach ($explodeRule as $k9=>$v9) {
            if((string)$v9 != 'require'){
                $finalExplodeRule[] = $v9;
            }
        }
        return $finalExplodeRule;
    }

    function checkArrRequire($parents,$k,$data){
        $rs = $this->getMapValue($parents,$k);
        $this->out("getMapValue:$rs");
        if($rs){
            if(!isset($data[$k]) || !$data[$k]){
                $this->throwException("$k,数组为空");
            }
        }
    }

    function getMapValue($parents,$k){
        if(!$parents){
            $arrFlagKey = $k;
        }else{
            $arrFlagKey = $parents."|".$k;
        }
        if(!$this->map){
            return 0;
        }
        foreach ($this->map as $k=>$v) {
            if(strpos((string)$k,(string)$arrFlagKey) !== false){
                $str1 = explode("|",$k);
                $str2 = explode("|",$arrFlagKey);
                $f = 1;
                foreach ($str2 as $k2=>$v2) {
                    if($v2 != $str1[$k2]){
                        $f = 0;
                        break;
                    }
                }
//                $this->out("f:$f ,v:$v");
//                var_dump($str2);var_dump($str1);
                if($f){
                    return $v;
                }
            }
        }
        return 0;
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

    function getMessage($rule){
        return $this->_filter->getMessage($rule);
    }

    function setDebug($debug){
        $this->_debug = $debug;
        $this->_filter->setDebug($debug);
    }
}
