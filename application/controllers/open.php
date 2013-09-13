<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

// This controller contains functions are accessible to anyone, even if
// they're not logged into the main club website.

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
                    sendEmail($mail->from, 'Christchurch Tramping Club', $mail->to,
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



}



?>