<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

// This class is the main controller for all member/membership functions
// used by the club database administrators to manage membership data.
// These functions are accessible only to club officers whose roles
// are listed in the "full_access_roles" array in config.php.
//TODO Add credit transfers to new memberships when coupling/decoupling.
class CTC extends BaseController
{
    public $currentMemberID;

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

        if (!session()->hasFullAccess) {
            die('You must be a club officer logged in to the website to use this function!!!');
        }

        helper(['url','form','date','pageload']);

        $this->currentMemberID = -1;
    }

    public function newMember()
    {
        $this->currentMemberID = -1;
        $rules = array_merge($this->memberValidationRules($this->currentMemberID),
                             $this->membershipValidationRules());
        $isPostBack = count($_POST) > 0;
        if ($isPostBack) {
            if ($this->validate($rules)) {
                $result = $this->ctcModel->insertMember($this->request);
                if ($result) {
                    return $this->loadPage('operationOutcome', 'CTCDB: New member successfully added');
                } else {
                    return $this->loadPage('newMemberFailure', 'CTCDB: New member addition failed',
                        array('tellWebmaster'=>True));
                }
            }
            // Failed validation - continue to re-show the data and validation results
            $templateData = $this->getFormDataFromPost();
        } else {
            // No Post data - show empty form
            $templateData = $this->getNewMemberFormData();
        }
        $membershipTypes = array();
        foreach ($this->ctcModel->getMembershipTypes() as $type) {
            if (!preg_match('/(Life)|(Couple)/', $type)) {  // Shouldn't use this form for couples or lifers
                array_push($membershipTypes, $type);
            }
        }
        if ($isPostBack) {
            $templateData['membershipTypeEnum']['values'] = $membershipTypes;
        } else {
            $templateData = array_merge(array('membershipTypeEnum' => array('type'=>'enum', 'label'=>'Membership type',
                'value'=>'Ordinary', 'values'=>$membershipTypes)), $templateData);
        }

        return $this->loadPage(array('newMemberHeader','memberForm'), 'CTCDB: New Member',
            array('postbackUrl' => "ctc/newMember", 'fields'=>$templateData));
    }

    public function newCouple()
    {
        $this->currentMemberID = -1;
        $rules = array_merge(
            $this->memberValidationRules($this->currentMemberID, '__1'),
            $this->memberValidationRules($this->currentMemberID, '__2'),
            $this->membershipValidationRules()
        );
        $rules['loginName__2'] = ['label' => 'Second Login Name', 'rules' => 'differs[loginName__1]'];

        if ($this->validate($rules)) {
            $result = $this->ctcModel->insertCouple($this->request);
            if ($result) {
                $page = $this->loadPage('operationOutcome', 'CTCDB: New Couple Successfully Added');
            } else {
                $page = $this->loadPage('operationOutcome', 'CTCDB: New Couple Addition Failed',
                                       array('tellWebmaster'=>True));
            }
        } else {
            // If it's not a valid postback, we must load or reload the form
            $isPostBack = count($_POST) > 0;
            if ($isPostBack) {
                $data = $this->getFormDataFromPost();
                unset($data['submitButton']);
            } else {
                $data = $this->getNewCoupleFormData();
            }
            $page = $this->loadPage('newCouple', 'CTCDB: New Couple', array('fields'=>$data));
        }
        return $page;
    }

    /**
     * Set a new password for a member. Admin capability to help out those who
     * can't use the "Forgotten password' capability on the home page.
     */
    public function setPassword()
    {
        $data = array('memberList' => $this->ctcModel->getAllMembersForPasswordSetting());
        return $this->loadPage('selectMemberForPasswordSetting', 'CTCDB: Select Member', $data);
    }

    /*
     * Callback from the resetPassword member selection form.
     */
    public function setParticularMemberPassword($id = 0)
    {
        $rules = [
            'newpass' => ['label' => 'New Password', 'rules' => 'trim|required|min_length[5]|matches[newpassconf]'],
            'newpassconf' => ['label' => 'Confirm New Password', 'rules' => 'trim|required']
        ];
        $isPostBack = count($_POST) > 0;
        if ($isPostBack) {
            if ($this->validate($rules)) {
                $this->ctcModel->setMemberPasswordRaw($id, $_POST['newpass']);
                return $this->loadPage('operationOutcome', "Password changed",
                    array( 'message' => 'The user\'s password has been changed.'));
            }
        }
        // Either failed validation or Post hasn't been sent yet
        return $this->loadPage('passwordChangeForm2','Password change',
            array('name' => $this->ctcModel->getMemberName($id),
                'postbackUrl'=>"ctc/setParticularMemberPassword/$id"));
    }

    /**
     * View/edit member details
     */
    public function editMember()
    {
        $data = array('memberList' => $this->ctcModel->getAllMembersForViewEdit());
        return $this->loadPage('show_members', 'CTCDB: Show Members', $data);
    }

    /**
     * Edit the member whose id is given
     * This is the callback from the View/Edit members screen
     */
    public function editParticularMember($id)
    {
        $this->currentMemberID = $id;

        $isPostBack = count($_POST) > 0;
        if ($isPostBack) {
            if ($this->validate($this->memberValidationRules($this->currentMemberID))) {
                // If we've received a valid form, update the DB and report the result.
                $changes = $this->ctcModel->updateMember($id, $this->request);
                if ($changes === null) {
                    return $this->loadPage('operationOutcome', 'CTCDB: Member Update Failure',
                    array('tellWebmaster' => true));
                } else if (count($changes) > 0) {
                    return $this->loadPage('operationOutcome', 'CTCDB: Member Update Done');
                } else {
                    return $this->loadPage('operationOutcome', 'CTCDB: Member Update No Change',
                    array('extraInfo' => 'The member update operation was completed with no errors but did not ' .
                            ' actually alter the information stored in the database!' .
                            ' Are you sure you actually changed the data in the form?'));
                }
                return;
            }
            // Validation failed, so we must (re)load the update member form
            // Get the set of fields from the posted form
            $fields = $this->getFormDataFromPost();
        } else {
            // Build new form from database values
            $fields = $this->getUpdateFormDataFromDb($id);
        }

        // Have to load the possible values for the membershipType update. This is
        // constrained according to whether the current membership is a couple or not.
        $currentType = $fields['membershipTypeEnum']['value'];
        if (preg_match('/.*Couple/', $currentType)) {
            $allowable = array('Couple','AssociateCouple','LifeCouple');
        } else /* Not a couple membership */ {
            $types = $this->ctcModel->getMembershipTypes();
            $allowable = array();
            foreach ($types as $type) {
                if (!preg_match('/.*Couple/', $type)) { // Can't make non-couple into a couple
                    array_push($allowable, $type);
                }
            }
        }
        $fields['membershipTypeEnum']['values'] = $allowable;
        $data = array('fields'=>$fields, 'postbackUrl'=> "ctc/editParticularMember/$id");
        return $this->loadPage(array('memberUpdateHeader','memberForm'), 'CTCDB: Edit Member', $data);
    }

    // Change membership of two existing members to a new Couple membership
    public function coupleMembers()
    {
        $isPostBack = count($_POST) > 0;
        $membershipFields = $this->ctcModel->getMembershipFields();
        if (!$isPostBack) {
            $coupleList = $this->ctcModel->getAllMembersForCoupling();
            return $this->loadPage('memberCouplingGetCouple', 'CTCDB: Get Couple', array('coupleList'=>$coupleList));
        } else { // postback from the couple selection form
            $ids = array();
            foreach (array_keys($_POST) as $key) {  /* Look for all the checked check boxes */
                if (preg_match('/cb[0-9]{1,5}/', $key)) {
                    $id = substr($key,2);
                    array_push($ids, $id);
                }
            }
            if (count($ids) != 2) {
                return $this->loadPage('operationOutcome', 'CTCDB: Wrong Coupling Count',
                array('extraInfo'=>'You must select just two members. No more, no less!'));
            } else {
                $id1 = $ids[0];
                $id2 = $ids[1];
                $membershipData = $this->ctcModel->getMembershipDataByMemberId($id1);
                $fields = array();
                foreach ($membershipFields as $fieldName) {
                    $label = $this->makeLabel($fieldName);
                    $type = $this->formFieldType($fieldName);
                    $value = $membershipData[$fieldName];
                    $fields[$fieldName] = array('type'=>$type, 'label'=>$label, 'value'=>$membershipData[$fieldName]);
                }
                $formData = array('fields' => $fields,
                        'member1Id'=>$id1,
                        'member2Id'=>$id2,
                        'member1Name'=>$this->ctcModel->getMemberName($id1),
                        'member2Name'=>$this->ctcModel->getMemberName($id2)
                );
                return $this->loadPage('memberCouplingGetData','CTCDB: Get Couple Data', $formData);
            }
        }
    }

    // Postback from the coupling-data acquisition form
    public function coupleMembers2($id1, $id2)
    {
        if (!$this->validate($this->membershipValidationRules())) {
            $fields = $this->getFormDataFromPost();
            $formData = array('fields' => $fields,
                        'member1Id'=>$id1,
                        'member2Id'=>$id2,
                        'member1Name'=>$this->ctcModel->getMemberName($id1),
                        'member2Name'=>$this->ctcModel->getMemberName($id2)
            );
            return $this->loadPage('memberCouplingGetData','CTCDB: Get Couple Data', $formData);
        } else {
            $membershipFields = $this->ctcModel->getMembershipFields();
            $fields = array();
            foreach ($membershipFields as $field) {
                $fields[$field] = $this->request->getPost($field);
            }
            if ($this->ctcModel->coupleMembers($id1, $id2, $fields)) {
                return $this->loadPage('operationOutcome', 'CTCDB: Members Successfully Coupled!');
            }
            else {
                return $this->loadPage('operationOutcome', 'CTCDB: Coupling Failure',
                array('tellWebmaster'=>True));
            }
        }
    }

    // Break a couple membership (which is shared by two members) into
    // two separate independent membership, each of which is a clone of
    // the original.
    // TODO: consider issue of decoupling a LifeCouple of AssociateCouple membership.
    public function decoupleMembers($membershipId = NULL, $phase = 0)
    {
        if ($membershipId === null) {
            // If direct from menu
            return $this->loadPage('decouple', 'CTCDB: Decouple',
                                   ['couples' => $this->ctcModel->getAllCouplesForDecoupling()]);
        } else if ($phase == 0) {
            // Here via couple-selection page
            $data = [ 'membershipId' => $membershipId,
                      'coupleName' => $this->ctcModel->getMembershipName($membershipId) ];
            return $this->loadPage('decoupleConfirm', 'CTCDB: Confirm Decouple', $data);
        } else {
            // Here after confirmation
            if ($this->ctcModel->decouple($membershipId)) {
                return $this->loadPage('operationOutcome', 'CTCDB: Decouple Success');
            } else {
                return $this->loadPage('operationOutcome', 'CTCDB: Decouple Failure',
                                       ['tellWebmaster' => true]);
            }
        }
    }

    // Close the given membership: 0 when called directly from menu.
    public function closeMembership($membershipId = 0)
    {
        if ($membershipId == 0) {
            $makeCloseLink = function ($membershipId)
            {
                return anchor("ctc/closeMembership/$membershipId","Close");
            };
            $memberships = $this->ctcModel->getAllMembershipsForSelection($makeCloseLink,
                "statusAdmin = 'Active' or statusAdmin = 'Pending'");
            return $this->loadPage('closeMembership', 'CTCDB: Close Membership', array('memberships'=>$memberships));
        } else {
            $isPostBack = count($_POST) > 0;
            if ($isPostBack)
            {
                $rules = [
                    'reason'  => ['label' => 'Reason for closure', 'rules' => 'checkReason'],
                    'resignationDate'  => ['label' => 'Resignation Date', 'rules' => 'dateCheck2'],
                ];

                if ($this->validate($rules)) {
                    // Here on a valid postback for closing a membership
                    $notes = $this->request->getPost('membershipNotes');
                    $resignationDate = $this->request->getPost('resignationDate');
                    if ($resignationDate == '') {
                        $resignationDate = date('d-m-Y');
                    }
                    $reason = $this->request->getPost('reason');
                    $this->ctcModel->closeMembership($membershipId, $reason, $resignationDate, $notes);
                    return $this->loadPage('operationOutcome', 'CTCDB: Membership has been closed');
                }
            }
            // First callback for a particular member, or an invalid callback. Load form.
            $msData = $this->ctcModel->getMembershipDataByMembershipId($membershipId);
            $data = array(
                'membershipId' => $membershipId,
                'membershipName' => $this->ctcModel->getMembershipName($membershipId),
                'membershipNotes' => $msData['membershipNotes']);
            return $this->loadPage('closeMembership2', 'CTCDB: Close Membership2', $data);
        }
    }

    // This function reinstates a membership so that it maintains a continuous
    // uninterrupted term in good financial standing. Used e.g.
    // after a struck off member re-appears and pays all unpaid dues.
    // Note that both members of a couple membership are reinstated.
    // Essentially the closing of the membership is just undone, as if in error.
    public function reinstateMembership($membershipId = 0)
    {
        if ($membershipId == 0) {
            $makeReinstateLink = function ($membershipId)
            {
                return anchor("ctc/reinstateMembership/$membershipId","Reinstate");
            };
            $memberships = $this->ctcModel->getAllMembershipsForSelection(
                    $makeReinstateLink,"(statusAdmin='StruckOff' or statusAdmin='Resigned' or statusAdmin='Deceased')");
            return $this->loadPage('reinstateMembership', 'CTCDB: Reinstate Membership', array('memberships'=>$memberships));
        } else {
            $membershipName = $this->ctcModel->getMembershipName($membershipId);
            $status = $this->ctcModel->getMembershipStatusByMembershipId($membershipId);
            return $this->loadPage('reinstateConfirm', 'CTCDB: Confirm reinstatement',
                array('membershipId' => $membershipId, 'membershipName'=>$membershipName, 'status'=>$status));
        }
    }

    public function reinstateMembership2($membershipId)
    {
        $this->ctcModel->reinstateMembership($membershipId);
        return $this->loadPage('operationOutcome', 'CTCDB: Membership reinstatement complete');
    }

    // This function is used to rejoin a member who has been struck off some time ago
    // (and never pays the missing dues) or who has resigned.
    // A new membership record is opened in this case.
    public function rejoinMember($memberID = 0)
    {
        if ($memberID == 0) {
            $makeRejoinLink = function ($membershipId)
            {
                return anchor("ctc/rejoinMember/$membershipId","Rejoin");
            };
            $members = $this->ctcModel->getAllMembersForSelection($makeRejoinLink,"(statusAdmin='StruckOff' or statusAdmin='Resigned')");
            return $this->loadPage('rejoinMember', 'CTCDB: Rejoin Members', array('members'=>$members));
        } else {
            $memberName = $this->ctcModel->getMemberName($memberID);
            $status = $this->ctcModel->getMemberStatus($memberID);
            return $this->loadPage('rejoinConfirm', 'CTCDB: Member rejoin confirmation',
                array('memberId'=>$memberID, 'memberName'=>$memberName, 'status'=>$status));
        }
    }

    public function rejoinMember2($memberID)
    {
        $this->ctcModel->rejoinMember($memberID);
        return $this->loadPage('operationOutcome', 'CTCDB: Member rejoin done');
    }

    /**
     * Add, remove or reassign club roles to/from members
     */
    public function manageRoles($changes = array())
    {
        return $this->loadPage('manageRoles', 'CTCDB: Manage Member Roles',
          array('roles' => $this->ctcModel->getRoles(),
              'members' => $this->ctcModel->getActiveMembers(),
              'currentRoles' => $this->ctcModel->getCurrentRoles(),
              'changes' => $changes)
        );
    }

    /**
     * Handle a submission of the main form from the manageRoles page
     */
    public function setAllRoles()
    {
        $oldRoles = $this->ctcModel->getCurrentRoles();
        $changes = array();
        $rowNum = 1;
        foreach ($oldRoles as $row) {
            $cbName = "cb".$rowNum;
            $role = $row->role;
            $name = $row->name;
            $newMemberID = $this->request->getPost('member'.$rowNum);

            if ($newMemberID != 0 || $this->request->getPost($cbName)) {
                $this->ctcModel->deleteRole($row->memberID, $row->roleID);
                $changes[] = "Role of $role removed from $name";
            }
            if ($newMemberID != 0) {
                $this->ctcModel->addRole($newMemberID, $row->roleID);
                $name = $this->ctcModel->getMemberName($newMemberID);
                $changes[] = "Role of $role assigned to $name";
            }
            $rowNum++;
        }

        return $this->manageRoles($changes);
    }


    /**
     * Callback from manageRoles page when a role is added
     */
    public function addRole()
    {
        $memberID = $this->request->getPost('member0');
        $roleID = $this->request->getPost('roleId');
        $data = array();
        if ($memberID != 0 && $roleID != 0) {
            $this->ctcModel->addRole($memberID, $roleID);
            $name = $this->ctcModel->getMemberName($memberID);
            $role = $this->ctcModel->getRole($roleID);
            $data = array("Role of $role assigned to $name");
        }
        return $this->manageRoles($data);
    }

    // Just a hook to the hacking view, which is just there for me to
    // run random bits of code while developing
    public function hacking()
    {
        return $this->loadPage('hacking', 'Hacking');
    }

    // VALIDATION CODE
    // ===============

    // The rules for validating member fields
    // A suffix can be added to all field names (and labels) for handling
    // couple membership forms.
    private function memberValidationRules($currentMemberID, $suffix = '' )
    {
        return [
            "lastName{$suffix}" =>
            [
                'label' => "Last Name{$suffix}",
                'rules' => 'required'
            ],
            "firstName{$suffix}" =>
            [
                'label' => "First Name{$suffix}",
                'rules' => 'required'
            ],
            "primaryEmail{$suffix}" =>
            [
                'label' => "Primary Email{$suffix}",
                'rules' => 'valid_email',
            ],
            "secondaryEmail{$suffix}" =>
            [
                'label' => "Secondary Email{$suffix}",
                'rules' => 'valid_email_or_empty',
            ],
            "loginName{$suffix}" =>
            [
                'label' => "Login name{$suffix}",
                'rules' => "required|min_length[4]|loginCheck[$currentMemberID]",
            ],
            "dateJoined{$suffix}" =>
            [
                'label' => "Date joined{$suffix}",
                'rules' => 'dateCheck'
            ]
        ];
    }

    // The rules for validating membership fields (as distinct from member fields)
    private function membershipValidationRules()
    {
        return [
            "address1" =>
            [
                'label' => 'Address Line 1',
                'rules' => 'required'
            ],
            "city" =>
            [
                'label' => 'City',
                'rules' => 'required'
            ],
            "membershipEmail" =>
            [
                'label' => 'Membership Email',
                'rules' => 'valid_email'
            ]
        ];
    }
}
