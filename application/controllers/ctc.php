<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

define("NO_MENU", False); // Parameter value for "$menuReqd param to _loadPage


class Ctc extends MY_Controller {
	// This class is the main controller for all member/membership functions
	// used by the club database administrators to manage membership data.
	// These functions are accessible only to club officers whose roles
	// are listed in the "full_access_roles" array in config.php.
	//TODO Add credit transfers to new memberships when coupling/decoupling.
	var $currentMemberId;

	public function __construct()
	{
		global $userData;
		if (!$userData['hasFullAccess']) {
			die("Sorry, you don't have access to that function");
		}
		parent::__construct();

		$this->load->database();
		$this->load->helper(array('url','form','date','pageload'));
		$this->load->library('form_validation');
		$this->load->model('Ctcmodel');
		$this->form_validation->set_error_delimiters('<p style="font-size: 18px; color: red">', '</p>');
		$this->currentMemberId = -1;
	}

	public function newMember()
	{
		$this->currentMemberId = -1;
		$this->_setupMemberValidation();
        $this->_setupMembershipValidation();
		if ($this->form_validation->run()) {
			$result = $this->Ctcmodel->insertMember();
			if ($result) {

				$this->_loadPage('operationOutcome', 'CTCDB: New member successfully added');
			}
			else {
				$this->_loadPage('newMemberFailure', 'CTCDB: New member addition failed',
				array('tellWebmaster'=>True));
			}
			return;
		}

		$isPostBack = count($_POST) > 0;
		if ($isPostBack) {
			$templateData = $this->_getFormDataFromPost();
		}
		else {
			$templateData = $this->_getNewMemberFormData();
		}
		$membershipTypes = array();
		foreach ($this->Ctcmodel->getMembershipTypes() as $type) {
			if (!preg_match('/(Life)|(Couple)/', $type)) {  // Shouldn't use this form for couples or lifers
				array_push($membershipTypes, $type);
			}
		}
		if ($isPostBack) {
			$templateData['membershipTypeEnum']['values'] = $membershipTypes;
		}
		else {
			$templateData = array_merge(array('membershipTypeEnum' => array('type'=>'enum', 'label'=>'Membership type',
			    'value'=>'Ordinary', 'values'=>$membershipTypes)), $templateData);
		}

		$this->_loadPage(array('newMemberHeader','memberForm'), 'CTCDB: New Member',
		array('postbackUrl' => "ctc/newMember", 'fields'=>$templateData));
	}

	public function newCouple()
	{
		$this->currentMemberId = -1;
        $this->_setupMemberValidation('__1');
        $this->_setupMemberValidation('__2');
        $this->_setupMembershipValidation();
        $this->form_validation->set_rules('loginName__2', 'second login name', 'callback__differentLogins');

		if ($this->form_validation->run()) {
			$result = $this->Ctcmodel->insertCouple();
			if ($result) {
				$this->_loadPage('operationOutcome', 'CTCDB: New Couple Successfully Added');
			}
			else {
				$this->_loadPage('operationOutcome', 'CTCDB: New Couple Addition Failed',
				array('tellWebmaster'=>True));
			}
			return;
		}
		// If it's not a valid postback, we must load or reload the form
		$isPostBack = count($_POST) > 0;
		if ($isPostBack) {
			$data = $this->_getFormDataFromPost();
            unset($data['submitButton']);
		}
		else {
			$data = $this->_getNewCoupleFormData();
		}

		$this->_loadPage('newCouple', 'CTCDB: New Couple', array('fields'=>$data));
	}

	/**
	 * Set a new password for a member. Admin capability to help out those who
	 * can't use the "Forgotten password' capability on the home page.
	 */
	public function setPassword()
	{
		$data = array('memberList' => $this->Ctcmodel->getAllMembersForPasswordSetting());
		$this->_loadPage('selectMemberForPasswordSetting', 'CTCDB: Select Member', $data);
	}

