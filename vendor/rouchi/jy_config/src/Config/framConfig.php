<?php


return [
    'event' => [
        'JY.INIT.AFTER' => [
            [
                'className' => \Jy\Listener\InitAfter::class,
                'priority' => 0,
            ],
        ],
        'JY.INIT.BEFORE' => [
            [
                'className' => \Jy\Listener\InitBefore::class,
                'priority' => 0,
            ],

        ],
        'JY.SERVER.START' => [
            //..
        ],
        'JY.REQUEST.END' => [
            [
                'className' => \Jy\Listener\RequestEnd::class,
                'priority' => 0,
            ],
        ],
        'JY.ACTION.BEFORE' => [
            [
                'className' => \Jy\Listener\ActionBefore::class,
                'priority' => 0,
            ],
            [
                'className' => \Jy\Listener\Middleware::class,
                'priority' => 0,
            ],
        ],
        'JY.EXCEPTION' => [
            //..
        ],

        // trace
        'JY.REQUEST.SEND' => [
            //..
        ],
        'JY.REQUEST.RECEIVE' => [
            //..
        ],
        'JY.CLIENT.SEND' => [
            //..
        ],
        'JY.CLIENT.RECEIVE' => [
            //..
        ],
    ],
    'middleware' => [
        'token' => [
            'className' => \Jy\Middleware\TokenMiddleware::class,
        ],
    ],
];
