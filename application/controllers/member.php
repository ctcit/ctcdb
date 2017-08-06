<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

// This controller contains functions available to club members logged into the
// joomla website.

define("NO_MENU", False); // Parameter value for "$menuReqd param to _loadPage

class Member extends MY_Controller {

	var $currentMemberId;
	var $currentPassword;
	var $currentUserName;
	var $currentUserLogin;

	public function __construct()
	{
		global $userData;
		if ($userData['userid'] == 0) {
			$ctcHome = config_item('joomla_base_url');
			echo '<head><script language="javascript">top.location.href="'.$ctcHome.'";</script></head>';
			die('Not logged in.');
		}
		parent::__construct();
		$this->currentMemberId = $userData['userid'];
		$this->currentUserLogin = $userData['login'];
		$this->currentUserName = $userData['name'];
		$this->load->database();
		$this->load->helper(array('url','form','date','pageload'));
		$this->load->model('Ctcmodel');
        $this->load->model('Tripchangenotificationmodel');
		$this->load->library('form_validation');
		$this->form_validation->set_error_delimiters('<p style="font-size: 18px; color: red">', '</p>');
	}

	// Function to display a menu of user-profile update options
	// @param $id -- the id of the member whose profile is being edited
	public function userDetails($id = 0) {
		global $userData;
		if ($id == 0) {
			$id = $userData['userid'];
		}
		else if ($userData['userid'] != $id) {  // Security check
			die("Access denied 1");
		}
		//$query = $this->db->query("select id from ctcweb9_joom1.jos_contact_details where name='dbadmin'");
        //$result = $query->row();
        //$dbAdminId = $result->id;
        $baseUrl = $this->config->item('joomla_base_url');
		//$sendEmailUrl = $baseUrl."?option=com_contact&amp;task=view&amp;contact_id=".strval($dbAdminId);
		$sendEmailUrl = $baseUrl."/index.php/contact-us/7-dbadmin";
		$this->currentPassword = $this->Ctcmodel->getMemberPassword($id);

		$isBrand8Flame7 = $this->_passwordCheck2('brand8flame7');
		$profileUrl = "member/profile/$id";
		$subsUrl = "member/subsForm/$id";
		$this->_loadPage('userDetailsOptions', 'User Details',
			$data = array(
				'changePasswordUrl' => "member/changePassword/$id",
				'isBrand8Flame7' => $isBrand8Flame7,
				'changeProfileUrl'=> $profileUrl,
			    'subsPaymentForm' => $subsUrl,
				'sendEmailUrl' => $sendEmailUrl,
				'css'=> "memberUpdate.css"),
			NO_MENU
		);

	}

    public function printableMembershipList(){
 		global $userData;
		if ($userData['userid'] === 0) {  // Security check
			die("Access denied #2");
		}
		$members = $this->Ctcmodel->getAllActiveMembers();//: $this->Ctcmodel->getAllActiveMembersByFirstName();
		$this->_loadPage('printableMembershipList','Membership list',
			array('members'=>$members,
                  'surnameFirst'=>TRUE,
                  'css'=> "memberUpdate.css"),
			NO_MENU
		);
   }

    public function printableMembershipListByFirstName(){
 		global $userData;
		if ($userData['userid'] === 0) {  // Security check
			die("Access denied #2");
		}
		$members = $this->Ctcmodel->getAllActiveMembersByFirstName();
		$this->_loadPage('printableMembershipList','Membership list',
			array('members'=>$members,
                  'surnameFirst'=>FALSE,
                  'css'=> "memberUpdate.css"),
			NO_MENU
		);
   }
    
	// Function to display the club membership list
	// UNTESTED.
	public function membershipList(){
		global $userData;
        $id = $userData['userid'];
		if ($id == 0) {  // Security check
			die("Access denied #2");
		}
		$printableListBySurnameUrl = "member/printableMembershipList";
		$printableListByFirstnameUrl = "member/printableMembershipListByFirstName";
		$membersBySurname = $this->Ctcmodel->getAllActiveMembers();
        $membersByFirstName = $this->Ctcmodel->getAllActiveMembersByFirstname();
		$this->_loadPage('membershipList','Membership list',
			array('membersBySurname'=>$membersBySurname,
                  'membersByFirstName'=>$membersByFirstName,
                  'css'=> "memberUpdate.css",
                  'printableListBySurnameUrl'=>$printableListBySurnameUrl,
                  'printableListByFirstnameUrl'=>$printableListByFirstnameUrl),
			NO_MENU
		);
	}

	// Function to allow a club member to update their profile.
	// @param $id -- the id of the member whose profile is being edited
	public function profile($id = 0)
	{
		global $userData;
		if ($id == 0 || $userData['userid'] != $id) {  // Security check
			die("Access denied #2");
		}

		$this->currentMemberId = $id;
		$this->_setupMemberValidation();
        $this->_setupMembershipValidation();

		if ($this->form_validation->run()) {
			// If we've received a valid form, update the DB and report the result.
			$this->handleValidUpdateForm($id);
		}
		else {
			// If we haven't got a valid form, then we must (re)load the update profile form
			$this->handleInvalidUpdateForm($id);
		}
	}

