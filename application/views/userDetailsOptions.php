<?php  if (!defined('BASEPATH')) exit('No direct script access allowed'); ?>

<h2>What would you like to do?</h2>
<?php 
if ($isBrand8Flame7) {
	echo "<p style=\"color:red\">NOTE: access to your personal data is unavailable until ".
	"you change your password from brand8flame7</p>";
}
?>
<p>Please click one of the following links.</p>
<ul>
<?php
echo "<li>".anchor($changePasswordUrl, "Change your password")."</li>";
if ($isBrand8Flame7) {
	echo "<li style=\"color:gray\">View/edit your personal details</li>";
	echo "<li style=\"color:gray\">View your subscription information</li>";
}
else {
	echo "<li>".anchor($changeProfileUrl, "View/edit your personal details")."</li>";
	echo "<li>".anchor($subsPaymentForm, "View your subscription information")."</li>";
}
echo "<li>".anchor($sendEmailUrl, "Send email to the club database administrator",
					array("target"=>"_top"))."</li>";
?>
</ul>
Use the last option if you wish to change your newsletter-subscription option (which affects
your annual membership fee) or if you prefer to deal with a real human.


