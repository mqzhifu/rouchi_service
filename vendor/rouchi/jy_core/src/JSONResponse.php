<?php
namespace Jy;

class JSONResponse {

    private $data;

    /**
     * 构造函数
     * @param $data
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    function __toString()
    {
        return json_encode($this->data, JSON_UNESCAPED_UNICODE) ?: json_last_error_msg();
    }
}
