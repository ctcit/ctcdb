<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

function sendEmail($from, $fromName, $to, $subject, $message, $cc=NULL)
{
    global $CI;
    
    $CI->load->library('email');
    $baseUrl = $CI->config->item('joomla_base_url');
    if (strpos($baseUrl,"localhost")) {
        // Mail from home prototyping site uses Clear's SMTP server
        $CI->email->initialize(array(
            'protocol'=>'smtp', 
            'smtp_host'=>"smtp.clear.net.nz",
            'newline'=>"\r\n",
            'crlf'=>"\r\n"
        ));
    }
    //else {
    //    $CI->email->initialize(array('protocol' => 'sendmail'));
    //}
    $CI->email->from("webmaster@ctc.org.nz", "Christchurch Tramping Club");
    $CI->email->reply_to($from, $fromName);
    $CI->email->to($to);
    if (!is_null($cc)) {
        $CI->email->cc($cc);
    }
    $CI->email->subject($subject);
    $CI->email->message($message);
    $CI->email->send();
    //echo "Sent email to " . $to ."<br />";
    //echo $CI->email->print_debugger();
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
