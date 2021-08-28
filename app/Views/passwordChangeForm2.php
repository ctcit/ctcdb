<?php echo form_open($postbackUrl); ?>

<h2>Password change form for user <?php echo "$name";?></h2>
Please enter the new password for this user (twice).
Then click 'Submit'.
<?php
	if (isset($validation)) {
		echo $validation->listErrors('ctcList');
	}
?>

<table class='passwordChangeTableNonEmbedded'>
	<tr>
		<td>New Password</td>
		<td><input type="password" name="newpass" value="<?php echo set_value('newpass'); ?>" size="50" /></td>
	</tr>
	<tr>
		<td>Confirm New Password</td>
		<td><input type="password" name="newpassconf" value="<?php echo set_value('newpassconf'); ?>" size="50" /></td>
	</tr>
</table>

<div><input type="submit" value="Submit" /></div>

<?php echo form_close(); ?>