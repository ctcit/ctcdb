<?php  if (!defined('BASEPATH')) exit('No direct script access allowed'); ?>
<h2>Payment deletion for subscription year <?php echo $year; ?></h2>
IMPORTANT NOTE: It should not be necessary to use this capability of the
database under normal circumstances. It exists only to provide a way of
undoing an erroneously-recorded payment, e.g. one recorded against the
wrong membership or for the wrong year.

<script type="text/javascript">
function changeYear()
{
	selectBox = document.getElementById('SelectYear');
	index = selectBox.selectedIndex;
	year = selectBox.options[index].value;
	document.YearSelectForm.action = "<?php echo site_url("subs/deletePayment"); ?>" + "/" + year;
	document.YearSelectForm.target = "_self";
	document.YearSelectForm.submit();
}
</script>
<form name="YearSelectForm" class="EmptyForm" method="post"></form>
<p>
Click the <i>Delete</i> link for the payment you wish to delete.
</p>
<p>You can use the filter to select subsets of the memberships or to find a particular member.
</p><p>
Change subscription year:

<select class="PaymentFormYearSelect" name ="SelectYear" id ="SelectYear" onchange="changeYear()">
<?php
$date = getDate();
$thisYear = $date['year'];
for ($yr = $thisYear - 2; $yr <= $thisYear + 2; $yr++) {
	$sel = $yr == $year ? " selected=\"yes\"" : "";
	echo "<option class=\"HeaderOption\" value=\"$yr\"$sel\">$yr</option>" ;
}
?>
</select>
Filter by string: <input id="filter" type="text" value="" onkeyup="selectRows('PaymentDeletion',1)" />
</p>
<p>
<?php
$fields = $paymentsQuery->list_fields();
$fields[0] = ''; // Kill the "ID" header
$payments = $paymentsQuery->result_array();
$table = array($fields);  // Header row
foreach ($payments as $payment) {
	$id = $payment['ID'];
	$membershipName = $payment['MembershipName'];
	$row = array();
	foreach (array_keys($payment) as $key) {
		if ($key == 'ID') {  // First column is made into the Delete link
			$row[] = anchor("subs/deletePayment2/$year/$id", "Delete");
		}
		else  {
			$row[] = htmlentities($payment[$key]);
		}
	}
	$table[] = $row	;
}

$numCols = count($fields);
$tableOpenString = '<table id="PaymentDeletion" class="oddEven">';
for ($i=1; $i<=$numCols; $i++) {
	$tableOpenString .= "<col class=\"col$i\" />";
}
$tmpl = array ( 'table_open'  => $tableOpenString, 'row_start' => '<tr class="odd">' );
$this->table->set_template($tmpl);
echo $this->table->generate($table);
?>
</p>
<p>
<a class="back_button" href='javascript:history.go(-1)'>[ Back ]</a>
</p>

