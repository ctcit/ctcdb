<?php  if (!defined('BASEPATH')) exit('No direct script access allowed'); ?>
<h1>Member-coupling confirmation and data</h1>

You are about to replace the individual memberships for
<b><?php echo $member1Name; ?></b>
and
<b><?php echo $member2Name; ?></b>
with a single shared couple membership initialised with the following
data (which you can edit here if you wish).

<?php
$this->load->helper('ctcforms');
echo validation_errors();;
echo form_open("ctc/coupleMembers2/$member1Id/$member2Id");
echo "<table>";
displayFieldsInTable($fields);
echo "</table>";

?>
<input
	type="Submit" value="Submit" class="submit_button" />
<a class="back_button" href='javascript:history.go(-1)'>[ Back ]</a>

<?php echo form_close(); ?>