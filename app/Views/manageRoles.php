<?php

function makeMemberCombo($members, $rowNum)
{
	$name = "member" . $rowNum;
	echo "<select name=\"$name\"><option value=\"0\"></option>";
	foreach ($members as $member) {
		echo "<option value=\"{$member->id}\">{$member->name}</option>";
	}
	echo"</select>";
}
?>
<h1>Role Management Form</h1>
<?php echo form_open("ctc/setAllRoles");?>
<p>Use the <em>Current Roles</em> section to delete or change roles
or the <em>Assign Role to Member</em> section to add a new member-role pairing without altering
any existing ones.</p><p><em>WARNING: use one or the other of these sections, not both.</em></p>
<p>This form does not allow the addition of completely new roles to the club;
ask the webmaster if you need to do that.</p>
<?php 
if (count($changes) > 0) {
	echo "<hr /><div  style=\"color:red\">\n";
	echo "<p>The following changes have just been made:<ol>\n";
	foreach ($changes as $change) {
		echo "<li>$change</li>\n";
	}
	echo "</ol></p></div>";
}
?>
<hr />
<h2>Current Roles</h2>
<p>The table below shows the existing roles. Changes can be made by clicking the
delete boxes or by selecting new members using the drop-down menus. Click <em>Submit</em>
when you have set new roles.</p>
<?php

echo '<table id="existingRoles" class="oddEven">';
echo '<tr><th>Role</th><th>Incumbent</th><th>Delete</th><th>ChangeTo</th></tr>';
$rowNum = 1;
foreach ($currentRoles as $row) {
	$name = $row->name;
	$cbName = "cb".$rowNum;
	$role = $row->role;
	echo "<tr><td>$role</td><td>$name</td><td><input type=\"checkbox\" name=\"$cbName\" /></td><td>";
	makeMemberCombo($members, $rowNum);
	echo "</td></tr>\n";
	$rowNum += 1;
}

echo "</table>";
echo "<p>" . form_submit("Submit", "Submit") . "</p>";
echo form_close();
?>
<hr />

<h2>Assign Role to Member</h2>
<p>Select a member and a role then click <em>Assign</em>.

</p>
<?php echo form_open("ctc/addRole");?>
<p>
Role: <select name="roleId"><option value="0"></option>
<?php 
foreach ($roles as $role) {
	echo "<option value=\"{$role->id}\">{$role->role}</option>";
}
?>
</select>&nbsp;&nbsp;Member: <?php makeMemberCombo($members, 0)?>
</p>
<?php
echo form_submit("Submit", "Assign");
echo form_close();
?>