	/*
	 * Callback from the resetPassword member selection form.
	 */
	public function setParticularMemberPassword($id = 0)
	{
		global $userData;
		if ($id == 0) {  // Security check (weak -- TODO: can this be improved?)
			die("Access denied 4");
		}
		$this->form_validation->set_rules('newpass', 'New Password', 'trim|required|min_length[5]|matches[newpassconf]');
		$this->form_validation->set_rules('newpassconf', 'Confirm New Password', 'trim|required');

		if ($this->form_validation->run()) {
			$this->Ctcmodel->setMemberPasswordRaw($id, $_POST['newpass']);
			$this->_loadPage('operationOutcome', "Password changed",
			array( 'message' => 'The user\'s password has been changed.'));
		}
		else {
			$this->_loadPage('passwordChangeForm2','Password change',
			array('name' => $this->Ctcmodel->getMemberName($id),
					  'postbackUrl'=>"ctc/setParticularMemberPassword/$id"));
		}
	}

	/**
	 * View/edit member details
	 */
	public function editMember()
	{
		$data = array('memberList' => $this->Ctcmodel->getAllMembersForViewEdit());
		$this->_loadPage('showmembers', 'CTCDB: Show Members', $data);
	}

	/**
	 * Edit the member whose id is given
	 * This is the callback from the View/Edit members screen
	 */
	public function editParticularMember($id)
	{
		$this->currentMemberId = $id;

		$this->_setupMemberValidation();
		if ($this->form_validation->run()) {
			// If we've received a valid form, update the DB and report the result.
			$changes = $this->Ctcmodel->updateMember($id);
			if ($changes === Null) {
				$this->_loadPage('operationOutcome', 'CTCDB: Member Update Failure',
				array('tellWebmaster' => True));
			}
			else if (count($changes) > 0)  {
				$this->_loadPage('operationOutcome', 'CTCDB: Member Update Done');
			}
			else {
				$this->_loadPage('operationOutcome', 'CTCDB: Member Update No Change',
				array('extraInfo' => 'The member update operation was completed with no errors but did not ' .
		    			' actually alter the information stored in the database!' .
		    			' Are you sure you actually changed the data in the form?'));
			}
			return;
		}
		// If we haven't got a valid form, then we must (re)load the update member form

		$isPostBack = count($_POST) > 0;
		if ($isPostBack) {
			$fields = $this->_getFormDataFromPost();  // Get the set of fields from the posted form
		}
		else {
			$fields = $this->_getUpdateFormDataFromDb($id);	// Else build a new form
		}

		// Have to load the possible values for the membershipType update. This is
		// constrained according to whether the current membership is a couple or not.
		$currentType = $fields['membershipTypeEnum']['value'];
		if (preg_match('/.*Couple/', $currentType)) {
			$allowable = array('Couple','AssociateCouple','LifeCouple');
		}
		else /* Not a couple membership */ {
			$types = $this->Ctcmodel->getMembershipTypes();
			$allowable = array();
			foreach ($types as $type) {
				if (!preg_match('/.*Couple/', $type)) { // Can't make non-couple into a couple
					array_push($allowable, $type);
				}
			}
		}
		$fields['membershipTypeEnum']['values'] = $allowable;
		$data = array('fields'=>$fields, 'postbackUrl'=> "ctc/editParticularMember/$id");
		$this->_loadPage(array('memberUpdateHeader','memberForm'), 'CTCDB: Edit Member', $data);
	}

	public function coupleMembers()
	// Change membership of two existing members to a new Couple membership
	{
		$isPostBack = count($_POST) > 0;
		$membershipFields = $this->Ctcmodel->getMembershipFields();
		if (!$isPostBack) {
			$coupleList = $this->Ctcmodel->getAllMembersForCoupling();
			$this->_loadPage('memberCouplingGetCouple', 'CTCDB: Get Couple', array('coupleList'=>$coupleList));
		}
		else { // postback from the couple selection form
			$ids = array();
			foreach (array_keys($_POST) as $key) {  /* Look for all the checked check boxes */
				if (preg_match('/cb[0-9]{1,5}/', $key)) {
					$id = substr($key,2);
					array_push($ids, $id);
				}
			}
			if (count($ids) != 2) {
				$this->_loadPage('operationOutcome', 'CTCDB: Wrong Coupling Count',
				array('extraInfo'=>'You must select just two members. No more, no less!'));
			}
			else {
				$id1 = $ids[0];
				$id2 = $ids[1];
				$membershipData = $this->Ctcmodel->getMembershipDataByMemberId($id1);
				$fields = array();
				foreach ($membershipFields as $fieldName) {
					$label = $this->_makeLabel($fieldName);
					$type = $this->_formFieldType($fieldName);
					$value = $membershipData[$fieldName];
					$fields[$fieldName] = array('type'=>$type, 'label'=>$label, 'value'=>$membershipData[$fieldName]);
				}
				$formData = array('fields' => $fields,
						'member1Id'=>$id1,
						'member2Id'=>$id2,
						'member1Name'=>$this->Ctcmodel->getMemberName($id1),
						'member2Name'=>$this->Ctcmodel->getMemberName($id2)
				);
				$this->_loadPage('memberCouplingGetData','CTCDB: Get Couple Data', $formData);
			}
		}
	}

