<h2>CTC Membership List</h2>
<p>The membership list below is formatted for on-screen viewing.
Printable versions are also available:
<ul>
    <li><?php echo anchor($printableListBySurnameUrl, "Printable version, sorted by last name", 'target="_blank"')?></li>
    <li><?php echo anchor($printableListByFirstnameUrl, "Printable version, sorted by first name", 'target="_blank"')?></li>
</ul>

<p>Please note that this list is intended for the use of Christchurch
Tramping Club members only and for Christchurch Tramping Club related
matters only. Please respect the privacy of your fellow members. In
particular, please do not use emails obtained from this list for mass
mailouts to club members. Such mailouts should be sent to the <a
	href="mailto:members@ctc.org.nz">moderated members mail list</a>.</p>
<p>Click on the checkboxes below to select either email address, street
address or mobile phone for the third column.</p>
<p>Note: if clicking the checkboxes has no effect, your browser may not
have javascript enabled. In that case you can still find out someone's
address by holding the mouse cursor over the person's name for a second
or so -- a small window should then pop up with the address in it.
Similarly, holding the cursor over a person's phone will reveal their
mobile number (if they have one).</p>
<p></p>
<script>
function showHideCols(colNum) {
    var addrCB = document.getElementById('showAddress');
    var emailCB = document.getElementById('showEmail');
    var mobCB = document.getElementById('showMob');
    if (colNum === 2 && addrCB.checked)
        emailCB.checked = mobCB.checked = false;
    else if (colNum ===2 && !addrCB.checked){
        emailCB.checked = true;
        mobCB.checked = false;
    }
    if (colNum === 3 && emailCB.checked)
        addrCB.checked = mobCB.checked = false;
    else if (colNum ===3 && !emailCB.checked){
        addrCB.checked = true;
        emailCB.checked = false;
    }
    if (colNum === 4 && mobCB.checked)
        addrCB.checked = emailCB.checked = false;
    else if (colNum === 4 && !mobCB.checked){
        addrCB.checked = false;
        emailCB.checked = true;
    }
    var tbl  = document.getElementById('members');
    var rows = tbl.getElementsByTagName('tr');
    for (var row=0; row<rows.length;row++) {
      var cels = rows[row].getElementsByTagName('td');
      cels[2].style.display = (addrCB.checked) ? '' : 'None';
      cels[3].style.display = (emailCB.checked) ? '' : 'None';
      cels[4].style.display = (mobCB.checked) ? '' : 'None';
    }
}
</script>

<script>
    function sort(){
        var chkSortByFirstName = document.getElementById('chkSortByFirstName');
        var tbl  = document.getElementById('members');
        var rows = tbl.getElementsByTagName('tr');
        for (var iRow = 0; iRow < rows.length; iRow++) {
            var row = rows[iRow];
            var showRow = (row.className === "byfirstname" && chkSortByFirstName.checked) ||
                          (row.className === "bysurname" && !chkSortByFirstName.checked) ||
                          (row.className ===""); // Header row
             row.style.display = showRow ? '': 'None';
        }
    }

</script>
<table width="75%">
	<tr>
		<td>Address: <input type="checkbox" id="showAddress"
			onclick="javascript:showHideCols(2)"></td>
		<td>Email: <input type="checkbox" id="showEmail"
			onclick="javascript:showHideCols(3)" CHECKED></td>
		<td>Mobile phone: <input type="checkbox" id="showMob"
			onclick="javascript:showHideCols(4)"></td>
		<td>Sort by first name: <input type="checkbox" id="chkSortByFirstName"
			onclick="javascript:sort()"></td>
	</tr>
</table>


<h3>Membership List</h3>
<p>Members can change their personal details via the <i>User Details</i>
 link in the members menu. Alternatively, you can email corrections
 to <a href="mailto:trampgeek@gmail.com?subject=Change%20of%20CTC%20Contact%20Details">Richard Lobb</a>.</p>


<?php

echo "<table id=\"members\">";
echo "<thead>";
echo "<tr><td class=\"col0\"><b>Name</b></td><td class=\"col1\"><b>Phone</b></td><td class=\"col2\"  style=\"display:none\"><b>Address</b></td><td  class=\"col3\"  style=\"display\"><b>Email</b></td><td class=\"col4\"
style=\"display:none\"><b>Mobile</b></td></tr>";
echo "</thead><tbody>";

// Controller has filled $members
foreach(array("bysurname", "byfirstname") as $order){
    $members = $order === "bysurname" ? $membersBySurname: $membersByFirstName;
    foreach ($members as $user) {
        $phone = $user->homePhone;
        $mobTitle = $user->mobilePhone;
        if ($mobTitle === "") $mobTitle = "No mobile";
        if (trim($phone) === "")
            $phone = $user->mobilePhone;
        // Hide the firstname rows by default
        $defaultStyle = $order === "bysurname"? "": " style = \"display:none\"";
        print "<tr class=\"$order\" $defaultStyle>";
        $addr = implode(', ', array($user->address1, $user->address2, $user->city, $user->postcode));
        $name = $order === "bysurname" ? implode(', ', array($user->lastName, $user->firstName)): implode(' ', array($user->firstName, $user->lastName));
        print "<td class=\"col0\" title=\"$addr\">$name</td>" .
            "<td class=\"col1\" title=\"$mobTitle \">$phone</td><td class=\"col2\"  " .
            "style=\"display:none\">$addr</td><td class=\"col3\"  style=\"display\">".
            "$user->primaryEmail</td><td class=\"col4\" style=\"display:none\">$user->mobilePhone</td>";
        print "</tr>";
    }
}

?>
