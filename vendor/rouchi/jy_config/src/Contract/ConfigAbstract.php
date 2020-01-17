<?php

namespace Jy\Config\Contract;

use Jy\Config\Contract\ConfigInterface;


abstract class ConfigAbstract implements ConfigInterface
{
    abstract public function get($module = "", $key = "");
    abstract public function set($module = "", $key = "", $value = "");
}