	public function coupleMembers2($id1, $id2)
	// Postback from the coupling-data acquisition form
	{
        $this->_setupMembershipValidation();
		if(!$this->form_validation->run()) {
			$fields = $this->_getFormDataFromPost();
			$formData = array('fields' => $fields,
						'member1Id'=>$id1,
						'member2Id'=>$id2,
						'member1Name'=>$this->Ctcmodel->getMemberName($id1),
						'member2Name'=>$this->Ctcmodel->getMemberName($id2)
			);
			$this->_loadPage('memberCouplingGetData','CTCDB: Get Couple Data', $formData);
		}
		else {
			$membershipFields = $this->Ctcmodel->getMembershipFields();
			$fields = array();
			foreach ($membershipFields as $field) {
				$fields[$field] = $this->input->post($field, True);
			}
			if ($this->Ctcmodel->coupleMembers($id1, $id2, $fields)) {
				$this->_loadPage('operationOutcome', 'CTCDB: Members Successfully Coupled!');
			}
			else {
				$this->_loadPage('operationOutcome', 'CTCDB: Coupling Failure',
				array('tellWebmaster'=>True));
			}
		}

	}

	public function decoupleMembers($membershipId = NULL, $phase = 0)
	// Break a couple membership (which is shared by two members) into
	// two separate independent membership, each of which is a clone of
	// the original.
	// TODO: consider issue of decoupling a LifeCouple of AssociateCouple membership.
	{
		if ($membershipId === NULL) {  // If direct from menu
			$this->_loadPage('decouple', 'CTCDB: Decouple');
		}
		else if ($phase == 0) {  // Here via couple-selection page
			$this->_loadPage('decoupleConfirm', 'CTCDB: Confirm Decouple',
			array('membershipId' => $membershipId,
				'coupleName' => $this->Ctcmodel->getMembershipName($membershipId)));
		}
		else { // Here after confirmation
			if ($this->Ctcmodel->decouple($membershipId)) {
				$this->_loadPage('operationOutcome', 'CTCDB: Decouple Success');
			}
			else {
				$this->_loadPage('operationOutcome', 'CTCDB: Decouple Failure',
				array('tellWebmaster' => True));
			}
		}
	}

	public function closeMembership($membershipId = 0)
	// Close the given membership: 0 when called directly from menu.
	{
		if ($membershipId == 0) {
			function makeCloseLink($membershipId) {
				return anchor("ctc/closeMembership/$membershipId","Close");
			}
			$memberships = $this->Ctcmodel->getAllMembershipsForSelection('makeCloseLink',
				"statusAdmin = 'Active' or statusAdmin = 'Pending'");
			$this->_loadPage('closeMembership', 'CTCDB: Close Membership', array('memberships'=>$memberships));
		}
		else {
			$this->form_validation->set_rules('reason', 'Reason for closure', "callback__checkReason");
			$this->form_validation->set_rules('resignationDate', 'Resignation date', "callback__dateCheck2");
			$this->form_validation->set_rules('membershipNotes', 'Membership Notes', '');

			if ($this->form_validation->run()) {
				// Here on a valid postback for closing a membership
				$notes = $this->input->post('membershipNotes', True);
				$resignationDate = $this->input->post('resignationDate', True);
				if ($resignationDate == '') {
					$resignationDate = date('d-m-Y');
				}
				$reason = $this->input->post('reason', True);
				$this->Ctcmodel->closeMembership($membershipId, $reason, $resignationDate, $notes);
				$this->_loadPage('operationOutcome', 'CTCDB: Membership has been closed');
			}

			else {
				// First callback for a particular member, or an invalid callback. Load form.
				$msData = $this->Ctcmodel->getMembershipDataByMembershipId($membershipId);
				$data = array(
					'membershipId' => $membershipId,
					'membershipName' => $this->Ctcmodel->getMembershipName($membershipId),
					'membershipNotes' => $msData['membershipNotes']);
				$this->_loadPage('closeMembership2', 'CTCDB: Close Membership2', $data);
			}
		}
	}

