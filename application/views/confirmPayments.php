<?php  if (!defined('BASEPATH')) exit('No direct script access allowed'); ?>
<h1>CTCDB: payment confirmation for Subscription Year <?php echo $subsYear; ?>.</h1>
<p>You are about to make the following additions or changes to the subscription
payments database table. Changed fields are shown in blue. Suspect fields, such as illegal
card numbers or secondary card numbers allocated to single memberships, are
shown with a red background. Do not submit forms with suspect fields unless
you are quite sure you know what you're doing!
<p>
Please inspect the changes carefully. Click
<i>Save to DB</i>
to save the changes, or
<i>Re-edit Form</i>
to return to the form you were editing.
</p><p></p>
<?php
if (count($table) == 0) {
	echo "There were no changed fields in that table!!";
}
else {
	echo form_open("subs/recordPayments2/$subsYear");
	echo form_hidden('changeTable', serialize($table));
	echo "<table class='ConfirmPayments'><tr>";
	$columns = array_keys($table[0]);
	foreach ($columns as $column) {
		echo "<th>$column</th>";
	}
	echo "<tr>";
	foreach ($table as $row) {
		echo "<tr>";
		foreach ($columns as $column) {
			$field = $row[$column];
			$value = $field['value'];
			$safeValue = htmlentities($value);
			$classes = array();
			if ($field['changed']) $classes[] = "ColumnChanged";
			if ($field['suspect']) $classes[] = "ColumnSuspect";
			$classSpecifier = count($classes) == 0 ? '' : " class=\"" . implode(' ', $classes) .'"';
			echo "<td$classSpecifier>$safeValue</td>";
			if ($field['changed']) {
				$name = $column.$row['MSID']['value'];
				echo form_hidden($name, $value);
			}
		}
		echo "</tr>";
	}
	echo "</table>";
	echo "<p>".form_submit("submit", "Save to DB").form_submit("reedit", "Re-edit Form");
	echo form_close();
}

?>



