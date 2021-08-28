<h1>CTCDB: payment confirmation.</h1>
<p>
You are about to record the subscription payments listed below in the database .
Please check the list carefully and click <i>Save to DB</i> only if it is all correct.
Otherwise, click <i>Back</i> and edit your subscription records form. 
</p>
<?php
$ids = array_keys($payments);
$columns = array_keys($payments[$ids[0]]);
echo "<table><tr><th>ID</th><th>" . implode("</th><th>", $columns) . "</th></tr>";
foreach ($ids as $id) {
	echo "<tr><td>$id</td><td>" . implode("</td><td>", $payments[$id]) . "</td></tr>";
}
echo "</table>";

echo form_open("subs/recordPayments2");
foreach ($ids as $id) {
	echo form_hidden("cb$id","Paid");  // Pseudo checkbox for use by processPaymentsForm function.
	$row = $payments[$id];
	$keys = array_keys($row);
	foreach ($keys as $key) {
		echo form_hidden($key.$id, $row[$key]);
	}

}
echo "<p>".form_submit("Submit", "Save to DB").
	"  <a class='back_button' href='javascript:history.go(-1)'>[ Back ]</a></p>";
echo form_close();
?>



