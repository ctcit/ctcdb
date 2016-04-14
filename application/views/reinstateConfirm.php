<?php  if (!defined('BASEPATH')) exit('No direct script access allowed'); ?>
<h1>Reinstatement confirmation</h1>
<p>
You're about to reinstate the membership <b><?php echo $membershipName; ?></b>,
the status of which is currently shown as <b><?php echo $status; ?></b>.
This will in effect cancel the resignation or 'strike-off' status, as if it were an error.</p>
<p>
Is that what you want to do? [The alternative is to
<i>rejoin</i> the member, which opens up a new membership, keeping the old one on file.]</p>

<?php
	echo form_open("ctc/reinstateMembership2/$membershipId");
	echo form_submit("Submit","OK");
	echo form_close();
	echo anchor("ctc","Cancel",'class="back_button"');
?>

