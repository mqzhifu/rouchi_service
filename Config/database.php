<?php

return [

    'connections' => [
        'mysql' => [
            'read' => [
                'host' => 'rm-uf657s667whz56ex2155.mysql.rds.aliyuncs.com',
                'username' => 'rouchi_boss_dev',
                'password' => 'Rouchi@123',
                'port' => '3306'
            ],
            'write' => [
                'host' => 'rm-uf657s667whz56ex2155.mysql.rds.aliyuncs.com',
                'username' => 'rouchi_boss_dev',
                'password' => 'Rouchi@123',
                'port' => '3306'
            ],
            'driver'    => 'mysql',
            'database'  => 'rouchi_boss_dev',
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
            'strict'    => false,
        ],


    ],

    'redis' => [
    'default' => [
        'host'     => '127.0.0.1',
        'password' => '',
        'port'     => '6379',
        'database' => 0,
    ],
],

];
