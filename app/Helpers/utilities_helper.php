<?php

/*
* Send an email
*
* @param string $from The email address of the sender
* @param string $fromName The name of the sender
* @param string $to A comma separated list or an array of email addresses to send to
* @param string $subject The subject of the email
* @param string $message The body of the email
* @param string $cc Optional comma separated list or a array of email addresses to cc
*
* @return bool True if the email was sent successfully, false otherwise
*/
function sendEmail($from, $fromName, $to, $subject, $message, $cc=NULL)
{
    if ($to == '' || is_null($to)) {
        log_message('error', 'No email address specified for sendEmail');
        return false;
    }
    // Load and the CI email library
    // Settings are defined in App\Config\Email.php
    $email = \Config\Services::email();

    // Populate message
    // We have to set the email from address to webmaster@ctc.org.nz as this is the validated
    // from email in Amazon SES
    $trueFromEmail = "webmaster@ctc.org.nz";
    $trueFromName = "Christchurch Tramping Club";
    $email->setFrom($trueFromEmail, $trueFromName);
    $email->setReplyTo($from, $fromName);
    $email->setTo($to);
    if (!is_null($cc)) {
        $email->SetCC($cc);
    }
    $email->setSubject($subject);
    $email->setMessage($message);

    // Log the email
    log_message('debug', '==== Sending email ===');
    $toString = is_array($to) ? join(',', $to) : $to;
    log_message('debug', 'To: '.$toString);
    if ($cc) {
        $ccString = is_array($cc) ? join(',', $cc) : $cc;
        log_message('debug', 'CC: '.$ccString);
    }
    log_message('debug', 'From: '.$trueFromEmail.' '.$trueFromName);
    log_message('debug', 'Reply-To: '.$from.' '.$fromName);
    log_message('debug', 'Subject: '.$subject);
    log_message('debug', "Body:\n".$message);

    if ( ENVIRONMENT !== 'production' ) {
        // In development, don't actually send the email
        log_message('debug', 'Email not sent in development environment');
        return true;
    }

    return $email->send();
}

function getSubsYear()
{
    $date = getdate();
    $year = $date['year'];
    if ($date['mon'] < 4) {
        // Convert calendar year to subscription year prior to April
        $year--;
    }
    return $year;
}

?>