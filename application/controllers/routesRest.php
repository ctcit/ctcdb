<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

// This controller contains archive functions 
// some functions available to anyone and others only available to club members logged into the joomla website.

define("NO_MENU", False); // Parameter value for "$menuReqd param to _loadPage

require_once('application/libraries/REST_Controller.php');

class ArchiveRest extends REST_Controller {

	var $currentMemberId = 0;
	var $currentUserName = '';

	public function __construct($config = 'rest')
	{
		parent::__construct($config);
		global $userData;
        if ($userData !== null){
		  $this->currentMemberId = $userData['userid'];
		  $this->currentUserName = $userData['name'];
        }
		$this->load->database(); //Todo find out how database is connected
		$this->load->helper(array('url','form','date','pageload'));
		$this->load->model('archiveitemmodel');
	}
     
    // This processes a single gpx file
    public function archiveItem_post(){
        $action = $_POST["action"];
        $data = array('success' => false, 'message' => 'Operation failed');
        if ($action === "UploadArchiveItem"){
            $archive_id = $_POST['archive_id'];
            $routenotes = $_POST['routenotes'];
            $filename = $_POST['gpxfilename'];
            $file = $_FILES['gpxfile'];
            $caption = pathinfo ($filename, PATHINFO_FILENAME);
            $gpxdata = file_get_contents($file['tmp_name']);
            $bounds = array('left'=>$_POST['left'], 'top'=>$_POST['top'], 'right'=>$_POST['right'],'bottom'=>$_POST['bottom']);
            $trackdate = $_POST['trackdate'];
            if ($this->currentMemberId == 0) {
                $this->response('Unauthenticated', 401);
                die();
            }
            if ($archive_id === "0"){
                // This is a new archive item
                $errorFileName = null;
                //$caption, $gpxfilename, $gpx, $routenotes, $originatorid, $bounds, $date
                if ($this->archiveitemmodel->create_new($caption, $filename, $gpxdata, $routenotes, $this->currentMemberId, $bounds, $trackdate) === 0)
                    $errorFileName = $filename;
                $data = ($errorFileName) ? array('success' => false, 'message' => 'Upload failed for '.$errorFilename)
                                  : array('success' => true, 'message' => $filename.' uploaded');
            }else{
                // We are reloading the gpx file and pertaining attributes for an existing archive item
                $row = $this->archiveitemmodel->get_archive_item($archive_id);
                $old_routenotes = $row->routenotes;
                if (strpos($old_routenotes, $routenotes) === false)
                     // Not sure what to do here - everything but this pertains to the new gpx, but user might have added important info
                    $routenotes .= $old_routenotes;
                $errorFileName = "";
                if ($this->archiveitemmodel->update_to_database($archive_id, $caption, $filename, $gpxdata, $routenotes, $this->currentMemberId, $bounds, $trackdate) ===0)
                  $errorFileName = $filename;
                $data = ($errorFileName !== "") ? array('success' => false, 'message' => 'Upload failed for '.$errorFilename)
                                         : array('success' => true, 'message' => $filename.' uploaded');
            }
        }else if ($action == "DeleteArchiveItems"){
            if ($this->currentMemberId == 0) {
                $this->response('Unauthenticated', 401);
                die();
            }
            $archive_item_ids = json_decode($_POST['archive_item_ids']);
            $cDeleted = 0;
            foreach ($archive_item_ids as $id){
               $this->archiveitemmodel->delete_from_database($id);
               $cDeleted++;
            }
            $result = $cDeleted.' file'.($cDeleted !== 1 ? 's':'').' deleted'; 
            $data = array('success' => true, 'message' => $result);
        }else if ($action == "DownloadArchiveItems"){
            // Anyone can do this
            $archiveItemIds = explode(":", $_POST['archiveItemIds']);
            foreach ($archiveItemIds as $id){
                $archiveitem = $this->archiveitemmodel->get_archive_item($id);
                $gpxs[] = $archiveitem->gpx;             
            }            
            $this->response(json_encode($gpxs));
            return;
        }else if ($action == "UpdateArchiveItem"){
            $id = $_POST["id"];
            $propname = $_POST["propname"];
            $value = $_POST["value"];
            $this->archiveitemmodel->update_archive_item($id, $propname, $value);
            $data = array('success' => true, 'message' => $propname.' updated');
        }
        ob_end_clean(); // Discard any potential output generated internally by php
        $this->response(json_encode($data));
    }

}
