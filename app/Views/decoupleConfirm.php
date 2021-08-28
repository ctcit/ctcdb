<h3>Confirm decoupling</h3>
Do you really wish to split the couple <b><?php echo $coupleName; ?></b>
 into two separate memberships?

<FORM>
<?php
	helper('url');
	helper('form');
	$decoupleUrl = site_url("ctc/decoupleMembers/$membershipId/1");
	$homeUrl = site_url();
	echo form_input(array(
		'type'=>'button','class'=>'button','value'=>'OK','onclick'=>"window.location.href='$decoupleUrl'"));
	echo form_input(array(
		'type'=>'button','class'=>'button','value'=>'Cancel','onclick'=>"window.location.href='$homeUrl'"));
?>
</FORM>




