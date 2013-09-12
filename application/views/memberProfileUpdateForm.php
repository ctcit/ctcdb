<?php  if (!defined('BASEPATH')) exit('No direct script access allowed'); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
<META HTTP-EQUIV="Expires" CONTENT="-1">
<?php
$this->load->helper('html');
echo link_tag("css/memberUpdate.css");
echo "<title>$title</title>";
?>

</head>
<body>
<div class="memberProfile">

<h2>Personal Details</h2>
<p>Shown below are your personal details from the current club database.
If any of the details are wrong, please correct them and click the <b><i>Submit</i></b>
button at the bottom.
</p><p>Note that if you're in a couple membership and change your
address or home phone, your partner's details will be automatically updated as well.</p>
<?php
	echo validation_errors();
	$this->load->helper('ctcforms');
	echo form_open($postbackUrl);
	echo '<table class="memberDataForm">';
	displayFieldsInTable($fields);
	echo "</table>";
?>
	<input type="submit" value="Submit" class="submit" />
<?php
	global $CI;
	$ctcHome = $CI->config->item('joomla_base_url');
	$base = trim(base_url(),'/');
	echo "&nbsp;&nbsp;<button type=\"button\"
		  onclick=\"if(confirm('Return to CTC home page, without changing your details?')) top.location.href='$ctcHome'\">
		  Cancel</button>";

	echo form_close();
?>
</div>
</body>
</html>
