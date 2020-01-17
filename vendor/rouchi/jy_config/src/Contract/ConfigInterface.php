<?php

namespace Jy\Config\Contract;


interface ConfigInterface
{
    public function get($module = "", $key = "");
    public function set($module = "", $key = "", $value = "");
}
