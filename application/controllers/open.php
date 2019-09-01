<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

// This controller contains functions are accessible to anyone, even if
// they're not logged into the main club website.

define("NO_MENU", False);

class Open extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(array('url','form','date','pageload'));
        $this->load->model('Ctcmodel');
        $this->load->model('Tripchangenotificationmodel');
        $this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('<p style="font-size: 18px; color: red">', '</p>');
    }
        
    public function forgottenUserName(){
        $this->_loadPage('forgottenUserName', "", array('css'=> "ctcdbNewWindow.css"), NO_MENU);
    }
    
    public function forgottenUserNameSubmit(){
        $searchData = $this->input->post("search_data");
        $recaptchaResponse = $this->input->post("captcha-validated");
        if ($recaptchaResponse !== "true")
            die("You need to confirm you are not a robot.");
        $memberData = $this->Ctcmodel->getMemberLoginNameFromEmailPhoneLoginName($searchData);
        $errorMessage = $memberData['errorMessage'];
        $mailSent = FALSE;
        if ($errorMessage !== ""){
            die($errorMessage);
        }else{
            $to = $memberData['emailAddress'];
            if ($to === "")
                die("User identified but no email address on record.");
            else{
              $subject = '[CTC]Your CTC login name';
              $message = "Hello,\n\nA login name reminder has been requested for your CTC account\n".
                         "Your login name is: ".$memberData['loginName']."\n".
                         "To login to your account, select the link below.\n\n".
                         config_item("base_url")."/index.php/log-in\n\n".
                         "Thank you.\n";
              $this->load->helper('utilities');
              // echo "Sending email from $userEmail ($name) to $to ($loginName), subject = $subject<br />";
              $mailSent = sendEmail("webmaster@ctc.org.nz", "Christchurch Tramping Club", $to, $subject, $message);
            }
        }
        if ($mailSent)
            echo("Login name has been sent to the email address on record.");
        else
            echo("Email send failed for some reason");
    }
        
    public function forgottenPassword(){
        $this->_loadPage('forgottenPassword', "", array('css'=> "ctcdbNewWindow.css"), NO_MENU);
    }
    
    public function forgottenPasswordSubmit(){
        $searchData = $this->input->post("search_data");
        $recaptchaResponse = $this->input->post("captcha-validated");
        if ($recaptchaResponse !== "true")
            die("You need to confirm you are not a robot.");
        $memberData = $this->Ctcmodel->getMemberLoginNameFromEmailPhoneLoginName($searchData);
        $errorMessage = $memberData['errorMessage'];
        $mailSent = FALSE;
        if ($errorMessage !== ""){
            die($errorMessage);
        }else{
            $to = $memberData['emailAddress'];
            if ($to === "")
                die("Your password was not changed. User was identified but had no email address on record.");
            else{
              $memberid = $memberData['id'];
              // Set new password
              $newPassword = $this->Ctcmodel->generatePassword($memberData['loginName']);
              $this->Ctcmodel->setMemberPasswordRaw($memberid, $newPassword);
              $subject = '[CTC]Your new CTC password';
              $message = "Hello,\n\nA password change has been requested for your CTC account\n".
                         "Your login name is: ".$memberData['loginName']."\n".
                         "Your new password is:".$newPassword."\n".
                         "To login to your account, select the link below.\n\n".
                         config_item("base_url")."/index.php/log-in\n\n".
                         "You should immediately change your new password to one you can remember.\n\n".
                         "Thank you.\n";
              $this->load->helper('utilities');
              // echo "Sending email from $userEmail ($name) to $to ($loginName), subject = $subject<br />";
              $mailSent = sendEmail("webmaster@ctc.org.nz", "Christchurch Tramping Club", $to, $subject, $message);
            }
        }
        if ($mailSent)
            echo("New password has been sent to the email address on record.");
        else
            echo("Your password was changed but the Email send failed for some reason. You may need to try the forgotten password process again.");
    }

    public function processMailQueue($maxRunTimeMins)
    // Send out any queued email messages from the mail_queue table.
    // This command is called via CRON every 15 minutes or so.
    // It will probably not run to completion, due to various timeouts.
    // LOCK TABLES is used on the mail queue maintain integrity of
    // the mail queue, batches and log tables. However, if multiple
    // calls to this method are extant at any time, all processes will
    // be sending out at the maximum mail rate, breaking the hostgator
    // throttle rate. So the time between polls should be at least
    // $maxRunTimeMins.
    {
        $this->Ctcmodel->lockMailQueue();
        $batches = $this->Ctcmodel->incompleteBatches();
        $nSent = 0;
        if (count($batches) > 0) {
            ignore_user_abort(True);
            set_time_limit(0);

            $timeToQuit = time() + $maxRunTimeMins * 60;
            $stallSeconds = 15;  // Seconds between emails to avoid being called a spammer
            $this->load->helper('utilities');

            $i = 0;
            while ($i < count($batches) && time() < $timeToQuit) {
                $batchId = $batches[$i];
                $mail = $this->Ctcmodel->getNextMailItem($batchId);

                $subject = '';
                while ($mail && time() < $timeToQuit) {
                    $subject = $mail->subject;
                    echo sendEmail($mail->from, 'Christchurch Tramping Club', $mail->to,
                                $subject, $mail->body);
                    $nSent++;
                    $this->Ctcmodel->logEmail($mail->to, $mail->batchId);
                    $this->Ctcmodel->deleteMailItem($mail->id);
                    $this->Ctcmodel->unlockMailQueue();
                    sleep($stallSeconds);
                    $this->Ctcmodel->lockMailQueue();
                    $mail = $this->Ctcmodel->getNextMailItem($batchId);
                }
                if ($mail === FALSE) {
                    $this->Ctcmodel->closeBatch($batchId);
                }
                $i++;
            }

            $this->Ctcmodel->purgeOldMailItems();  // Housekeeping
        }
        $this->Ctcmodel->unlockMailQueue();
        $this->_loadPage('operationOutcome', "Mail daemon done. $nSent emails sent.");
    }

    // This function handles incoming text messages from the 'send-sms-to-website'
    // service (http://www.send-sms-to-website.com/). Each text results
    // in a POST to this URL with parameters FROM (the phone number) and TEXT
    // (the message).
    // I have booked the keywords CTC and TRIP (which must be the first words
    // of the text message).
    // The message is displayed on the website via the Notify Trip Change
    // entry in the main site's Member's Menu, and a response text is sent
    // via http://websms.co.nz acknowledging receipt of the message.
    public function incomingtext() {
        $mob = $this->input->post('FROM');  // Originating mobile number
        $message = $this->input->post('TEXT');
        if ($mob && $message) {
            $username = "richard.lobb@canterbury.ac.nz";
            $pass = "mugglewump";
            $reply = "CTC trip notification received, thanks";
            $cellNum = $mob[0] == '+' ? substr($mob, 1) : $mob;
            $queryParams = array(
                    'username' => $username,
                    'password' => $pass,
                    'cellnum'  => $cellNum,
                    'message'  => $reply,
                    'premium'  => 1
                );
            $url = "http://websms.co.nz/api/send.php";
            $sep = '?';
            foreach ($queryParams as $key=>$value) {
                $url .= $sep . "$key=" . urlencode($value);
                $sep='&';
            }
            $ch = curl_init($url);
            curl_exec($ch);
            $curlErrorNum = curl_errno($ch);
            $curlError = curl_error($ch);
            curl_close($ch);

            $id = $this->Ctcmodel->getMemberIdFromMobileNum($mob);
            $name = $id ? $this->Ctcmodel->getMemberName($id) : '';
            $this->Tripchangenotificationmodel->insert($mob, $name, $message, $curlErrorNum,
                    $curlError);
        }
    }

    public function testNameFromMobile() {
        $testNums = array(
            '+64211191059',
            '0064 21 119 1059',
            '+ 6421-119-1059',
            '027-4046397',
            '0272709008',
            '021 1808956',
            '0275 244 225',
            '275 244 225',
            '0',
            '1234567',
            '03 351 2344',
            '3512344');
        $s = '<table><tr><th>Num</th><th>Name</th></tr>';
        foreach ($testNums as $num) {
            $id = $this->Ctcmodel->getMemberIdFromMobileNum($num);
            $name = $id ? $this->Ctcmodel->getMemberName($id) : '';
            $s .= "<tr><td>$num</td><td>$name</td></tr>";
        }
        $s .= '</table>';
        echo $s;
    }
    
    
    // ********************
    // TRIP REPORT SITE MAP
    // *********************
    // Return a page containing links in the "goto" format for all trip
    // reports in the database. Links are absolute, using the config parameter
    // joomla_base_url. This is to provide a pseudo site-map for use by
    // search engines.
    // This function is a bit of a hack, as it's not really
    // a rest API call at all - it returns a full web page.
    public function allTripReportLinks() {
        $this->db = $this->load->database('tripreports', true);
        $this->load->model('tripreportmodel');
        $this->load->helper('url');
        $allTrips = $this->tripreportmodel->getAllTripReports();
        $this->_loadPage('allTripReportLinks', 'All Trip Reports',
			array('trips' => $allTrips),
			NO_MENU
		);
    }


}



?>
