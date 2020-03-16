<?php

namespace Jy\Config\Contract;


interface ConfigInterface
{
    public function get($module = "", $key = "", $default = null);
    public function set($module = "", $key = "", $value = "");
}
