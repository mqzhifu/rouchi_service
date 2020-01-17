<?php

class ValidData{
    public $_type = array('int','string','float','bool','object','class','resource','array');
    PUBLIC $_scalarType = array('int','string','float','bool');
    function isScalarType($info){
        foreach ($this->_scalarType as $k=>$v) {
            if($v == $info){
                return true;
            }
        }
        return false;
    }

    function valid($data,$rules){
        if(!$data){
            exit(-1);
        }

        if(!$rules){
            exit(-2);
        }

        $this->recursion($data,$rules);
    }

    function recursion($data,$rules){
        if(!$data || !$rules ){
            return null;
        }
//        $scalarType = array('int','string','float','bool');
        foreach ($rules as $k=>$rule) {
            echo $k ."\r\n";
            if($rule['must']){
                if(!$data[$k]){
                    exit("必填，key不存在1: $k ");
                }
            }



            if(!in_array($rule['type'],$this->_type)){
                exit(" type is err.".$rule['type']);
            }
            if( $this->isScalarType($rule['type']) ){
                $func = "is_".$rule['type'];
                if(!$func($data[$k])){
                    exit("类型错误2 ");
                }
            }else{
                echo "in complex type:".$rule['type']."\r\n";
                if($rule['type'] == 'array'){
                    echo "exec in array case \r\n" . "config type:". $rule['config']['key_type']."\r\n";
                    if($rule['config']['must']){//必填
                        if(!isset($data[$k] ) || !$data[$k] ){
                            exit("类型错误3 ");
                        }

                        if(!is_array($data[$k])){
                            exit("类型错误5 ");
                        }
                    }

                    if(!$data[$k]){
                        return -1;
                    }

                    if(!is_array($data[$k])){
                        exit("类型错误6 ");
                    }

                    if($rule['config']['key_type'] == 'number'){//数字下标
                        //数组都是<标量>简单类型，直接做类型判断
                        if(  !is_array($rule['config']['value_type']) && $this->isScalarType($rule['config']['value_type'])    ){
                            //这里少了一个 must 判断，回头再补
                            $func = "is_".$rule['type'];
                            foreach ($data[$k] as $k2=>$v2) {
                                if(!$func($data[$k])){
                                    exit("类型错误4 ");
                                }
                            }
                        }else{
                            foreach ($data[$k] as $k3=>$v3) {

                            }

                            exit(-5);
                        }
                    }
                    elseif($rule['config']['key_type'] == 'string'){//字符串下标
                        foreach ($rule['config']['value_type'] as $hashKey=>$hashConfig) {
                            if($hashConfig['must']){
                                if(! $data[$k][$hashKey]){
                                    exit("必填，key不存在5: $hashKey ");
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}




$rule = array(
    'cnt'=>    array('type'=>'int','must'=>1),
    'name'=>    array('type'=>'string','must'=>1),
    'price'=>    array('type'=>'float','must'=>1),
    'isNew'=>    array('type'=>'bool','must'=>0),
    //以上均是标量类型
//    'myOb'=>    array('type'=>'object','must'=>1),
//    'Log'=>    array('type'=>'class','must'=>1,'classInstantName'=>'Log'),
//    'stream'=>    array('type'=>'resource','must'=>1),
    //1维数字下标数组，是最简单的一种
    'dataArrOneNum'=>    array('type'=>'array','must'=>1,
        'config'=>array("key_type"=>'number','must'=>1,'value_type'=>'int')
    ),
    //1维字符串下标数组
    'dataArrOneStr'=>    array('type'=>'array','must'=>1,
        'config'=>array("key_type"=>'string','value_type'=>array(
            'title'=> array('type'=>'string','must'=>1),
            'id'=> array('type'=>'int','must'=>1),),
            ),
    ),
    //二维数组，都是以数字为下标
    'dataArrTwoNum'=>    array('type'=>'array','must'=>1,
        'config'=>array("key_type"=>'number','must'=>1,'value_type'=>array(
            array('type'=>'array','must'=>1,'config'=>array("key_type"=>'number','must'=>1,'value_type'=>'int')),
            )
        ),
    ),
    //二维数组，都是以字符串为下标
//    'dataArrTwoStr'=>    array('type'=>'array','must'=>1,"key_is_str"=>1,'deep'=>1,'value_type'=>array(
//            'title'=> array('type'=>'string','must'=>1),
//            'id'=> array('type'=>'int','must'=>1),
//        ),
//    ),


);

class MyOb{

}

$myOb = new MyOb();

$data = array(
    'cnt'=>1,'name'=>'aaaaa','price'=>1.02,'isNew'=>false,'myOb'=>$myOb,'Log'=>$myOb,'stream'=>2222,
    'dataArrOneNum'=>array(1,6,9,10),
    'dataTwoNum'=>array(
        array(1,6,9,10),
        array(1,6,9,10),
    ),
);

$class =new ValidData();
$rs = $class->valid($data,$rule);
var_dump($rs);exit;