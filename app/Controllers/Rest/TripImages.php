<?php

namespace App\Controllers\Rest;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class TripImages extends BaseResourceController
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

        $this->imageModel = model('ImageModel');
    }

    public function index()
    {
        // PENDING
        return $this->respond([]);
    }

    // Get a trip image.
    public function show($id = 0)
    {
        // PENDING
        return $this->respond([]);
    }

    // Post a new trip image. Returns trip image id.
    public function create()
    {
        $response = $this->checkValidUser();
        if ($response === null) {
            $data = $this->getData();
            $name = $data['name'];
            $caption = $data['caption'];
            $dataUrl = $data['dataUrl'];
            $id = $this->imageModel->create_from_dataurl($name, $caption, $dataUrl);
            $response = $this->respond(['id'=>$id]);
        }
        return $response;
    }

    // Update an existing trip image
    public function update($id = null)
    {
        // PENDING
        return $this->respond([]);
    }

    // Delete an existing trip image
    public function delete($id = null)
    {
        // PENDING - Currently ANY logged in user can delete ANY image
        $response = $this->checkValidUser();
        if ($response === null) {
            if ($id === null) {
                $response = $this->respond("Must specify an ID to delete", 400);
            }
            else if ($this->imageModel->deleteImage($id)) {
                $response = $this->respond(['result'=> 'success'], 200);
            } else {
                $response = $this->respond(['result'=> 'unexpected failure'], 500);
            }
        }
        return $response;
    }

}

?>