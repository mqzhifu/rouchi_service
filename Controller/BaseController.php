<?php


namespace Rouchi\Controller;


class BaseController extends \Jy\Controller
{
    public function errorResponse($msg, $code = 422)
    {
        return $this->json([], $code, $msg);
    }

    public function successResponse($msg, $data = [], $code = 200)
    {
        $data = is_object($data) && method_exists($data, 'toArray') ? $data->toArray() : (array) $data;
        return $this->json($data, $code, $msg);
    }
}