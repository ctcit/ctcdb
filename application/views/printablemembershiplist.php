<?php  if (!defined('BASEPATH')) exit('No direct script access allowed'); ?>
<h2>CTC Membership List (sorted by <?php echo $surnameFirst ? "Last name": "First name"?>)</h2>
<p>Please note that this list is intended for the use of Christchurch Tramping Club members only and
for Christchurch Tramping Club related matters only.
</p>
<p>
Please respect the privacy of your fellow
members. 
</p>
<p>
In particular, please do not use emails obtained from this list for mass mailouts to
club members.
</p>
<p>
Such mailouts should be sent to the
<a href="mailto:members@ctc.org.nz">moderated members mail list</a>.</p>
<table>
<tr><th class=\"col0\">Name</th><th class=\"col1\">Phone</th><th class=\"col2\">Address</th><th class=\"col3\">Email</th></tr>
<?php
$rowClass = "sectiontableentry1";
// Controller has filled $members
foreach ($members as $user) {
    print "<tr class=\"$rowClass\" style=\"font-size:10px\">";
    $name = $surnameFirst ? implode(', ', array($user->lastName, $user->firstName)): implode(' ', array($user->firstName, $user->lastName));
    $address = implode(', ', array($user->address1, $user->address2, $user->city, $user->postcode));
    $phone = trim($user->homePhone) !== '' ? $user->homePhone: $user->mobilePhone;
    $email = trim($user->primaryEmail) != '' ? $user->primaryEmail: $user->secondaryEmail;
    print "<td class=\"col0\">$name</td><td class=\"col1\">$phone</td><td class=\"col2\">$address</td><td class=\"col3\">$email</td>";
    print "</tr>";
    if ($rowClass == "sectiontableentry1") {
        $rowClass = "sectiontableentry2";
    }
    else {
        $rowClass = "sectiontableentry1";
    }
}
echo "</table>";
?>