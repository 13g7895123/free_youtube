<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;

class BaseController extends \CodeIgniter\Controller
{
    use ResponseTrait;

    protected $helpers = ['response'];

    protected $request;
    protected $logger;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        // 載入回應輔助函數
        helper('response');
    }
}
