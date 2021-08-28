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
    private const NO_MENU = false;

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
                               array('postbackUrl' => "open/forgottenUserNameSubmit",'css'=> "ctcdbNewWindow.css"),
                               self::NO_MENU);
    }

    public function forgottenUserNameSubmit()
    {
        $searchData = $this->request->getPost("search_data");
        $recaptchaResponse = $this->request->getPost("captcha-validated");
        if ($recaptchaResponse !== "true") {
            die("You need to confirm you are not a robot.");
        }
        $memberData = model('ctcModel')->getMemberLoginNameFromEmailPhoneLoginName($searchData);
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
                               'css'=> "ctcdbNewWindow.css"), self::NO_MENU);
    }

    public function forgottenPasswordSubmit()
    {
        $searchData = $this->request->getPost("search_data");
        $recaptchaResponse = $this->request->getPost("captcha-validated");
        if ($recaptchaResponse !== "true") {
            die("You need to confirm you are not a robot.");
        }
        $memberData = model('ctcModel')->getMemberLoginNameFromEmailPhoneLoginName($searchData);
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
                $newPassword = model('ctcModel')->generatePassword($memberData['loginName']);
                model('ctcModel')->setMemberPasswordRaw($memberid, $newPassword);
                $subject = '[CTC]Your new CTC password';
                $message = "Hello,\n\nA password change has been requested for your CTC account\n".
                            "Your login name is: ".$memberData['loginName']."\n".
                            "Your new password is:".$newPassword."\n".
                            "To login to your account, select the link below.\n\n".
                            config('Joomla')->baseURL."/index.php/log-in\n\n".
                            "You should immediately change your new password to one you can remember.\n\n".
                            "Thank you.\n";
                helper('utilities');
                // echo "Sending email from $userEmail ($name) to $to ($loginName), subject = $subject<br />";
                $mailSent = sendEmail("webmaster@ctc.org.nz", "Christchurch Tramping Club", $to, $subject, $message);
            }
        }
        if ($mailSent) {
            echo("New password has been sent to the email address on record.");
        } else {
            echo("Your password was changed but the Email send failed for some reason. You may need to try the forgotten password process again.");
        }
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
        $ctcModel = model('ctcModel');
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
                    echo sendEmail($mail->from, 'Christchurch Tramping Club', $mail->to,
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
        $model = model('tripreportmodel');
        $allTrips = $model->getAllTripReports();
        return $this->loadPage('allTripReportLinks', 'All Trip Reports',
            array('trips' => $allTrips),
            self::NO_MENU
        );
    }
}
