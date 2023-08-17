<?php

namespace App\Controllers\Rest;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class BaseResourceController extends ResourceController
{

    private const NO_MENU = false;

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
        $this->response->setHeader("Access-Control-Allow-Origin", "http://localhost:3000");

        $method = $_SERVER['REQUEST_METHOD'];
        if ($method == "OPTIONS") {
            header('Access-Control-Allow-Origin: *');
            header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
            header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
            die();
        }
    }

    protected function getData()
    {
        // Try and get the request as JSON, but as an array
        $data = $this->request->getJson(true);
        if ($data === null)
        {
            // Request wasn't JSON - may be regular form-data
            $data = $this->request->getVar();
        }
        return $data;
    }

    protected function checkValidUser()
    {
        if (session()->userID == 0) {
            return $this->respond("You must be a logged in club member to access this function", 401);
        }
        return null;
    }

    protected function checkIsUser($id)
    {
        if (session()->userID != $id) {
            return $this->respond("You don't have permission to do that", 401);
        }
        return null;
    }

    protected function checkAdmin()
    {
        if ( !$this->isAdmin() )
        {
            return $this->respond("You must be a logged in club officer to access this function", 401);
        }
        return null;
    }

    protected function isAdmin()
    {
        return (session()->userID != 0) && (count(session()->roles) > 0);
    }

}

?>