	// Function to handle the receipt of a valid profile update form
	public function handleValidUpdateForm($id)
	{
		$changes = $this->Ctcmodel->updateMember($id, true);
		if ($changes === Null) {
			$this->_loadPage('operationOutcome', "Update Failed",
				array(
					'message' => 'CTCDB: Profile Update Failure',
					'tellWebmaster' => True,
					'css'=> "memberUpdate.css"),
				NO_MENU
			);
		}
		else if (count($changes) > 0) {
			$this->emailChanges($changes);
			$this->_loadPage('operationOutcome', "Successful Update",
				array(
					'message' => 'Your profile has been successfully updated.',
					'css'=> "memberUpdate.css"),
				NO_MENU
			);
		}
		else  {
			$this->_loadPage('operationOutcome', "No change",
					array(
						'message' => 'CTCDB: Profile Update -- No Change',
						'extraInfo' => 'The update of your profile was completed with no errors but did not ' .
							' actually alter the information stored in the database!' .
							' Are you sure you actually changed the data in the form?',
						'css'=> "memberUpdate.css"),
					NO_MENU
			);
		}
	}

	// Function to handle the receipt of an invalid profile update form.
	// (Re)loads form
	public function handleInvalidUpdateForm($id)
	{
		$isPostBack = count($_POST) > 0;
		if ($isPostBack) {
			$fields = $this->_getFormDataFromPost();  // Get the set of fields from the posted form
		}
		else {
			$fields = $this->getProfileDataFromDb($id);	// Else build a new form
		}

		$this->_loadPage('memberProfileUpdateForm',
						 'Member Details Form',
						 array(
							'fields'=>$fields,
							'postbackUrl'=> "member/profile/$id",
							'css' => "memberUpdate.css"),
						 NO_MENU);
	}


	// Function to allow a club member to change their password.
	// @param $id -- the id of the member whose password is being edited
	// This function uses the new (version 1.7) form form_validation class.
	// TODO: rationalise password computation (which also exists in ctc_model)_.
	public function changePassword($id = 0)
	{
		global $userData;
		if ($id == 0 || $userData['userid'] != $id) {  // Security check
			die("Access denied 3");
		}
		$this->load->library('form_validation');
		$this->form_validation->set_error_delimiters('<p class="error">', '</p>');
		$this->form_validation->set_rules('currentpass', 'Current Password', 'trim|required');
		$this->currentPassword = $this->Ctcmodel->getMemberPassword($id);
		$this->form_validation->set_rules('currentpass', 'Current Password', 'callback__passwordCheck');
		$this->form_validation->set_rules('newpass', 'New Password', 'trim|required|min_length[5]|matches[newpassconf]');
		$this->form_validation->set_rules('newpassconf', 'Confirm New Password', 'trim|required');


		if ($this->form_validation->run()) {
			$this->Ctcmodel->setMemberPasswordRaw($id, $_POST['newpass']);
			$this->_loadPage('operationOutcome', "Password changed",
				array(
					'message' => 'Your password has been changed.',
					'css'=> "memberUpdate.css"),
				NO_MENU
			);
		}
		else {
			$this->_loadPage('passwordChangeForm','Password change',
				array('currentPassword'=>$this->currentPassword,
					  'postbackUrl'=>"member/changePassword/$id",
					  'css'=> "passwordUpdate.css"),
				NO_MENU);
		}

	}

	public function _passwordCheck($pass)
	// Validate that current password matches the one in the database
	// (after salting and hashing). Set form_validation error message if not.
	{
		$matches = $this->_passwordCheck2($pass);
		if (!$matches) {
			$this->form_validation->set_message('_passwordCheck', 'Wrong current password');
		}
		return $matches;
	}

	public function _passwordCheck2($pass)
	// True iff current password is $pass
	{
		$bits = preg_split('/:/', $this->currentPassword);
		$hash = $bits[0];
		$salt = count($bits) == 2 ? $bits[1] : '';
		$ok = md5($pass.$salt) == $hash;
		return $ok;
	}

	// Function to allow club members to view the subscription payment form
	public function subsForm($id = 0)
	{
		global $userData;
		if ($id == 0 || $userData['userid'] != $id) { // security check
			die("Access denied");
		}

		$this->load->helper('utilities');
		$year = getSubsYear();
		$query = $this->Ctcmodel->getMembershipPaymentStatus($year, $id);
		if ($query->num_rows() != 1) {
			$this->loadPage("operationOutcome", "Oops.",
				array('tellWebmaster' => True),
				NO_MENU);
		}
		else {
			$row = $query->row();
			$this->_loadPage('subsDetailsForm','Your Subscription Renewal Form',
					array('name'	 => $row->MembershipName,
					      'msType'   => $row->Type,
						  'login' 	 => $row->Login,
						  'msid'  	 => $row->MSID,
						  'sub'  	 => $row->Fee,
						  'paid'  	 => $row->DatePaid != NULL,
					      'overdue'  => False,
						  'css'=> "passwordUpdate.css"),
					NO_MENU);
		}
	}


