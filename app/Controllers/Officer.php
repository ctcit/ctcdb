<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;


// This controller contains functions available to club officers logged into the
// joomla website.
class Officer extends BaseController
{

    /**
     * Constructor.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param LoggerInterface   $logger
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);

        $roles = session()->roles;
        if ($roles == NULL || count($roles) == 0) {
            die('You must be a club officer logged in to the website to use this function!!!');
        }
    }

    public function index()
    {
        return $this->loadPage('home', 'CTC Database Home');
    }

}
