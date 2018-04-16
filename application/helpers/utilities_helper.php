<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

function sendEmail($from, $fromName, $to, $subject, $message, $cc=NULL)
{
    global $CI;
    
    $CI->load->library('email');
    $baseUrl = $CI->config->item('joomla_base_url');
    if (strpos($baseUrl,"localhost")) {
        // Mail from home prototyping site uses init file settings
        //($this->smtp_user == '' AND $this->smtp_pass == '')
        $CI->email->initialize(array(
            'protocol'=>'smtp', 
            'smtp_host'=>$CI->config->item("smtp_host"),
            'smtp_user'=>$CI->config->item("smtp_user"),
            'smtp_pass'=>$CI->config->item("smtp_pass"),
            'smtp_port'=>$CI->config->item("smtp_port"),
            'smtp_timeout'=>$CI->config->item("smtp_timeout"),
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
    return $CI->email->send();
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