    // Trip change notification form: allows trip leader (or anyone, actually)
    // to send a trip change notification to the database and email
    // associated subscribers.
    public function tripChangeNotification()
    {
        $this->_loadPage('tripChangeNotificationForm', 'Trip Change Notification',
                array('memberName'=>$this->currentUserName,
                      'css'=> "memberUpdate.css"), NO_MENU);
    }


    // Process trip change notification postback
    public function processTripChange()
    {
        $name = $this->currentUserName;
        $mob = '';   // Mobile number (irrelevant and unknown)
        $message = $this->input->post('tripChangeMessage');
        $this->Tripchangenotificationmodel->insert($mob, $name, $message);
        $this->_loadPage('operationOutcome', 'Message received',
                array('message'=>'Thank you. Your trip change notification has been received',
                      'css'=> "memberUpdate.css"), NO_MENU);
    }


    // Function to allow a club member to see a list of incoming trip change
    // notifications, most recent first. Displays at most 100 messages
    public function listTripChangeNotifications($menuReqd=1)
    {
        $texts = $this->Tripchangenotificationmodel->getAllRecent();
        $params = array('texts'=>$texts);
        if (!$menuReqd) {
            $params['css'] = "memberUpdate.css";
        }
        $this->_loadPage('tripChangeNotifications', 'Recent Trip Changes',
                $params, $menuReqd);
    }


	// PROFILE UPDATE SUPPORT FUNCTIONS
	// ================================
	public function getProfileDataFromDb($id)
	// Extract from the database all the information required to display correctly
	// the member update form for a particular member (which includes
	// their current data values). Returns a set of form field definitions
	// as described in _getNewMemberFormData.
	{
		$row = $this->Ctcmodel->getMemberDataByMemberId($id);
		$memberFields = $this->Ctcmodel->getMemberProfileFields();
		$membershipFields = $this->Ctcmodel->getMembershipProfileFields();
		$allProfileFields = array_merge($memberFields , $membershipFields);

		$fields = array();
		foreach (array_keys($row) as $key) {
			if (in_array($key, $allProfileFields)) {
				$label = $this->_makeLabel($key);
				$type = $this->_formFieldType($key);
				$values = $this->_formFieldValues($key);
				$field = array('type'=>$type, 'label' => $label, 'value' => $row[$key], 'values' => $values);
				$fields[$key] = $field;
			}
		}
		$partner = $this->Ctcmodel->getPartnerName($id);
		if ($partner == '') $partner = "N/A";
		$fields['statusAdminHidden'] = array('type'=>'hidden', 'label'=>'', 'value'=>$row['statusAdmin']);
		$fields['partnerHidden'] = array('type'=>'hidden', 'label' =>'', 'value'=>$partner);
		$fields['memberNameHidden'] = array('type'=>'hidden', 'label' =>'', 'value'=> $row['firstName'].' '.$row['lastName']);
		return $fields;
	}

	public function emailChanges($changeList)
	// Generate an email to dbadmin@ctc.org.nz with a list of the changes
	// made by the given userID to their member & membership data.
	{
		$message = "User {$this->currentUserName}, logged in as {$this->currentUserLogin}, has made the following changes.\n\n";
		foreach (array_keys($changeList) as $key) {
			list($old, $new) = $changeList[$key];
			$message .= "$key: $new (was $old)\n";
		}
		$this->load->helper('utilities');
		sendEmail('webmaster@ctc.org.nz', 'CTC website', 'dbadmin@ctc.org.nz', 'CTCDB: member update', $message);
	}


	// VALIDATION CODE
	// ===============

    // The rules for validating member fields
    // A suffix can be added to all field names (and labels) for handling
    // couple membership forms.
	function _setupMemberValidation($suffix = '')
	{
        $rules = array(
            array(
                'field' => "lastName{$suffix}",
                'label' => "Last Name{$suffix}",
                'rules' => 'required'),
            array(
                'field' => "firstName{$suffix}",
                'label' => "First Name{$suffix}",
                'rules' => 'required'),
            array(
                'field' => "primaryEmail{$suffix}",
                'label' => "Primary Email{$suffix}",
                'rules' => 'valid_email'),
            array(
                'field' => "loginName{$suffix}",
                'label' => "Login name{$suffix}",
                'rules' => 'required|min_length[4]|callback__loginCheck')
        );

		$this->form_validation->set_rules($rules);
	}
    
    // The rules for validating membership fields (as distinct from member fields)
    function _setupMembershipValidation()
	{
        $rules = array(
            array(
                'field' => "address1",
                'label' => 'Address Line 1',
                'rules' => 'required'),
            array(
                'field' => "city",
                'label' => 'City',
                'rules' => 'required')
        );

		$this->form_validation->set_rules($rules);
	}

	function _loginCheck($login)
	// Checks if the given (new) login is valid for the current user id (field of $this).
	{
        $result = $this->Ctcmodel->isValidLogin($login, $this->currentMemberId);
		if ($result !== TRUE) {
			$this->form_validation->set_message('_loginCheck', $result);
			return FALSE;
		}
		else {
			return TRUE;
		}
	}

}

?>
