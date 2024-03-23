<?php

namespace App\Controllers\Rest;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class HutAvailability extends BaseResourceController
{
    private const NO_MENU = false;

    public $model;

    public const BookingHorizonMonths = 6;

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

        $dt = new \DateTime();
        $availability = [];
        $lastMonth = (clone $dt)->modify('+' . self::BookingHorizonMonths . ' months')->format('m-Y');
        while( $dt->format('m-Y') != $lastMonth ) {
            $date = $dt->format('Y-m-d');
            $availability[$date] = $this->model->bunksAvailableOnDate($date);
            $dt->modify('+1 day');
        }
        return $this->respond($availability);
    }
}

?>