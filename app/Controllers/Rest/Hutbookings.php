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
        $pageSize = $this->request->getGet("pageSize") ?? 10;
        if ($this->isAdmin() && !is_null($this->request->getGet("admin"))) {
            $past = !is_null($this->request->getGet("past"));
            $today = (new \DateTime("today"))->format('Y-m-d');
            if ($past) {
                return $this->respond($this->model->where("start_date < '$today'" )
                                           ->orderBy("start_date", "desc")
                                           ->paginate($pageSize));
            } else {
                return $this->respond($this->model->where("start_date >= '$today'")
                                           ->orderBy("start_date", "asc")
                                           ->paginate($pageSize));
            }
        }
        return $this->respond($this->model
                                   ->findByMember(session()->userID, $pageSize));
    }

    public function show($id = 0)
    {
        if ($invalidResponse = $this->checkValidUser()) {
            return $invalidResponse;
        }
        $onlyForId = !$this->isAdmin() ? session()->userID : null;
        if ($result = $this->model->findById($id, $onlyForId)) {
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
        $result = $this->model->tryCreate($booking);
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
        $data['id'] = $id;
        $booking = new \App\Models\HutBooking($data);
        $result = $this->model->tryUpdate($booking);
        if ($result['result'] != "OK")
        {
            return $this->respond(["status"=>"failed", "reason"=>$result['result']], 400);
        }
        return $this->respond($result['booking']);
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