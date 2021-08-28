<?php

namespace App\Controllers\Rest;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class User extends BaseResourceController
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
    }

    public function index()
    {
        return $this->show();
    }

    // Return the data relating to the currently-logged in user.
    // If no user is logged in this will be just {id: 0}. Otherwise it
    // will be an object with id, login, name and a list of official roles for that
    // user.
    public function show($id = null)
    {
        // PENDING
        $data = array('id'=> session()->userID);
        if ($data['id'] !== 0) {
            $data['login'] = session()->login;
            $data['name'] = session()->name;
            $data['roles'] = session()->roles;
        }
        return $this->respond($data);
    }

    public function create()
    {
        // PENDING
        return $this->respond([]);
    }

    public function update($id = null)
    {
        // PENDING
        return $this->respond([]);
    }

    public function delete($id = null)
    {
        // PENDING
        return $this->respond([]);
    }
}

?>