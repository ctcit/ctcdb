<?php

namespace App\Controllers\Rest;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

use App\Models\HutBooking;
use App\Models\HutBookingsModel;

class HutBookings extends BaseResourceController
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

        $this->model = model('HutBookingsModel');
    }

    public function index()
    {
        if ($invalidResponse = $this->checkValidUser()) {
            return $invalidResponse;
        }
        if ($this->isAdmin()) {
            return $this->respond($this->model->findAll());
        }
        return $this->respond($this->model->findByMember(2218));
    }

    public function show($id = 0)
    {
        if ($invalidResponse = $this->checkValidUser()) {
            return $invalidResponse;
        }
        $onlyForId = !$this->isAdmin() ? session()->userID : null;
        if ($result = $this->model->findById($id,$onlyForId)) {
            return $this->respond($result);
        }
        return $this->respond("No booking with id=$id", 404);
    }

    public function create()
    {
        if ($invalidResponse = $this->checkValidUser()) {
            return $invalidResponse;
        }
        $data = $this->getData();
        $booking = new \App\Models\HutBooking($data);
        // PENDING - Pass correct user ID!:W
        $result = $this->model->tryCreate($booking, 2218);
        if ($result['result'] != "OK")
        {
            return $this->respond(["status"=>"failed", "reason"=>$result['result']], 400);
        }
        return $this->respond($result['booking']);
    }

    public function update($id = null)
    {
        if ($invalidResponse = $this->checkValidUser()) {
            return $invalidResponse;
        }
        $data = $this->getData();
        $existingBooking = $this->model->find($id);
        if ($existingBooking) {
            if (!$this->isAdmin() && $invalidResponse = $this->checkIsUser($existingBooking->member_id)) {
                return $invalidResponse;
            }
            $update = new \App\Models\HutBooking($data);
            $update->id = $id;
            if ($this->model->save($update)) {
                $existingBooking = $this->model->find($id);
                return $this->respond($existingBooking, 200);
            }
        }
        return $this->respond("Failed", 400);
    }

    public function delete($id = null)
    {
        if ($invalidResponse = $this->checkValidUser()) {
            return $invalidResponse;
        }
        $onlyForId = !$this->isAdmin() ? session()->userID : null;
        if($this->model->tryDelete($id, $onlyForId)) {
            return $this->respond("OK", 200);
        }
        return $this->respond("Not found or not allowed", 400);
    }

}

?>