<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

define("NO_MENU", False); // Parameter value for "$menuReqd param to _loadPage
class Archive extends MY_Controller {
	// This class is the main controller for all member/membership functions
	// used by the club database administrators to manage membership data.
	// These functions are accessible only to club officers whose roles
	// are listed in the "full_access_roles" array in config.php.
	//TODO Add credit transfers to new memberships when coupling/decoupling.
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
		$this->load->model('archiveitemmodel');
    }
    
        
    // Function to display list of archive items
	// TODO allow for filter
	public function archiveItemList(){
		global $userData;
        $userId = ($userData !== null) ? $userData['userid']: 0;
        $archiveItems = $this->archiveitemmodel->get_all_archive_items(null);
		$this->_loadPage('archiveItemListView','Route archive list',
			array('archiveItems'=>$archiveItems,
                  'css'=> "archiveList.css",
                  'userId'=> $userId),
			NO_MENU
		);
	} 
    
    public function downloadGpx($p_id){
        $archive_item = $this->archiveitemmodel->get_archive_item($p_id);
        $data = array('gpxfilename'=>$archive_item->gpxfilename, 'gpx'=>$archive_item->gpx, 'contentPage'=>'downloadGpx');
        $this->load->view('downloadTemplate', $data);
    }
    
    public function showArchiveMapping($p_ids, $p_title){
        $data = array('archiveItemIds'=>$p_ids, 'css'=> "archiveList.css");// Leave as string for target to split up
        $this->_loadPage('archivemapping', $p_title, $data, NO_MENU);
    }    

}
