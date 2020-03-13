<?php

namespace Rouchi\Controller\V1;

use Jy\Controller;

use Jy\Facade\Config;
use Jy\Facade\DB;
use Jy\Facade\Log;
use Jy\Facade\Redis;
use Jy\Request;
use Jy\Common\Valid\Facades\Valid;

use Rouchi\Product\OrderBean;
use Rouchi\Product\PaymentBean;
use Rouchi\Product\SmsBean;
use Rouchi\Product\UserBean;


include_once './../vendor/rouchi/jy_common/src/MsgQueue/Test/testUnitClient.php';

class Index extends Controller
{
    /**

     * @valid {"a":"int"}

     */
    public function index(Valid $valid , Request $request)
    {

        $SmsBean = new SmsBean();
        $OrderBean = new OrderBean();
        $PaymentBean = new PaymentBean();
        $UserBean = new UserBean();


        $SmsBean->setDebug(3);

        $SmsBean->_msg = "aaa";
        $SmsBean->send();
        exit;

        $OrderBean->_id = 1;
        $OrderBean->_channel = 'baidu';
        $OrderBean->send();
        $OrderBean->sendDelay(10000);


//        $PaymentBean->_price = '100';
//        $PaymentBean->send();

//        $UserBean->_id = 2;
//        $UserBean->send();






//        throw new NackExcepiton(10,1000);









//        echo 444;
//        var_dump($request);
//        $rule = array(
//            "a"=>'int|require');
//        echo json_encode($rule);
//
//        Log::buffFlushFile();
        $redisConf = Config::get('redis', 'redis');

//
//        $feilds = ['password', 'name', 'mobile'];
//
//        $data = ["password" => 'pwd123', "name" => 'name12234', "mobile" => 'mb12345'];
//        $data2 = [
//            ["password" => 'pwd123', "name" => 'name12234', "mobile" => 'mb12345'],
//            ["password" => 'pwd456', "name" => 'name789', "mobile" => 'mb678'],
//        ];
//        //DB::insert('user', $feilds, $data);
//        //DB::getInstance()->insert('user', $feilds, $data);
//        //DB::getInstance()->multiInsert('user', $feilds, $data2);
//        //DB::multiInsert('user', $feilds, $data2);
//        $sql = "select * from user order by id desc limit 10";
//        //$ret = DB::findAll($sql, []);
//        $ret = DB::findOne($sql, []);
//        //$ret = Redis::set('user', "xiaoming");
//        //print_r(['ret' => $ret]);
//        return $this->json(['ret' => $ret]);

    }
    //
}

