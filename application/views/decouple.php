<?php  if (!defined('BASEPATH')) exit('No direct script access allowed'); ?>
<h1>De-coupling a couple membership</h1>
To decouple two coupled members, click their associated 'Zap' link. You can use the filter to select a subset of the couples in the club.
<p> 
The address fields in each of the new memberships is copied from the current couple membership, so probably
at least one will need to be fixed! Various other membership fields, like 'mailFMC' etc, may need to be tweaked, too.
</p>
<p>Filter by string: <input id="filter" type="text" value=""
	onkeyup="selectRows('membershipSelect',1)" />
</p>
<p>
<?php
$couples = $this->Ctcmodel->getAllCouplesForDecoupling();
$numCols = count($couples[0]);
$tableOpenString = '<table id="membershipSelect" class="oddEven">';
for ($i=1; $i<=$numCols; $i++) {
	$tableOpenString .= "<col class=\"col$i\" />";
}
$tmpl = array ( 'table_open'  => $tableOpenString, 'row_start' => '<tr class="odd">' );
$this->table->set_template($tmpl);
echo $this->table->generate($couples);
?>
</p>
