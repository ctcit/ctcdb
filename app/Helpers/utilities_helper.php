<?php

function sendEmail($from, $fromName, $to, $subject, $message, $cc=NULL)
{
    // Load and the CI email library
    // Settings are defined in App\Config\Email.php
    $email = \Config\Services::email();

    // Populate message
    $email->setFrom("webmaster@ctc.org.nz", "Christchurch Tramping Club");
    $email->setReplyTo($from, $fromName);
    $email->setTo($to);
    if (!is_null($cc)) {
        $email->SetCC($cc);
    }
    $email->setSubject($subject);
    $email->setMessage($message);

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