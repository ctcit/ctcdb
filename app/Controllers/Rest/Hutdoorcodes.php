<?php

namespace App\Controllers\Rest;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

use App\Models\HutBooking;
use App\Models\HutBookingsModel;

class HutDoorCodes extends BaseResourceController
{
    public $model;

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

        $this->model = model('HutDoorCodesModel');
    }

    public function index()
    {
        if (!$this->isAdmin()) {
            return $invalidResponse;
        }
        $current = [ $this->model->current(), ];
        $future = $this->model->future();
        return $this->respond(array_merge($current, $future));
    }

    public function create()
    {
        if (!$this->isAdmin()) {
            return $invalidResponse;
        }
        $data = $this->getData();
        $entry = new \App\Models\HutDoorCode($data);
        $result = $this->model->tryAdd($entry, 2218);
        if ($result['result'] != "OK")
        {
            return $this->respond(["status"=>"failed", "reason"=>$result['result']], 400);
        }
        return $this->respond($result['codeEntry']);
    }

    public function update($id = null)
    {
        if (!$this->isAdmin()) {
            return $invalidResponse;
        }
        $data = $this->getData();
        $existingEntry = $this->model->find($id);
        if ($existinEntry) {
            $update = new \App\Models\HutDoorCode($data);
            $update->id = $id;
            if ($this->model->save($update)) {
                $entry = $this->model->find($id);
                return $this->respond($entry, 200);
            }
        }
        return $this->respond("Failed", 400);
    }

    public function delete($id = null)
    {
        if (!$this->isAdmin()) {
            return $invalidResponse;
        }
        if($this->model->tryDelete($id)) {
            return $this->respond("OK", 200);
        }
        return $this->respond("Not found or not allowed", 400);
    }

}

?>