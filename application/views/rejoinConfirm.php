<?php  if (!defined('BASEPATH')) exit('No direct script access allowed'); ?>
<h1>Rejoin confirmation</h1>

You're about to open a new membership for <b><?php echo $memberName; ?></b>, whose most-recent
status is <b><?php echo $status; ?></b>.
Fields for the new membership record will be copied from the old one, so may need editing via <i>View/Edit</i>
after this step is complete.
<p> Proceed? [The alternative is to
<i>reinstate</i> the member, which simply cancels the prior resignation or 'strike off' action,
as though it were an error.]
</p>

<?php
	echo form_open("ctc/rejoinMember2/$memberId");
	echo form_submit("Submit","OK");
	echo form_close();
	echo anchor("ctc","Cancel",'class="back_button"');
?>

