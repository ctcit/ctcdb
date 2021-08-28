<?php

namespace App\Controllers\Rest;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

// This class is the controller for the RESTful interface to the GPX archive
class Routes extends BaseResourceController
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

        $this->routeModel = model('RouteModel');
    }

    public function index()
    {
        $action = $this->request->getPost("action");
        $data = array('success' => false, 'message' => 'Operation failed');
        if ($action === "UploadRoute") {
            $route_id = $this->request->getPost('route_id');
            $routenotes = $this->request->getPost('routenotes');
            $filename = $this->request->getPost('gpxfilename');
            $file = $_FILES['gpxfile'];
            $caption = pathinfo ($filename, PATHINFO_FILENAME);
            $gpxdata = file_get_contents($file['tmp_name']);
            $bounds = [ 'left' => $this->request->getPost('left'),
                        'top' => $this->request->getPost('top'),
                        'right' => $this->request->getPost('right'),
                        'bottom'=> $this->request->getPost('bottom') ];
            $trackdate = $this->request->getPost('trackdate');
            if (session()->userID == 0) {
              $data = array('success' => false, 'message' => 'Unauthenticated user: Upload failed for '.$filename);
            } else if ($route_id === "0") {
                // This is a new route
                $errorFileName = null;
                if ($this->routeModel->createNew($caption, $filename, $gpxdata, $routenotes, session()->userID, $bounds, $trackdate) === 0) {
                    $errorFileName = $filename;
                }
                $data = ($errorFileName) ? array('success' => false, 'message' => 'Upload failed for '.$errorFilename)
                                         : array('success' => true, 'message' => $filename.' uploaded');
            } else {
                // We are reloading the gpx file and pertaining attributes for an existing route item
                $row = $this->routeModel->getRoute($route_id);
                $old_routenotes = $row->routenotes;
                if (strpos($old_routenotes, $routenotes) === false) {
                     // Not sure what to do here - everything but this pertains to the new gpx, but user might have added important info
                    $routenotes .= $old_routenotes;
                }
                $errorFileName = "";
                if ($this->routeModel->updateToDatabase($route_id, $caption, $filename, $gpxdata, $routenotes, $this->currentMemberId, $bounds, $trackdate) ===0) {
                    $errorFileName = $filename;
                }

                $data = ($errorFileName !== "") ? array('success' => false, 'message' => 'Upload failed for '.$errorFilename)
                                         : array('success' => true, 'message' => $filename.' uploaded');
            }
        } else if ($action == "DeleteRoutes") {
            if ($this->currentMemberId == 0) {
                $data = array('success' => false, 'message' => 'Unauthenticated user: Upload failed for '.$filename);
            } else {
                $route_ids = json_decode($this->request->getPost('route_ids'));
                $cDeleted = 0;
                foreach ($route_ids as $id) {
                    $this->routeModel->deleteFromDatabase($id);
                    $cDeleted++;
                }
                $result = $cDeleted.' file'.($cDeleted !== 1 ? 's':'').' deleted';
                $data = array('success' => true, 'message' => $result);
            }
        } else if ($action == "DownloadRoutes") {
            // Anyone can do this
            $routeIds = explode(":", $this->request->getPost('routeIds'));
            $gpxs = [];
            foreach ($routeIds as $id) {
                $route = $this->routeModel->getRoute($id);
                if ($route !== null) {
                    $gpxs[] = $route->gpx;
                }
            }
            return $this->respond($gpxs);
        } else if ($action == "UpdateRoute") {
            $id = $this->request->getPost("id");
            $propname = $this->request->getPost("propname");
            $value = $this->request->getPost("value");
            $this->routeModel->updateRoute($id, $propname, $value);
            $data = array('success' => true, 'message' => $propname.' updated');
        }
        // Discard any potential output generated internally by php
        ob_end_clean();
        return $this->respond($data);
    }
}
