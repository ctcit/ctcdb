<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

// This controller contains functions available to club members logged into the
// joomla website. It ONLY ever allows the user to edit their own details
class Member extends BaseController
{
    public $currentMemberID;
    public $currentUserName;
    public $currentUserLogin;

    private const EMBEDDED = true;

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

        $this->currentMemberID = session()->userID;
        if ($this->currentMemberID == 0) {
            $ctcHome = config('Joomla')->baseURL;
            echo '<head><script language="javascript">top.location.href="'.$ctcHome.'";</script></head>';
            die('Not logged in.');
        }
        $this->currentUserLogin = session()->login;
        $this->currentUserName = session()->name;

        helper(['url','form','date','pageload']);
    }

    public function index()
    {
        return $this->userDetails();
    }

    // Function to display a menu of user-profile update options
    public function userDetails()
    {
        $baseURL = config('Joomla')->baseURL;

        return $this->loadPage('userDetailsOptions', 'User Details',
            $data = array(
                'changePasswordUrl' => "member/changePassword",
                'changeProfileUrl'=> "member/profile",
                'subsPaymentForm' => "member/subsForm",
                'membershipEmail' => "membership@ctc.org.nz",
                'css'=> "joomlaEmbedded.css"),
            self::EMBEDDED
        );
    }

    public function printableMembershipList()
    {
        $members = $this->ctcModel->getAllActiveMembers();
        return $this->loadPage('printableMembershipList','Membership list',
            array('members' => $members,
                  'surnameFirst' => true,
                  'css'=> "joomlaEmbedded.css"),
            self::EMBEDDED
        );
   }

    public function printableMembershipListByFirstName()
    {
        $members = $this->ctcModel->getAllActiveMembersByFirstName();
        return $this->loadPage('printableMembershipList','Membership list',
            array('members' => $members,
                  'surnameFirst' => false,
                  'css'=> "joomlaEmbedded.css"),
            self::EMBEDDED
        );
   }

    // Function to display the club membership list
    public function membershipList()
    {
        $printableListBySurnameUrl = "member/printableMembershipList";
        $printableListByFirstnameUrl = "member/printableMembershipListByFirstName";
        $membersBySurname = $this->ctcModel->getAllActiveMembers();
        $membersByFirstName = $this->ctcModel->getAllActiveMembersByFirstname();
        return $this->loadPage('membershipList','Membership list',
            array('membersBySurname'=>$membersBySurname,
                  'membersByFirstName'=>$membersByFirstName,
                  'css'=> "joomlaEmbedded.css",
                  'printableListBySurnameUrl'=>$printableListBySurnameUrl,
                  'printableListByFirstnameUrl'=>$printableListByFirstnameUrl),
            self::EMBEDDED
        );
    }

    // Function to allow a club member to update their profile.
    public function profile()
    {
        $isPostBack = count($_POST) > 0;
        if ($isPostBack) {
            if ($this->validate($this->profileValidationRules())) {
                // We've received a valid form, update the DB and report the result.
                return $this->handleValidUpdateForm();
            }
        }

        // Either no POST data yet or validation failed
        return $this->handleInvalidUpdateForm();
    }


    // Function to allow a club member to change their password.
    // TODO: rationalise password computation (which also exists in ctc_model).
    public function changePassword()
    {
        $this->currentPassword = $this->ctcModel->getMemberPassword($this->currentMemberID);

        $isPostBack = count($_POST) > 0;
        if ($isPostBack) {
            if ($this->validate($this->passwordValidationRules())) {
                $this->ctcModel->setMemberPasswordRaw($this->currentMemberID, $_POST['newpass']);
                return $this->loadPage('operationOutcome', "Password changed",
                        [ 'message' => 'Your password has been changed.',
                          'css'=> "joomlaEmbedded.css" ],
                        self::EMBEDDED
                );
            }
        }
        return $this->loadPage('passwordChangeForm','Password change',
                [ 'postbackUrl'=>"member/changePassword",
                'css'=> "joomlaEmbedded.css" ],
                self::EMBEDDED);
    }


    // Function to allow club members to view the subscription payment form
    public function subsForm()
    {
        helper('utilities');
        $year = getSubsYear();
        $query = $this->ctcModel->getMembershipPaymentStatus($year, $this->currentMemberID);
        if ($query->getNumRows() != 1) {
            return $this->loadPage("operationOutcome", "Oops.",
                array('tellWebmaster' => true),
                self::EMBEDDED);
        } else {
            $row = $query->getRow();
            return $this->loadPage('subsDetailsForm','Your Subscription Renewal Form',
                    array('name'	 => $row->MembershipName,
                          'msType'   => $row->Type,
                          'login' 	 => $row->Login,
                          'msid'  	 => $row->MSID,
                          'sub'  	 => $row->Fee,
                          'paid'  	 => $row->DatePaid != null,
                          'overdue'  => false,
                          'css'=> "joomlaEmbedded.css"),
                    self::EMBEDDED);
        }
    }


    // PROFILE UPDATE SUPPORT FUNCTIONS
    // ================================
    // Function to handle the receipt of a valid profile update form
    private function handleValidUpdateForm()
    {
        $changes = $this->ctcModel->updateMember($this->currentMemberID, $this->request, true);
        if ($changes === null) {
            return $this->loadPage('operationOutcome', "Update Failed",
                array(
                    'message' => 'CTCDB: Profile Update Failure',
                    'tellWebmaster' => True,
                    'css'=> "joomlaEmbedded.css"),
                self::EMBEDDED
            );
        } else if (count($changes) > 0) {
            $this->emailChanges($changes);
            return $this->loadPage('operationOutcome', "Successful Update",
                array(
                    'message' => 'Your profile has been successfully updated.',
                    'css'=> "joomlaEmbedded.css"),
                self::EMBEDDED
            );
        } else {
            return $this->loadPage('operationOutcome', "No change",
                    array(
                        'message' => 'CTCDB: Profile Update -- No Change',
                        'extraInfo' => 'The update of your profile was completed with no errors but did not ' .
                            ' actually alter the information stored in the database!' .
                            ' Are you sure you actually changed the data in the form?',
                        'css'=> "joomlaEmbedded.css"),
                    self::EMBEDDED
            );
        }
    }

    // Function to handle the receipt of an invalid profile update form.
    // (Re)loads form
    private function handleInvalidUpdateForm()
    {
        $isPostBack = count($_POST) > 0;
        if ($isPostBack) {
            // Get the set of fields from the posted form
            $fields = $this->getFormDataFromPost();
        } else {
	        // else build a new form
            $fields = $this->getProfileDataFromDb();
        }

        return $this->loadPage('memberProfileUpdateForm',
                         'Member Details Form',
                         array(
                            'fields'=>$fields,
                            'postbackUrl'=> "member/profile",
                            'css' => "joomlaEmbedded.css"),
                         self::EMBEDDED);
    }


    // Extract from the database all the information required to display correctly
    // the member update form for the logged in member (which includes
    // their current data values). Returns a set of form field definitions
    // as described in _getNewMemberFormData.
    private function getProfileDataFromDb()
    {
        $row = $this->ctcModel->getMemberDataByMemberID($this->currentMemberID);
        $memberFields = $this->ctcModel->getMemberProfileFields();
        $membershipFields = $this->ctcModel->getMembershipProfileFields();
        $allProfileFields = array_merge($memberFields , $membershipFields);

        $fields = array();
        foreach (array_keys($row) as $key) {
            if (in_array($key, $allProfileFields)) {
                $label = $this->makeLabel($key);
                $type = $this->formFieldType($key);
                $values = $this->formFieldValues($key);
                $field = array('type'=>$type, 'label' => $label, 'value' => $row[$key], 'values' => $values);
                $fields[$key] = $field;
            }
        }
        $partner = $this->ctcModel->getPartnerName($this->currentMemberID);
        if ($partner == '') $partner = "N/A";
        $fields['statusAdminHidden'] = array('type'=>'hidden', 'label'=>'', 'value'=>$row['statusAdmin']);
        $fields['partnerHidden'] = array('type'=>'hidden', 'label' =>'', 'value'=>$partner);
        $fields['memberNameHidden'] = array('type'=>'hidden', 'label' =>'', 'value'=> $row['firstName'].' '.$row['lastName']);
        return $fields;
    }

    // Generate an email to dbadmin@ctc.org.nz with a list of the changes
    // made by the given userID to their member & membership data.
    private function emailChanges($changeList)
    {
        $message = "User {$this->currentUserName}, logged in as {$this->currentUserLogin}, has made the following changes.\n\n";
        foreach (array_keys($changeList) as $key) {
            list($old, $new) = $changeList[$key];
            $message .= "$key: $new (was $old)\n";
        }
        helper('utilities');
        sendEmail('webmaster@ctc.org.nz', 'CTC website', 'dbadmin@ctc.org.nz', 'CTCDB: member update', $message);
    }


    // VALIDATION CODE
    // ===============

    // The rules for validating member & membership fields
    private function profileValidationRules()
    {
        return [
            "lastName" =>
            [
                'label' => "Last Name",
                'rules' => 'required'
            ],
            "firstName" =>
            [
                'label' => "First Name",
                'rules' => 'required'
            ],
            "primaryEmail" =>
            [
                'label' => "Primary Email",
                'rules' => 'valid_email',
            ],
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
        ];
    }

    private function passwordValidationRules()
    {
        return [
            'currentpass' =>
            [
                'label' => 'Current Password',
                'rules' => "trim|required|password_is_correct[$this->currentMemberID]"
            ],
            'newpass' =>
            [
                'label' => 'New Password',
                'rules' => 'trim|required|min_length[5]|matches[newpassconf]'
            ],
            'newpassconf' =>
            [
                'label' => 'New Password Confirmation',
                'rules' => 'trim|required'
            ]
        ];
    }
}

?>