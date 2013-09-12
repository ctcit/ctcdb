<?php  if (!defined('BASEPATH')) exit('No direct script access allowed'); ?>
<h3>Confirm payment deletion</h3>

<FORM>
<?php
	echo "<p>Do you really wish to delete the payment record for $membershipName for the year $year?</p>";
	$deleteUrl = site_url("subs/deletePayment3/$paymentId");
	$homeUrl = site_url();
	echo form_input(array(
		'type'=>'button','class'=>'button','value'=>'OK','onclick'=>"window.location.href='$deleteUrl'"));
	echo form_input(array(
		'type'=>'button','class'=>'button','value'=>'Cancel','onclick'=>"window.location.href='$homeUrl'"));
?>
</FORM>