	public function reinstateMembership($membershipId = 0)
	// This function reinstates a membership so that it maintains a continuous
	// uninterrupted term in good financial standing. Used e.g.
	// after a struck off member re-appears and pays all unpaid dues.
	// Note that both members of a couple membership are reinstated.
	// Essentially the closing of the membership is just undone, as if in error.
	{
		if ($membershipId == 0) {
			function makeReinstateLink($membershipId) {
				return anchor("ctc/reinstateMembership/$membershipId","Reinstate");
			}
			$memberships = $this->Ctcmodel->getAllMembershipsForSelection('makeReinstateLink',"(statusAdmin='StruckOff' or statusAdmin='Resigned')");
			$this->_loadPage('reinstateMembership', 'CTCDB: Reinstate Membership', array('memberships'=>$memberships));
		}
		else {
			$membershipName = $this->Ctcmodel->getMembershipName($membershipId);
			$status = $this->Ctcmodel->getMembershipStatusByMembershipId($membershipId);
			$this->_loadPage('reinstateConfirm', 'CTCDB: Confirm reinstatement',
			array('membershipId' => $membershipId, 'membershipName'=>$membershipName, 'status'=>$status));
		}
	}

	public function reinstateMembership2($membershipId)
	{
		$this->Ctcmodel->reinstateMembership($membershipId);
		$this->_loadPage('operationOutcome', 'CTCDB: Membership reinstatement complete');
	}

	public function rejoinMember($memberId = 0)
	// This function is used to rejoin a member who has been struck off some time ago
	// (and never pays the missing dues) or who has resigned.
	// A new membership record is opened in this case.
	{
		if ($memberId == 0) {
			function makeRejoinLink($membershipId) {
				return anchor("ctc/rejoinMember/$membershipId","Rejoin");
			}
			$members = $this->Ctcmodel->getAllMembersForSelection('makeRejoinLink',"(statusAdmin='StruckOff' or statusAdmin='Resigned')");
			$this->_loadPage('rejoinMember', 'CTCDB: Rejoin Members', array('members'=>$members));
		}
		else {
			$memberName = $this->Ctcmodel->getMemberName($memberId);
			$status = $this->Ctcmodel->getMemberStatus($memberId);
			$this->_loadPage('rejoinConfirm', 'CTCDB: Member rejoin confirmation',
			array('memberId' => $memberId, 'memberName'=>$memberName, 'status'=>$status));
		}
	}

	public function rejoinMember2($memberId)
	{
		$this->Ctcmodel->rejoinMember($memberId);
		$this->_loadPage('operationOutcome', 'CTCDB: Member rejoin done');
	}

	/**
	 * Add, remove or reassign club roles to/from members
	 */
	public function manageRoles($changes = array())
	{
		$this->_loadPage('manageRoles', 'CTCDB: Manage Member Roles',
		  array('roles' => $this->Ctcmodel->getRoles(),
			  'members' => $this->Ctcmodel->getActiveMembers(),
			  'currentRoles' => $this->Ctcmodel->getCurrentRoles(),
			  'changes' => $changes)
		);
	}

