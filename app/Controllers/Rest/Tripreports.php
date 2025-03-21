<?php

namespace App\Controllers\Rest;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Models\TripReportModel;

class TripReports extends BaseResourceController
{
    private const NO_MENU = false;
    private $tripReportModel;

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

        $this->tripReportModel = model('TripReportModel');
    }

    public function index()
    {
        $limit = $this->request->getGet('limit');
        if ($limit === null || $limit == '') {
            $limit = 100;
        }
        $year = $this->request->getGet('year');
        if ($year === null || $year == '') {
            $year = 0;
        }
        $days = $this->request->getGet('days');
        if ($days === null || $days == '') {
            $days = -1;
        }

        if ($year > 1900)
        {
            $rows = $this->tripReportModel->getByYear($year, $limit);
            return $this->respond($rows);
        } else {
            $rows = $this->tripReportModel->getRecent($limit, $days);
            return $this->respond($rows);
        }
    }

    // Get a trip rerport.
    public function show($id = 0)
    {
        // I'm not sure exactly what the rationale behind this is,
        // but the trip reports app expects to be able to get an empty
        // trip report by requesting id = 0
        if ($id == 0)
        {
            $row = $this->tripReportModel->create();
        } else {
            $row = $this->tripReportModel->getById($id);
            if ($row->id == 0) {
                return $this->failNotFound();
            }
        }
        return $this->respond($row);
    }

    // A list of all years for which trip reports exist in desc. order
    public function show_years()
    {
        $years = $this->tripReportModel->getAllYears();
        return $this->respond($years);
    }

    // Post a new trip report. Returns trip id.
    public function create()
    {
        $response = $this->checkValidUser();
        if ($response === null) {
            $data = $this->getData();
            if ($data === null)
            {
                // Couldn't get any data
                return $this->respond("Missing POST body", 400);
            }
            $id = $this->tripReportModel->saveTripReport($data, true);
            $response = $this->show($id);
        }
        return $response;
    }

    // Update an existing trip report
    public function update($id = null)
    {
        $data = $this->getData();
        if ($data === null) {
            // Couldn't get any data
            return $this->respond("Missing POST body", 400);
        }
        if (!array_key_exists('id', $data)) {
            if ($id === null) {
                return $this->respond("Must specify ID", 400);
            } else {
                $data['id'] = $id;
            }
        }
        if ($id == 0) {
            // Tripreports POSTs to id=0 to create a new trip report
            $response = $this->create();
        } else {
            $response = $this->checkCanEdit($id);
            if ($response === null) {
                $row = $this->tripReportModel->getById($id);
                if ($row->id == 0) {
                    $response = $this->failNotFound();
                } else {
                    $this->tripReportModel->saveTripReport($data, false);
                    $response = $this->show($id);
                }
            }
        }
        return $response;
    }

    // Delete an existing trip report
    public function delete($id = null)
    {
        $response = $this->checkCanEdit($id);
        if ($response === null)
        {
            if ($id === null) {
                return $this->respond("Must specify an ID to delete", 400);
            }
            $row = $this->tripReportModel->getById($id);
            if ($row->id == 0) {
                $response = $this->failNotFound();
            } else if ($this->tripReportModel->deleteTripReport($id)) {
                $response = $this->respond(['result'=> 'success'], 200);
            } else {
                $response = $this->respond(['result'=> 'unexpected failure'], 500);
            }
        }
        return $response;
    }

    private function checkCanEdit($tripReportID)
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