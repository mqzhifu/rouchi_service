<?php

namespace Rouchi\Controller\V1;

use Jy\Controller;

use Jy\Facade\Config;
use Jy\Facade\DB;
use Jy\Facade\Redis;


class Index extends Controller
{

    public function index()
    {

        $data = Config::get('redis', 'redis');

        $feilds = ['password', 'name', 'mobile'];

        $data = ["password" => 'pwd123', "name" => 'name12234', "mobile" => 'mb12345'];
        $data2 = [
            ["password" => 'pwd123', "name" => 'name12234', "mobile" => 'mb12345'],
            ["password" => 'pwd456', "name" => 'name789', "mobile" => 'mb678'],
        ];
        //DB::insert('user', $feilds, $data);
        //DB::getInstance()->insert('user', $feilds, $data);
        //DB::getInstance()->multiInsert('user', $feilds, $data2);
        //DB::multiInsert('user', $feilds, $data2);
        $sql = "select * from user order by id desc limit 10";
        //$ret = DB::findAll($sql, []);
        $ret = DB::findOne($sql, []);
        //$ret = Redis::set('user', "xiaoming");
        //print_r(['ret' => $ret]);
        return $this->json(['ret' => $ret]);

    }
}

