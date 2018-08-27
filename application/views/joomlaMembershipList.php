<?php  if (!defined('BASEPATH')) exit('No direct script access allowed'); ?>
<h2>CTC Membership List</h2>
<p>The membership list below is formatted for on-screen viewing.
Printable versions are also available:
<ul>
	<li><a
		href="http://www.ctc.org.nz/index2.php?option=com_chronocontact&pop=1&page=0&chronoformname=PFMembershipList"
		target="_blank"> printable version, sorted by first name</a></li>
	<li><a
		href="http://www.ctc.org.nz/index2.php?option=com_chronocontact&pop=1&page=0&chronoformname=PFMembershipListSurnameSort"
		target="_blank"> printable version, sorted by last name</a></li>
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

<table width="75%">
	<tr>
		<td>Address: <input type="checkbox" id="showAddress"
			onclick="javascript:showHideCols(2)"></td>
		<td>Email: <input type="checkbox" id="showEmail"
			onclick="javascript:showHideCols(3)" CHECKED></td>
		<td>Mobile phone: <input type="checkbox" id="showMob"
			onclick="javascript:showHideCols(4)"></td>
	</tr>
</table>


<h3>Membership List</h3>
Please email corrections to
<a href="mailto:Susan@toniq.co.nz?subject=Change of CTC Contact Details">Susan
Pearson</a>
.
<?php
// TODO: Get rid of the specific DB connect code. Drive this from
// a method of the member controller.
global $my;
global $database;
global $mosConfig_absolute_path;
require_once "$mosConfig_absolute_path/includes/ctcfuncs2.php";

$userID = $my->username;
if ($userID == "") {
	echo "Sorry, but you must be logged in to see the membership list.<br />";
}
else {
	$host = 'localhost';
	$user = 'userman';
	$password = 'susanrulesok';
	$dbase = 'ctc';
	$dbprefix = '';
	$conn = mysql_connect($host, $user, $password);
	$db = mysql_select_db($dbase);
	$query = mysql_query(
	"SELECT concat(firstName,' ',lastName) as FullNameByFirstName, 
			primaryEmail as PrimaryEmail,
			homePhone as HomePhone,
			mobilePhone as MobilePhone,
			concat(address1, ', ', address2,', ', city) as Address
	 FROM members, memberships
	 WHERE membershipId = memberships.id
	 AND statusAdmin='Active'
	 ORDER BY FullNameByFirstName");

	echo "<table id=\"members\">";
	echo "<thead>";
	echo "<tr class=\"$rowClass\"><td class=\"col0\"><b>Name</b></td><td class=\"col1\"><b>Phone</b></td><td class=\"col2\"  style=\"display:none\"><b>Address</b></td><td  class=\"col3\"  style=\"display\"><b>Email</b></td><td class=\"col4\"
style=\"display:none\"><b>Mobile</b></td></tr>";
	echo "</thead><tbody>";
	$rowClass = "sectiontableentry1";
	$user = mysql_fetch_object($query);
	while ($user !== FALSE) {
		$phone = $user->HomePhone;
		$mobTitle = $user->MobilePhone;
		if ($mobTitle == "") $mobTitle = "No mobile";
		print "<tr class=\"$rowClass\">";
		$addr = "$user->Address";
		print "<td class=\"col0\" title=\"$addr\">$user->FullNameByFirstName</td>" .
			"<td class=\"col1\" title=\"$mobTitle \">$phone</td><td class=\"col2\"  " .
			"style=\"display:none\">$addr</td><td class=\"col3\"  style=\"display\">".
			"$user->PrimaryEmail</td><td class=\"col4\" style=\"display:none\">$user->MobilePhone</td>";
		print "</tr>";
		if ($rowClass == "sectiontableentry1") {
			$rowClass = "sectiontableentry2";
		}
		else {
			$rowClass = "sectiontableentry1";
		}
		$user = mysql_fetch_object($query);
	}
}
?>
