<?php  if (!defined('BASEPATH')) exit('No direct script access allowed'); ?>

<h1>Closing a membership. Step 1.</h1>
<p>This form is used to close a membership, e.g. due to death, resignation or non-payment of
subscription ("strike off"). Before using this form to process resignations, however, you might wish to consider whether
the member should instead be offered Associate Membership.</p>
Click the <i>close</i> link for the membership you wish to close. You can use the filter to select a subset of the memberships.
<p>

Filter by string: <input id="filter" type="text" value=""
	onkeyup="selectRows('membershipSelect',1)" />
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

