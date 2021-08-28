<!--- The data entry form for a couple. This is an extended version of the
	single member/membership form, using the notation that input field and area
	names ending in __1 or __2 are taken to be associated with the member data
	(as distinct from membership data) for members 1 and 2 respectively.
	All other fields and input areas are assumed to be membership data,
	which is placed after the two-column
	member input fields.
--->

<h1>New couple data entry form</h1>
<p>Fill in at least the new members' names and their address and click
submit. The 'dateJoined' fields are the dates at which the two members
were approved by committee, in DD-MM-YYYY format. If left blank, the
current date is used.</p>

<?php

if (isset($validation)) {
	echo $validation->listErrors('ctcList');
}
echo form_open("ctc/newCouple");
helper('ctcforms');
echo '<table id="NewCoupleMembers">';
echo '<tr><th>First member</th><th>Second member</th></tr>';
echo "<tr>";
for($i=1; $i <=2; $i++) {  // For each member in turn
	echo "<td><table class=\"MemberInNewCouple\"><col class=\"col1\" /><col class=\"col2\" />";
	displayFieldsInTable($fields, "/__$i$/");
	echo "</table></td>";
}
echo "</tr></table><p></p><h2>Shared data fields</h2>";

echo '<table id="NewCoupleMembershipFields"><col class="col1" /><col class="col2" />';
displayFieldsInTable($fields, "/(^.*[^12]$)|(^.*[^_][12]$)|(^.*[^_]_[12]$)/"); // Display fields not ending in __[12]
echo "</table></td>";

echo form_submit("submitButton","Submit");
echo "<a href='javascript:history.go(-1)' class='back_button'>[ Back ]</a>";

echo form_close();
?>



