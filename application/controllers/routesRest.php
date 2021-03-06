<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

define("NO_MENU", False); // Parameter value for "$menuReqd param to _loadPage

require_once('application/libraries/REST_Controller.php');

class RoutesRest extends REST_Controller
{
    // This class is the controller for the RESTful interface to the GPX archive
	var $currentMemberId = 0;
	var $currentUserName = '';

	public function __construct($config = 'rest')
	{
		parent::__construct($config);
		global $userData;
        if ($userData !== null) {
		  $this->currentMemberId = $userData['userid'];
		  $this->currentUserName = $userData['name'];
        }
		$this->load->database(); //Todo find out how database is connected
		$this->load->helper(array('url','form','date','pageload'));
		$this->load->model('routemodel');
	}
     
    public function route_post()
    {
        $action = $_POST["action"];
        $data = array('success' => false, 'message' => 'Operation failed');
        if ($action === "UploadRoute") {
            $route_id = $_POST['route_id'];
            $routenotes = $_POST['routenotes'];
            $filename = $_POST['gpxfilename'];
            $file = $_FILES['gpxfile'];
            $caption = pathinfo ($filename, PATHINFO_FILENAME);
            $gpxdata = file_get_contents($file['tmp_name']);
            $bounds = array('left'=>$_POST['left'], 'top'=>$_POST['top'], 'right'=>$_POST['right'],'bottom'=>$_POST['bottom']);
            $trackdate = $_POST['trackdate'];
            if ($this->currentMemberId == 0) {
              $data = array('success' => false, 'message' => 'Unauthenticated user: Upload failed for '.$filename);
            } else if ($route_id === "0") {
                // This is a new route 
                $errorFileName = null;
                //$caption, $gpxfilename, $gpx, $routenotes, $originatorid, $bounds, $date
                if ($this->routemodel->createNew($caption, $filename, $gpxdata, $routenotes, $this->currentMemberId, $bounds, $trackdate) === 0) {
                    $errorFileName = $filename;
                }
                $data = ($errorFileName) ? array('success' => false, 'message' => 'Upload failed for '.$errorFilename)
                                         : array('success' => true, 'message' => $filename.' uploaded');
            } else {
                // We are reloading the gpx file and pertaining attributes for an existing route item
                $row = $this->routemodel->getRoute($route_id);
                $old_routenotes = $row->routenotes;
                if (strpos($old_routenotes, $routenotes) === false) {
                     // Not sure what to do here - everything but this pertains to the new gpx, but user might have added important info
                    $routenotes .= $old_routenotes;
                }
                $errorFileName = "";
                if ($this->routemodel->updateToDatabase($route_id, $caption, $filename, $gpxdata, $routenotes, $this->currentMemberId, $bounds, $trackdate) ===0) {
                    $errorFileName = $filename;
                }
                  
                $data = ($errorFileName !== "") ? array('success' => false, 'message' => 'Upload failed for '.$errorFilename)
                                         : array('success' => true, 'message' => $filename.' uploaded');
            }
        } else if ($action == "DeleteRoutes") {
            if ($this->currentMemberId == 0) {
                $data = array('success' => false, 'message' => 'Unauthenticated user: Upload failed for '.$filename);
            } else {
                $route_ids = json_decode($_POST['route_ids']);
                $cDeleted = 0;
                foreach ($route_ids as $id) {
                $this->routemodel->deleteFromDatabase($id);
                $cDeleted++;
                }
                $result = $cDeleted.' file'.($cDeleted !== 1 ? 's':'').' deleted'; 
                $data = array('success' => true, 'message' => $result);
            }
        } else if ($action == "DownloadRoutes") {
            // Anyone can do this
            $routeIds = explode(":", $_POST['routeIds']);
            foreach ($routeIds as $id) {
                $route = $this->routemodel->getRoute($id);
                $gpxs[] = $route->gpx;             
            }            
            $this->response(json_encode($gpxs));
            return;
        } else if ($action == "UpdateRoute") {
            $id = $_POST["id"];
            $propname = $_POST["propname"];
            $value = $_POST["value"];
            $this->routemodel->updateRoute($id, $propname, $value);
            $data = array('success' => true, 'message' => $propname.' updated');
        }
        ob_end_clean(); // Discard any potential output generated internally by php
        $this->response(json_encode($data));
    }

    // used by triphub
    // if 'id' specified, get single route by id (with Gpx)
    // if no 'id' specified, get all routes (without Gpx)
    public function route_get()
    {
        $data = array('success' => false, 'message' => 'Operation failed');
        $id =  $this->_get_args["id"];
        if ($id !== null) {
            // get single route by id
            $data = $this->routemodel->getRoute($id);
        }
        else {
            // get all routes
            $data = $this->routemodel->getAllRoutes(null);
        }          
        ob_end_clean(); // Discard any potential output generated internally by php
        $this->response($data);
    }

}
