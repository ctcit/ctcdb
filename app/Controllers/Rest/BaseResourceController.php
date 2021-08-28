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

    protected function checkCanEdit($tripReportID)
    {
        // Check that the current user can edit the given trip report
        $response = $this->checkValidUser();
        if ($response === null) {
            $row = $this->tripReportModel->getById($tripReportID);
            if (count(session()->roles) === 0 && session()->userID !== $row->uploader_id) {
                $response = $this->respond("You do not have permission to edit this item", 403);
            }
        }
        return $response;
    }

}

?>