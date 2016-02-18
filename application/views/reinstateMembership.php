<?php  if (!defined('BASEPATH')) exit('No direct script access allowed'); ?>
<h1>Reinstating a membership. Step 1.</h1>
<p>This menu option reinstates a struck-off or resigned membership as if it never got closed.
This can be used where a member reappears and pays all overdue subs, maintaining an unbroken
membership. It could
also be used for a member who resigns but then changes their mind. It should
<i>not</i> be used on a member who wishes to take out a new membership after a period of
non-membership. Use 'rejoin' for that. 
</p>
<p>Note that both members of a Couple membership are reinstated by this action.
<p>
Click the <i>Reinstate</i> link for the membership you wish to reinstate.
You can use the filter to select a subset of the memberships.
</p>
<p>

Filter by string: <input id="filter" type="text" value=""
	onkeyup="selectRows('membershipSelect')" />
</p>
<p>
<?php
$numCols = count($memberships[0]);
$tableOpenString = '<table id="membershipSelect" class="oddEven">';
for ($i=1; $i<=$numCols; $i++) {
	$tableOpenString .= "<col class=\"col$i\" />";
}
$tmpl = array ( 'table_open'  => $tableOpenString, 'row_start' => '<tr class="odd">' );
$this->table->set_template($tmpl);
echo $this->table->generate($memberships);
?>
</p>
<p>
<a class="back_button" href='javascript:history.go(-1)'>[ Back ]</a>
</p>