	/**
	 * Handle a submission of the main form from the manageRoles page
	 */
	public function setAllRoles()
	{
		$oldRoles = $this->Ctcmodel->getCurrentRoles();
		$changes = array();
		$rowNum = 1;
		foreach ($oldRoles as $row) {
			$cbName = "cb".$rowNum;
			$role = $row->role;
			$name = $row->name;
			$newMemberId = $this->input->post('member'.$rowNum);

			if ($newMemberId != 0 || $this->input->post($cbName)) {
				$this->Ctcmodel->deleteRole($row->memberId, $row->roleId);
				$changes[] = "Role of $role removed from $name";
			}
			if ($newMemberId != 0) {
				$this->Ctcmodel->addRole($newMemberId, $row->roleId);
				$name = $this->Ctcmodel->getMemberName($newMemberId);
				$changes[] = "Role of $role assigned to $name";
			}
			$rowNum++;
		}

		$this->manageRoles($changes);
	}


	/**
	 * Callback from manageRoles page when a role is added
	 */
	public function addRole()
	{
		$memberId = $this->input->post('member0');
		$roleId = $this->input->post('roleId');
		$data = array();
		if ($memberId != 0 && $roleId != 0) {
			$this->Ctcmodel->addRole($memberId, $roleId);
			$name = $this->Ctcmodel->getMemberName($memberId);
			$role = $this->Ctcmodel->getRole($roleId);
			$data = array("Role of $role assigned to $name");
		}
		$this->manageRoles($data);
	}

	public function hacking()
	// Just a hook to the hacking view, which is just there for me to
	// run random bits of code while developing
	{
		$this->_loadPage('hacking', 'Hacking');
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
                'field' => "secondaryEmail{$suffix}",
                'label' => "Secondary Email{$suffix}",
                'rules' => 'valid_email'),
            array(
                'field' => "loginName{$suffix}",
                'label' => "Login name{$suffix}",
                'rules' => 'required|min_length[4]|callback__loginCheck'),
            array(
                'field' => "dateJoined{$suffix}",
                'label' => "Date joined{$suffix}",
                'rules' => 'callback__dateCheck'),
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
                'rules' => 'required'),
            array(
                'field' => "membershipEmail",
                'label' => 'Membership Email',
                'rules' => 'valid_email')
        );

		$this->form_validation->set_rules($rules);
	}

	function _loginCheck($login)
	// Checks if the given (new) login contains only legitimate characters
	// and is valid for the current user id (field of $this)
	// (extracted from the current session) or valid for a new member if that id is -1.
	{
        $result = $this->Ctcmodel->isValidLogin($login, $this->currentMemberId);
        if ($result !== TRUE){
			$this->form_validation->set_message('_loginCheck', $result);
			return FALSE;
		} else {
			return TRUE;
		}
	}

	function _differentLogins($login)
	// Returns true iff the given login (namely loginName__2) isn't the same as 'loginName__1'
	{
		if ($login == $this->input->post('loginName__1', True)) {
			$this->form_validation->set_message('_differentLogins', 'The two members must have different logins');
			return FALSE;
		}
		else {
			return TRUE;
		}
	}

	function _dateCheck($date)
	// Checks if a date is in NZ standard form DD-MM-YYYY format (or, more generally, is
	// in any of the forms accepted by date_to_mysql).
	{
		if (date_to_mysql($date) !== NULL) {
			return TRUE;
		}
		else {
			$mess = 'Invalid date. Must be in form DD-MM-YYYY.';
			$this->form_validation->set_message('_dateCheck', $mess);
			return FALSE;
		}
	}

	function _dateCheck2($date)
	// Checks if a date is NULL or in NZ standard form DD-MM-YYYY format (or, more generally, is
	// in any of the forms accepted by date_to_mysql).
	// This version differs from the above in that it allows null dates, and is set up
	// for use with the new form_validation class, not the old one.
	{
		if ($date == NULL || date_to_mysql($date) !== NULL) {
			return TRUE;
		}
		else {
			$mess = "Invalid date. Must be empty (meaning 'today') or in form DD-MM-YYYY.";
			$this->form_validation->set_message('_dateCheck2', $mess);
			return FALSE;
		}
	}

	function _checkReason($reason) {
		$result = TRUE;
		if ($reason == "SelectOne") {
			$this->form_validation->set_message('_checkReason', 'You must select one of the reasons for closing this membership');
			$result = FALSE;
		}
		return $result;
	}

}