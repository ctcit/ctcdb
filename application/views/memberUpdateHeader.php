<?php  if (!defined('BASEPATH')) exit('No direct script access allowed'); 
	$memberName = $fields['memberNameHidden']['value'];
	$status = $fields['statusAdminHidden']['value'];
	$partner = $fields['partnerHidden']['value'];
	echo "<h1>Update form for $memberName</h1><ul>";
	echo "<li>Status: $status</li>";
	echo "<li>Partner: $partner</li></ul>";
?>

Change any of the fields below and click submit to store the changes.
Note that for
'Coupled' members, changes made to shared information like address or
membership email will automatically be updated for the other member of
the couple.
