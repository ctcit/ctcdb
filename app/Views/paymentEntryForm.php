<h1>Payments for subscription year <?php echo $year; ?>
</h1>
<script type="text/javascript">
function changeYear()
{
    selectBox = document.getElementById('SelectYear');
    index = selectBox.selectedIndex;
    year = selectBox.options[index].value;
    document.YearSelectForm.action = "<?php echo site_url("subs/recordPayments"); ?>" + "/" + year;
    document.YearSelectForm.target = "_self";
    document.YearSelectForm.submit();
}
</script>
<form name="YearSelectForm" class="EmptyForm" method="post"></form>
<p>
Click the <i>Paid</i> checkbox and enter the amount and card number(s)
for each membership payment that you wish to record. You can set the
payment date to a date in the form DD-MM-YYYY either by entering it in text
(e.g. 23-4-2008) or by clicking the calendar icon and selecting a date in the
pop-up window. If you leave the date field blank, the current date will be used.
You can also use this form to alter the details of existing
payments if you wish.
Then click <i>Save payments</i> at the bottom of the page.
</p>
<p>
Please note that nothing is recorded until the <i>Save payments</i> button
has been clicked, so don't enter too many subscriptions at once in case you lose the lot,
e.g. in the event of an Internet disconnection!
</p>
<p>You can use the filter to select subsets of the memberships.
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
Filter by string: <input id="filter" type="text" value=""
    onkeyup="selectRows('subsPayments',1,6)" />
</p>
<p>
<?php
if (isset($message)) {
    echo "<span  class='FormReloadMessage'><p>$message</p></span>";
}

if (!isset($reloadValues)) {
    $reloadValues = array();
}

$noEdit = array("MSID", "IDs", "MembershipName", "Login","Type","Fee");
$fields = $membershipQuery->getFieldNames();
array_unshift($fields, 'Row');
$numCols = count($fields);

helper('form');
helper('ctcforms');
echo form_open("subs/recordPayments/$year", array('name'=>'paymentsForm'));
echo '<table id="subsPayments" class="oddEven">';
for ($i=1; $i<=$numCols; $i++) {
    echo "<col class=\"col$i\" />";
}
echo "<tr>";
foreach ($fields as $field) {
    echo "<th>$field</th>";
}
echo "</tr>";

$memberships = $membershipQuery->getResultArray();

$rowNum = 1;
foreach ($memberships as $membership) {
    echo "<tr><td>$rowNum</td>";
    $id = $membership['MSID'];

    if (isset($reloadValues["cb$id"]) || $membership["DatePaid"] != NULL) {
        $selectString = ' CHECKED="Yes"';
    }
    else {
        $selectString = "";
    }
    foreach (array_keys($membership) as $key) {
        if ($key == 'Paid') {
            if ($selectString != '') $selectString .= " DISABLED=\"Yes\"";
            echo "<td><input type='checkbox' name='cb$id' value='Paid' $selectString /></td>";
        }
        else if (in_array($key, $noEdit)) {
            $value = htmlentities($membership[$key]);
            echo "<td>$value</td>";
        }
        else {
            $fieldName = $key.$id;
            if (isset($reloadValues[$fieldName])) {
                $value = $reloadValues[$fieldName]['value'];
                $class = $reloadValues[$fieldName]['status'];
            }
            else {
                $value = htmlentities($membership[$key]);
                $class = "";
            }
            if ($key == 'DatePaid') {
                echo "<td>";
                if ($class != '') {
                    echo form_date($fieldName, $fieldName, $value, $class);
                }
                else {
                    echo form_date($fieldName, $fieldName, $value);
                }
                echo  "</td>";
            }
            else {
                echo "<td><input name='$fieldName' value=\"$value\" class='$class'/></td>";
            }
        }
    }
    echo "</tr>";
    $rowNum++;
}
echo "</table>";

echo "<p>" . form_submit("Submit", "Save payments") .
    " [Saves all changed rows, even if currently hidden
    by the filter, to the database.]</p>";
echo form_close();
?>
</p>
