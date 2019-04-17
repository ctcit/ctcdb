<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

function sendEmail($from, $fromName, $to, $subject, $message, $cc=NULL)
{
    global $CI;
    
    // Load and configure CI email library
    $CI->load->library('email');
    if ( $CI->config->item("use_smtp") == TRUE ) {
	// Use the specified SMTP server to deliver mail
        $CI->email->initialize(array(
            'protocol'=>'smtp', 
            'smtp_host'=>$CI->config->item("smtp_host"),
            'smtp_user'=>$CI->config->item("smtp_user"),
            'smtp_pass'=>$CI->config->item("smtp_pass"),
            'smtp_port'=>$CI->config->item("smtp_port"),
            'smtp_crypto'=>$CI->config->item("smtp_crypto"),
            'smtp_timeout'=>$CI->config->item("smtp_timeout"),
            'newline'=>"\r\n",
            'crlf'=>"\r\n"
        ));
    }
    // else use the default CI email settings - i.e. use php mail

    // Populate message
    $CI->email->from("webmaster@ctc.org.nz", "Christchurch Tramping Club");
    $CI->email->reply_to($from, $fromName);
    $CI->email->to($to);
    if (!is_null($cc)) {
        $CI->email->cc($cc);
    }
    $CI->email->subject($subject);
    $CI->email->message($message);

    //echo "Sent email to " . $to ."<br />";
    //echo $CI->email->print_debugger();
    return $CI->email->send();
}

function getSubsYear()
{
    $date = getdate();
    $year = $date['year'];
    if ($date['mon'] < 4) {
        $year--;  // Convert calendar year to subscription year prior to April
    }
    return $year;
}

?>
