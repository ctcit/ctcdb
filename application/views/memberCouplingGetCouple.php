<?php  if (!defined('BASEPATH')) exit('No direct script access allowed'); ?>
<h1>Member-coupling form</h1>
<p>
Use the checkboxes in the leftmost column to select the two members who are to share a new "Couple" membership
and click submit.
Note that since a couple must share an address, you should be able
to type their street name into the filter field to reduce the membership
list to manageable size.
</p><p>
The fields for the new shared membership will be copied from the member who has been in the club
the longest. However, you will get a chance
to change these fields on the 'Confirm couple details' form, which you get when you click 'Submit'.
</p>
<?php echo form_open("ctc/coupleMembers"); ?>

<p>Filter by string: <input id="filter" type="text" value=""
	onkeyup="selectRows('memberSelect')" />
</p>
<p>
<?php
$numCols = count($coupleList[0]);
$tableOpenString = '<table id="memberSelect" class="oddEven">';
for ($i=1; $i<=$numCols; $i++) {
	$tableOpenString .= "<col class=\"col$i\" />";
}
$tmpl = array ( 'table_open'  => $tableOpenString, 'row_start' => '<tr class="odd">' );
$this->table->set_template($tmpl);
echo $this->table->generate($coupleList);
echo "</p>";
echo form_submit("Submit", "Submit");
echo form_close();
?>
<a href='javascript:history.go(-1)'>[ Back ]</a>
