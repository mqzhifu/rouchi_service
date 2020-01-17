<?php
namespace Jy\Log\Log;

use Jy\Log\Contract\Main;

class File extends Main {

    private $_totalRecord = 0;//将所有日志 统一记录到某一个文件中

    private $_projectName = "";//项目名，用于 分类文件夹
    private $_module = "";//模块名，属于项目的子集，用于分类文件夹

    private $_wrap = "\r\n";//换行符

    private $_hashType = "day";//year month day
    private $_hashTypeDesc = array("year",'month','day','hour');
    private $_filePrefix = "";//日记文件 前缀名
    private $_ext = ".txt";//文件扩展名
    private $_path = "";//日志文件在于的 基位置

    private $_writePath  = "";//类内部使用，如果有 分类文件夹，临时写入
    private $_fd = null;//类内部使用，缓存文件句柄，后期优化
    private $_level = "";//类内部使用

//    private static $instance = null;
//    public static function getInstance(){
//        if(self::$instance){
//            return self::$instance;
//        }
//        $self =  new self();
//        self::$instance = $self;
//        return self::$instance;
//    }

//    function __call($name, $arguments)
//    {
//        $this->_level = "debug";
//
//        $this->initPath("debug");
//        $info =  parent::debug($message,$context);
//        $this->flush($info);
//    }

    function __construct(){
        parent::__construct();
    }

    function init($k,$v){
        if(isset($this->$k)){
            $this->$k = $v;
            return $this;
        }
        throw new \Exception("set private variable err.");
    }

    //统计不可调试日志
    function emergency($message ,array $context = array()){
        $this->_level = "emergency";
        $info =  parent::emergency($message,$context);
        $this->flush($info);
    }
    //警报日志
    function alert($message ,array $context = array()){
        $this->_level = "alert";
        $info =  parent::emergency($message,$context);
        $this->flush($info);
    }
    //危险日志
    function critical($message ,array $context = array()){
        $this->_level = "critical";
        $info =  parent::emergency($message,$context);
        $this->flush($info);
    }
    //错误日志
    function error($message ,array $context = array()){
        $this->_level = "error";
        $info =  parent::emergency($message,$context);
        $this->flush($info);
    }
//    //警告日志
    function warning($message ,array $context = array()){
        $this->_level = "warning";
        $info =  parent::emergency($message,$context);
        $this->flush($info);
    }
    //提醒日志
    function notice($message ,array $context = array()){
        $this->_level = "notice";
        $info =  parent::emergency($message,$context);
        $this->flush($info);
    }
//  //普通日志
    function info($message ,array $context = array()){
        $this->_level = "info";
        $info =  parent::emergency($message,$context);
        $this->flush($info);
    }
    //调试日志
    function debug($message ,array $context = array()){
        $this->_level = "debug";
        $info =  parent::emergency($message,$context);
        $this->flush($info);
    }

    function log($level,$message ,array $context = array()){
        $this->_level = "log";
        $info =  parent::log($level,$message,$context);
        $this->flush($info);
    }
    //=============================================
    function setHashType($type){
        if(!in_array($type,$this->_hashTypeDesc)){
            throw new \Exception("setHashType failed, type value is error.");
        }
        $this->_hashType = $type;
    }


    //====================以上可对外开放======================

    //初始化路径
    function initPath($category){
        $this->checkBasePath();

        $this->_writePath = $this->_path;
        if($this->_projectName){
            $this->_writePath .=  "/".$this->_projectName;
            $this->checkPathAndMkdir();
        }

        if($this->_module){
            $this->_writePath .=  "/".$this->_module;
            $this->checkPathAndMkdir();
        }

        $this->_writePath .=  "/".$category;
        $this->checkPathAndMkdir();
    }
    //在基目录下，新建一个文件，记录所有类型的日志，可以看成是一个：总日志文件
    function totalRecord($info){
        if(!$this->_totalRecord)
            return 0;

        $pre = "";
        if($this->_projectName){
            $pre .= "[{$this->_projectName}]";
        }

        if($this->_module){
            $pre .= "[{$this->_module}]";
        }

        $pre .= "[{$this->_level}]";

        $file = $this->_filePrefix."total".$this->_ext;
        $info = $pre   . $info . $this->_wrap;
        $this->writeFile($this->_path  ."/" .$file,$info);
    }

