<?php
/**
 * @user: ligongxiang (ligongxiang@rouchi.com)
 * @date : 2020/1/9
 * @version : 1.0
 * @file : Curl.php
 * @desc :
 */

namespace Jy\Common\NetTools;


class Curl implements InterfaceNetTool
{
    const MAX_REDIR_TIMES = 5;
    const USER_AGENT = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; (R1 1.5))';
    const CONNECT_TIMEOUT = 1;
    const TIMEOUT = 2;

    //句柄
    public $_ch = null;

    /*
     * 如传入参数中有同名参数将覆盖默认设置参数。
     *
     * 参数常量与值可参考官方文档：
     * https://www.php.net/manual/zh/function.curl-setopt.php
     * */
    public function init($dest,$data = array(),$option = array())
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $dest);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);                    //跟随Location跳转
        curl_setopt($ch, CURLOPT_MAXREDIRS, self::MAX_REDIR_TIMES);     //最大跳转次数
        curl_setopt($ch, CURLOPT_USERAGENT, self::USER_AGENT);          //代理
        curl_setopt($ch, CURLOPT_POST, 1);                              //默认post
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);                         //post参数
        curl_setopt($ch,CURLOPT_HTTPHEADER,array(
            'Content-type: application/json'
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);                    //接收返回数据
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::CONNECT_TIMEOUT);//连接超时时间
        curl_setopt($ch, CURLOPT_TIMEOUT, self::TIMEOUT);               //超时时间
        if (!empty($option))
            curl_setopt_array($ch,$option);

        $this->_ch = $ch;
        return $this;
    }

    public function ping()
    {
        return $this;
    }

    public function exec(): string
    {
        return curl_exec($this->_ch);
    }

    public function recycle()
    {
        curl_close($this->_ch);
        return;
    }
}