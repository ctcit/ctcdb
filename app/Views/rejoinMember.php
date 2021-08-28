<h1>Rejoining a member. Step 1.</h1>
<p>This menu option opens a new membership for a member who has been struck off 
or who has resigned. It is subtly different from the 'reinstate' menu option,
which cancels a prior resignation or 'strike off' as though it were an error.
</p>
<p>The new membership record will contain default values for all fields except the
address fields, which are copied from the most-recent membership record for the
member, so it will probably be necessary to edit the member/membership via
the usual Edit/View member menu option.</p>
<p>
Click the <i>Rejoin</i> link for the member you wish to rejoin.
You can use the filter to select a subset of the memberships.
</p>
<p>

Filter by string: <input id="filter" type="text" value=""
	onkeyup="selectRows('memberSelect')" />
</p>
<p>
<?php
$numCols = count($members[0]);
$tableOpenString = '<table id="memberSelect" class="oddEven">';
for ($i=1; $i<=$numCols; $i++) {
	$tableOpenString .= "<col class=\"col$i\" />";
}
$tmpl = array ( 'table_open'  => $tableOpenString, 'row_start' => '<tr class="odd">' );
$table = new \CodeIgniter\View\Table();
$table->setTemplate($tmpl);
echo $table->generate($members);
?>
</p>
<p>
<a class="back_button" href='javascript:history.go(-1)'>[ Back ]</a>
</p>

