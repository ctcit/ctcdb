<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

// This controller contains functions are accessible to anyone, even if
// they're not logged into the main club website.
class Open extends BaseController
{
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

        helper(['url','form','date','pageload']);
    }

    public function forgottenUserName()
    {
        return $this->loadPage('forgottenUserName', "",
                               array('postbackUrl' => "open/forgottenUserNameSubmit",
                                     'css'=> "joomlaEmbedded.css"),
                               self::EMBEDDED);
    }

    public function forgottenUserNameSubmit()
    {
        $searchData = $this->request->getPost("search_data");
        $recaptchaResponse = $this->request->getPost("captcha-validated");
        if ($recaptchaResponse !== "true") {
            die("You need to confirm you are not a robot.");
        }
        $memberData = model('CTCModel')->getMemberLoginNameFromEmailPhoneLoginName($searchData);
        $errorMessage = $memberData['errorMessage'];
        $mailSent = false;
        if ($errorMessage !== "") {
            die($errorMessage);
        } else {
            $to = $memberData['emailAddress'];
            if ($to === "") {
                die("User identified but no email address on record.");
            } else {
                $subject = '[CTC]Your CTC login name';
                $message = "Hello,\n\nA login name reminder has been requested for your CTC account\n".
                            "Your login name is: ".$memberData['loginName']."\n".
                            "To login to your account, select the link below.\n\n".
                            config('Joomla')->baseURL."/index.php/log-in\n\n".
                            "Thank you.\n";
                helper('utilities');
                $mailSent = sendEmail("webmaster@ctc.org.nz", "Christchurch Tramping Club", $to, $subject, $message);
            }
        }
        if ($mailSent) {
            echo("Login name has been sent to the email address on record.");
        } else {
            echo("Email send failed for some reason");
        }
    }

    public function forgottenPassword()
    {
        return $this->loadPage('forgottenPassword', "",
                               array('postbackUrl' => "open/forgottenPasswordSubmit",
                               'css'=> "joomlaEmbedded.css"), self::EMBEDDED);
    }

    public function forgottenPasswordSubmit()
    {
        $searchData = $this->request->getPost("search_data");
        $recaptchaResponse = $this->request->getPost("captcha-validated");
        if ($recaptchaResponse !== "true") {
            die("You need to confirm you are not a robot.");
        }
        $memberData = model('CTCModel')->getMemberLoginNameFromEmailPhoneLoginName($searchData);
        $errorMessage = $memberData['errorMessage'];
        $mailSent = false;
        if ($errorMessage !== "") {
            die($errorMessage);
        } else {
            $to = $memberData['emailAddress'];
            if ($to === "") {
                die("Your password was not changed. User was identified but had no email address on record.");
            } else {
                $memberid = $memberData['id'];
                // Set new password
                $newPassword = model('CTCModel')->generatePassword($memberData['loginName']);
                model('CTCModel')->setMemberPasswordRaw($memberid, $newPassword);
                $subject = '[CTC]Your new CTC password';
                $message = "Hello,\n\nA password change has been requested for your CTC account\n".
                            "Your login name is: ".$memberData['loginName']."\n".
                            "Your new password is:".$newPassword."\n".
                            "To login to your account, select the link below.\n\n".
                            config('Joomla')->baseURL."/index.php/log-in\n\n".
                            "You should immediately change your new password to one you can remember.\n\n".
                            "Thank you.\n";
                helper('utilities');
                $mailSent = sendEmail("webmaster@ctc.org.nz", "Christchurch Tramping Club", $to, $subject, $message);
            }
        }
        if ($mailSent) {
            echo("New password has been sent to the email address on record.");
        } else {
            echo("Your password was changed but the Email send failed for some reason. You may need to try the forgotten password process again.");
        }
    }

    public function prospectiveMember()
    {
        return $this->loadPage('prospectiveMemberForm', "",
                               array('postbackUrl' => "open/prospectiveMemberSubmit",
                                     'css'=> "joomlaEmbedded.css"),
                               self::EMBEDDED);
    }

    public function prospectiveMemberSubmit()
    {
        $request = $this->request;
        $recaptchaResponse = $this->request->getPost("captcha-validated");
        if (!$this->recaptchaVerify()) {
            die("reCapcha verification failed. Please email members@ctc.org.nz for help.");
        }
        
        if (empty($request->getPost('email')) || empty($request->getPost('email2'))) {
            die("<p>An email address is required.</p>".
                "<p>Please back up in the browser, correct the error and resubmit.</p>");
        } else if ($request->getPost('email') != $request->getPost('email2')) {
            die("<p>Sorry, your form could not be processed as the two email fields do not match.</p>".
                "<p>Please back up in the browser, correct the error and resubmit.</p>");
        } else {
            // Now send the email
            // The "name" field needs the leading underscore because for some reason Joola removes
            // the "name" attribute from the input if it's value is "name" ?!?
            $name = $request->getPost('_name');
            $email = $request->getPost('email');
            $email2 = $request->getPost('email2');
            $phone = $request->getPost('phone');
            $mobile = $request->getPost('mobile');
            $address = $request->getPost('address');
            $postcode = $request->getPost('postcode');
            $howDidYouHear = $request->getPost('howdidyouhear');
            $notes = $request->getPost('notes');

            $body = <<<END
New CTC Prospective Member Contact

Name: $name
Email: $email
Email Veification: $email2
Phone: $phone
Mobile: $mobile
Address: $address
Postcode: $postcode
How did you hear about the CTC: $howDidYouHear
Notes: $notes
END;
            //$to = "new_members@ctc.org.nz"; // Todo look up a contact to find this
            $to = "nickedwards@gmail.com";
            $from = "new_members@ctc.org.nz";
            $sender = "CTC website contact";
            $subject = "CTC Proposed Member";

            log_message('debug', 'Sending email to '.$to);
            log_message('debug', 'From: '.$from.' Sender: '.$sender);
            log_message('debug', 'Subject: '.$subject);
            log_message('debug', 'Body: '.$body);  

            if ( ENVIRONMENT === 'development' ) {
                log_message('debug', 'Email not sent in development mode');
            } else {
                helper('utilities');
            }
            $mailSent = sendEmail($from, $sender, $to, $subject, $body);

            echo( "<p>Your form has been submitted.</p>".
                  "<p>Thank you for your interest in the CTC. Our Membership Officer wil be in touch with tyou soon.</p>" );
        }
        return true;
    }

    // Send out any queued email messages from the mail_queue table.
    // This command is called via CRON every 15 minutes or so.
    // It will probably not run to completion, due to various timeouts.
    // LOCK TABLES is used on the mail queue maintain integrity of
    // the mail queue, batches and log tables. However, if multiple
    // calls to this method are extant at any time, all processes will
    // be sending out at the maximum mail rate, breaking the hostgator
    // throttle rate. So the time between polls should be at least
    // $maxRunTimeMins.
    public function processMailQueue($maxRunTimeMins)
    {
        $ctcModel = model('CTCModel');
        $ctcModel->lockMailQueue();
        $batches = $ctcModel->incompleteBatches();
        $nSent = 0;
        if (count($batches) > 0) {
            ignore_user_abort(true);
            set_time_limit(0);

            $timeToQuit = time() + $maxRunTimeMins * 60;
            // Seconds between emails to avoid being called a spammer
            $stallSeconds = 15;
            helper('utilities');

            $i = 0;
            while ($i < count($batches) && time() < $timeToQuit) {
                $batchId = $batches[$i];
                $mail = $ctcModel->getNextMailItem($batchId);

                $subject = '';
                while ($mail && time() < $timeToQuit) {
                    $subject = $mail->subject;
                    sendEmail($mail->from, 'Christchurch Tramping Club', $mail->to,
                              $subject, $mail->body);
                    $nSent++;
                    $ctcModel->logEmail($mail->to, $mail->batchId);
                    $ctcModel->deleteMailItem($mail->id);
                    $ctcModel->unlockMailQueue();
                    sleep($stallSeconds);
                    $ctcModel->lockMailQueue();
                    $mail = $ctcModel->getNextMailItem($batchId);
                }
                if ($mail === false) {
                    $ctcModel->closeBatch($batchId);
                }
                $i++;
            }

            $ctcModel->purgeOldMailItems();
        }
        $ctcModel->unlockMailQueue();
        return $this->loadPage('operationOutcome', "Mail daemon done. $nSent emails sent.");
    }

    // ********************
    // TRIP REPORT SITE MAP
    // *********************
    // Return a page containing links in the "goto" format for all trip
    // reports in the database. Links are absolute, using the config parameter
    // joomla_base_url. This is to provide a pseudo site-map for use by
    // search engines.
    public function allTripReportLinks()
    {
        $model = model('TripReportModel');
        $allTrips = $model->getAllTripReports();
        return $this->loadPage('allTripReportLinks', 'All Trip Reports',
            array('trips' => $allTrips),
            self::EMBEDDED
        );
    }

    private function recaptchaVerify()
    {
        $secret = config('Security')->recaptchaSecretKey;

        // The response from reCAPTCHA
        $recaptchaResponse = $this->request->getPost("g-recaptcha-response");

        // The user's IP address
        $remoteIP = $this->request->getIPAddress();

        // Make the request to verify the reCAPTCHA response
        $requestURL = "https://www.google.com/recaptcha/api/siteverify?secret={$secret}&response={$recaptchaResponse}&remoteip={$remoteIP}";

        if ( ENVIRONMENT === 'development' ) {
            log_message('debug', 'Recaptcha verification skipped in development mode');
            log_message('debug', 'Would have made request to: '.$requestURL);
            return true;
        }

        $verifyResponse = file_get_contents($requestURL);
        $responseData = json_decode($verifyResponse);
        return $responseData->success;
    }
}
