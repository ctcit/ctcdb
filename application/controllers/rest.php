<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once('application/libraries/REST_Controller.php');

class Rest extends REST_Controller {
    // This class is the controller for the RESTful interface to the CTC
    // database.
    // To handle CORS (Cross Origin Resource Sharing) it first issues
    // the access-control headers, and then quits if it's an OPTIONS request,
    // which is the "pre-flight" browser generated request to check access.
    // See http://stackoverflow.com/questions/15602099/http-options-error-in-phil-sturgeons-codeigniter-restserver-and-backb

    public function __construct()
    {
        //header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
        header('Access-Control-Allow-Credentials: true');
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, " .
                "Content-Type, Accept, Access-Control-Request-Method");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, HEAD, DELETE");
        $method = $_SERVER['REQUEST_METHOD'];
        if($method == "OPTIONS") {
            die();
        }
        
        parent::__construct();
        $this->load->database('ctcweb9_joom1');
        $this->load->model('tripreportmodel');
    }
    
    
    protected function log($type, $message) {
        // Call log_message with the same parameters, but prefix the message
        // by *rest* for easy identification.
        log_message($type, '*rest* ' . $message);
    }
    
    
    protected function error($message, $httpCode=400) {
        // Generate the http response containing the given message with the given
        // HTTP response code. Log the error first.
        $this->log('error', $message);
        $this->response($message, $httpCode);
    }
    
    
    public function index_get() {
        $this->response('Please access this API via the tripreports collection');
    }
    
    // ****************************
    //        TRIP REPORTS
    // ****************************

    // Put (i.e. update) a trip report
    public function tripreports_put($report_id) {
        $this->checkCanEdit($report_id);
        $data = $this->put(null, True); // All input data, xss filtered
        $this->tripreportmodel->saveReport($data, FALSE);
    }
    
    // Post a new trip report. Returns trip id.
    public function tripreports_post() {
        $this->checkLoggedIn();
        $data = $this->post(null, True); // All input data, xss filtered
        $id = $this->tripreportmodel->saveReport($data, TRUE);
        $this->response(array('id'=>$id));
    }
    
    public function tripreportyears_get() {
        // A list of all years for which trip reports exist in desc. order
        $years = $this->tripreportmodel->getAllYears();
        $this->response($years);
    }
 
    // Get a trip rerport.
    public function tripreports_get($id=NULL) {
        global $userData;
        if ($id) {
            $row = $this->tripreportmodel->getById($id);
            if ($row->id == 0) {
                show_404($this->uri->uri_string());
            }
        } else {
            $row = $this->tripreportmodel->create();
        }
        $this->response($row);
    }
    
    // Get a list of trip reports for a given year.
    public function yearstripreports_get($year) {
        $rows = $this->tripreportmodel->getByYear($year);
        $this->response($rows);
    }
    
    // Delete the given trip.
    public function tripreports_delete($id) {
        $this->checkCanEdit($id);
        $this->tripreportmodel->delete($id);        
    }
    
    
    private function checkCanEdit($id) {
        // Check for a current user. If not, issue an immediate 401 Not authenticated.
        // Then check if the currently logged in user can edit the
        // trip report with the given id. Otherwise issue an immediate
        // 403 unauthorised response.
        // Only the original trip report author or a club officer can
        // delete trip.
        // If called with a non-existent tripid an exception will be thrown
        // and an HTTP return code of 500 will result.
        // If the function returns normally, editing can proceed.
        global $userData;
        $this->checkLoggedIn();
        $row = $this->tripreportmodel->getById($id);
        if (count($userData['roles']) == 0 && $userData['userid'] !== $row->id) {
            $this->response('Unauthorised trip report modification', 403);
            die();
        }
    }
    
    
    private function checkLoggedIn() {
        // Check that there is a currently logged in user. If not issue
        // an immediate 401 not authenticated response. Otherwise just return
        // (no value).
        global $userData;
        if ($userData['userid'] == 0) {
            $this->response('Unauthenticated', 401);
            die();
        }
    }
    
    // *******************************
    //    CURRENT USER INFO
    // *******************************
    // Return the data relating to the currently-logged in user.
    // If no user is logged in this will be just {id: 0}. Otherwise it
    // will be an object with id, login, name and a list of official roles for that
    // user.
    public function user_get() {
        global $userData;
        $data = array('id'=>(isset($userData['userid']) ? $userData['userid'] : 0));
        if ($data['id']) {
            $data['login'] = $userData['login'];
            $data['name'] = $userData['name'];
            $data['roles'] = $userData['roles'];
        }
        $this->response((object) $data);
    }

    
    // ********************************
    //       IMAGES
    // ********************************   
    
    
    public function tripimages_post() {
        // Add a new trip image to the database. Body is a JSON record with the
        // following attributes:
        //    name: the image name (usually the original filename)
        //    caption: the caption to be displayed (if desired)
        //    dataUrl: the image in the form of a dataUrl
        $this->checkLoggedIn();
        $this->load->model('imagemodel');
        $name = $this->post('name', false);
        $caption = $this->post('caption', false);
        $dataUrl = $this->post('dataUrl', false);
        $this->log('debug', "Received image $name, captioned $caption");
        $id = $this->imagemodel->create_from_dataurl($name, $caption, $dataUrl);
        $this->response(array('id'=>$id));
    }
    
    
    public function tripimages_get($image_id) {
        // Get the specified image. Returns a JSON record containing the
        // following attributes:
        //    name: the image name (usually the original filename)
        //    caption: the caption to be displayed (if desired)
        //    width:  the width in pixels
        //    height: the height in pixels
        //    url:  an url that can be used in an <img> tag to display the image
        //    t_width: the width in pixels of the thumbnail image
        //    t_height: the height in pixels of the thumbnail
        //    t_url: an url that can be used to display the image
    }
    
    public function tripimages_delete($image_id) {
        // Delete a specified trip image
        $this->checkLoggedIn();  // TODO - better security
        $this->load->model('imagemodel');
        $this->imagemodel->delete($image_id);
        
    }
    
    // ********************************
    //       GPXS
    // ********************************  
    
    public function gpxs_post() {
        $this->checkLoggedIn();
        $this->load->model('gpxmodel');
        $name = $this->post('name', false);
        $caption = $this->post('caption', false);
        $dataUrl = $this->post('dataUrl', false);
        $this->log('debug', "Received gpx file $name, captioned $caption");
        $id = $this->gpxmodel->create_from_dataurl($name, $caption, $dataUrl);
        $this->response(array('id'=>$id));
        
    }
    
    public function gpxs_get($gpx_id) {
        // TODO: 
    }
    
    public function gpxs_delete($gpx_id) {
        $this->checkLoggedIn();  // TODO - better security
        $this->load->model('gpxmodel');
        $this->gpxmodel->delete($gpx_id);        
    }
}