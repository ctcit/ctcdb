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
    // and only allow one future code to be set at a time - and we delete any other future codes.
    // We intentionally only support GET (index) and POST (create) here - editing and deleting are only
    // supported but POSTing a new record.

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
            "future_code" => isset($future[0]) ? $future[0]->code : '',
            "future_effective" => isset($future[0]) ? $future[0]->effective : ''
        ];
        return $this->respond($result);
    }

    public function create()
    {
        if (!$this->isAdmin()) {
            return $invalidResponse;
        }
        // Delete all existing entries - if they don't match the new ones
        $data = $this->request->getJSON();
        if (!isset($data->current_code) || !isset($data->future_code) || !isset($data->future_effective)) {
            return $this->respond("Missing data", 400);
        }
        $existing = $this->model->current();
        if ($existing && $existing->code != $data->current_code) {
            $this->model->delete($existing->id);
            $entry = new \App\Models\DoorCode(["effective"=>date("Y-m-d"), "code"=>$data->current_code  ]);
            $this->model->save($entry);
        }
        $future = $this->model->future();
        $exists = false;
        foreach($future as $f) {
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