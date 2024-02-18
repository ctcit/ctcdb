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

    // The HutDoorCodesModel allows for an arbitrary number of code records
    // Here we only ever return the current code and the next future code,
    // and only allow one future code to be set at a time - and we delete any other future codes

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
        $current = $this->model->current();
        $future = $this->model->future();
        $result = [
            "current_code" => $current->code,
            "future_code" => $future[0]->code,
            "future_effective" => $future[0]->effective
        ];
        return $this->respond($result);
    }

    /*
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
    */

    // We (soft) delete alll existing records and add new ones
    public function create()
    {
        if (!$this->isAdmin()) {
            return $invalidResponse;
        }
        // Delete all existing entries - if they don't match the new ones
        $data = $this->request->getJSON();
        if (!isset($data->code) || !isset($data->future_code) || !isset($data->future_effective)) {
            print_r($data);
            return $this->respond("Missing data", 400);
        }
        $existing = $this->model->current();
        if ($existing && $existing->code != $data->code) {
            $this->model->delete($existing->id);
            $entry = new \App\Models\DoorCode(["effective"=>date("Y-m-d"), "code"=>$data->code  ]);
            $this->model->save($entry);
        }
        $future = $this->model->future();
        foreach($future as $f) {
            $exists = false;
            if ($f->code == $data->future_code && $f->effective == $data->future_effective) {
                $exists = true;
            } else {
                $this->model->delete($f->id);
            }
        }
        if (!$exists) {
            $entry = new \App\Models\DoorCode(["effective"=>$data->future_effective, "code"=>$data->future_code]);
            $this->model->save($entry);
        }
        return $this->index();
    }
}

?>