    function writeFile($pathFile,$info){
        if(!isset($this->_fd[md5($pathFile)])){
            $this->_fd[md5($pathFile)] = fopen($pathFile,"a+");
        }
        fwrite(  $this->_fd[md5($pathFile)],$info);

//        $fd = fopen($pathFile,"a+");
//        fwrite($fd,$info);
//        fclose($fd);
    }
    //持久化到文件中
    function flush($info){
        $this->initPath($this->_level);
        $this->totalRecord($info);

        $ext= $this->_ext;
        if ($this->_hashType){
            if($this->_hashType == 'year'){
                $filePath = $this->_writePath . "/" . $this->_filePrefix . date("Y").$ext;
            }elseif($this->_hashType == 'month'){
                $filePath = $this->_writePath . "/" . $this->_filePrefix .  date("Y"). date("m").$ext;
            }elseif($this->_hashType == 'day'){
                $filePath = $this->_writePath . "/"  .$this->_filePrefix.  date("Y"). date("m") . date("d").$ext;
            }elseif($this->_hashType == 'hour'){
                $filePath = $this->_writePath . "/" .$this->_filePrefix . date("Y"). date("m"). date("d") . date("H").$ext;
            }else{
                exit("-1");
            }

        }else{
            $filePath = $this->_writePath . "/" .$this->_filePrefix .$ext;
        }

        $info = $info . $this->_wrap;

        $this->writeFile($filePath,$info);
    }
    //检查设置路径正确否
    function checkBasePath(){
        if(!is_dir($this->_path)){
            throw new \Exception("base path is not dir:$this->_path");
        }
    }
    //检查路是否存在 ，不存在 则尝试创建
    function checkPathAndMkdir(){
        if(!is_dir($this->_writePath)){
            $rs = mkdir($this->_writePath);
            if(!$rs){
                throw new \Exception("create dir failed,path:$this->_writePath");
            }
        }
    }
    //判断目录是否有写权限
    function fileModeInfo($file_path){
        /* 如果不存在，则不可读、不可写、不可改 */
        if (!file_exists($file_path))
        {
            return false;
        }
        $mark = 0;
        if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')
        {
            /* 测试文件 */
            $test_file = $file_path . '/cf_test.txt';
            /* 如果是目录 */
            if (is_dir($file_path))
            {
                /* 检查目录是否可读 */
                $dir = @opendir($file_path);
                if ($dir === false)
                {
                    return $mark; //如果目录打开失败，直接返回目录不可修改、不可写、不可读
                }
                if (@readdir($dir) !== false)
                {
                    $mark ^= 1; //目录可读 001，目录不可读 000
                }
                @closedir($dir);
                /* 检查目录是否可写 */
                $fp = @fopen($test_file, 'wb');
                if ($fp === false)
                {
                    return $mark; //如果目录中的文件创建失败，返回不可写。
                }
                if (@fwrite($fp, 'directory access testing.') !== false)
                {
                    $mark ^= 2; //目录可写可读011，目录可写不可读 010
                }

                @fclose($fp);
                @unlink($test_file);
                /* 检查目录是否可修改 */
                $fp = @fopen($test_file, 'ab+');
                if ($fp === false)
                {
                    return $mark;
                }
                if (@fwrite($fp, "modify test.\r\n") !== false)
                {
                    $mark ^= 4;
                }
                @fclose($fp);
                /* 检查目录下是否有执行rename()函数的权限 */
                if (@rename($test_file, $test_file) !== false)
                {
                    $mark ^= 8;
                }
                @unlink($test_file);
            }
            /* 如果是文件 */
            elseif (is_file($file_path))
            {
                /* 以读方式打开 */
                $fp = @fopen($file_path, 'rb');
                if ($fp)
                {
                    $mark ^= 1; //可读 001
                }
                @fclose($fp);
                /* 试着修改文件 */
                $fp = @fopen($file_path, 'ab+');
                if ($fp && @fwrite($fp, '') !== false)
                {
                    $mark ^= 6; //可修改可写可读 111，不可修改可写可读011...
                }
                @fclose($fp);
                /* 检查目录下是否有执行rename()函数的权限 */
                if (@rename($test_file, $test_file) !== false)
                {
                    $mark ^= 8;
                }
            }
        }
        else
        {
            if (@is_readable($file_path))
            {
                $mark ^= 1;
            }
            if (@is_writable($file_path))
            {
                $mark ^= 14;
            }
        }
        return $mark;
    }

}