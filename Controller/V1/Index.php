<?php

namespace Rouchi\Controller\V1;


use Rouchi\Controller\BaseController;


class Index extends BaseController
{
    public function index()
    {
        return $this->successResponse('success');
    }
}

