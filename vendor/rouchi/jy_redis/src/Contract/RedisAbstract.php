<?php

namespace Jy\Redis\Contract;

use Jy\Redis\Contract\ConfigInterface;


abstract class RedisAbstract implements RedisInterface
{
    abstract public function connect();
}
