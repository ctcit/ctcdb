
<?php echo form_open($postbackUrl); ?>

<h2>Password Change Form</h2>
Please enter your current password and the new password you want (twice).
Then click 'Submit'.
<?php echo validation_errors(); ?>
<p>
<table class='passwordChangeTable'>
	<tr>
		<td>Current Password</td>
		<td><input type="password" name="currentpass" value="<?php echo set_value('currentpass'); ?>" size="50" /></td>
	</tr>

	<tr>
		<td>New Password</td>
		<td><input type="password" name="newpass" value="<?php echo set_value('newpass'); ?>" size="50" /></td>
	</tr>
	
	<tr>
		<td>Confirm New Password</td>
		<td><input type="password" name="newpassconf" value="<?php echo set_value('newpassconf'); ?>" size="50" /></td>
	</tr>
</table>
</p>
<div><input type="submit" value="Submit" /></div>

<?php echo form_close(); ?>
