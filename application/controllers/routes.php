<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

define("NO_MENU", False); // Parameter value for "$menuReqd param to _loadPage

class Routes extends MY_Controller 
{
	// This controller contains route functions.
	// Some functions available to anyone and others only available to club members logged into the joomla website.
	var $currentMemberId;

	public function __construct()
	{
		parent::__construct();
		global $userData;
        if ($userData !== null){
		  $this->currentMemberId = $userData['userid'];
		  $this->currentUserName = $userData['name'];
        }
		$this->load->database(); //Todo find out how database is connected
		$this->load->helper(array('url','form','date','pageload'));
		$this->load->model('routemodel');
    }
    
        
    // Function to display list of routes
	// TODO allow for filter
	public function routeList()
	{
		global $userData;
        $userId = ($userData !== null) ? $userData['userid']: 0;
		$routes = $this->routemodel->getAllRoutes(null);
		$this->_loadPage('routeListView','Route archive list',
			array('routes' => $routes,
                  'css'=> "routeList.css",
                  'userId'=> $userId),
			NO_MENU
		);
	} 
    
	public function downloadGpx($p_id)
	{
        $route = $this->routemodel->getRoute($p_id);
        $data = array('gpxfilename' => $route->gpxfilename, 'gpx' => $route->gpx, 'contentPage' => 'downloadGpx');
        $this->load->view('downloadTemplate', $data);
    }
    
	public function showRouteMapping($p_ids, $p_title)
	{
        $data = array('routeIds' => $p_ids, 'css' => "routeList.css");// Leave as string for target to split up
        $this->_loadPage('routemapping', $p_title, $data, NO_MENU);
    }    

}
