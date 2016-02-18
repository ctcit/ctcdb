<?php  if (!defined('BASEPATH')) exit('No direct script access allowed'); ?>
<h1>Closing membership of <?php echo $membershipName; ?></h1>

You're about to close the membership for <b><?php echo $membershipName; ?></b>.
If that's correct, fill out the fields below and click <i>Submit</i>. Otherwise, click <i>Cancel</i>.
<p>
If given, the <i>resignation date</i> is the date of a received notice of resignation, in DD-MM-YYYY format.
If left blank, the current date is used.
</p>
<p>You can optionally add extra information to the membership notes field.</p>

<?php 
	echo validation_errors();
	echo form_open("ctc/closeMembership/$membershipId");
?>
<table>
<tr><td>Reason for closure: </td>
	<td><select name= "reason">
		<option value="SelectOne" <?php echo set_select('reason', 'SelectOne', TRUE); 	?>> SELECT ...</option>
		<option value="Resigned"  <?php echo set_select('reason', 'Resigned'); 			?>> Resigned</option>
		<option value="Deceased"  <?php echo set_select('reason', 'Deceased'); 			?>> Deceased</option>
		<option value="StruckOff" <?php echo set_select('reason', 'StruckOff'); 		?>> Struck off</option>
		<option value="Expired"   <?php echo set_select('reason', 'Expired'); 			?>> Prospective Expiry</option>
		</select>
	</td></tr>
<tr><td>Resignation date:</td>
	<td><?php echo form_date("resignationDate","resignationDate",set_value('resignationDate'));?></td>
</tr>
<tr><td>Membership notes:</td>
	<td><?php
		$membershipNoteData = array(
			'name'=>"membershipNotes",
			'value'=>set_value('membershipNotes', $membershipNotes),
			'rows'=>10,
			'cols'=>80
		);
		echo form_textarea($membershipNoteData);?>
	</td>
</tr>
</table>
<?php
	echo form_submit("Submit","Submit");
	echo anchor("", "Cancel", 'class="back_button"');
?>

