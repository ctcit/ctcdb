<p>Please choose one of the following links.</p>
<ul>
<?php
echo "<li>".anchor($changePasswordUrl, "Change your password")."</li>";
echo "<li>".anchor($changeProfileUrl, "View/edit your personal details")."</li>";
echo "<li>".anchor($subsPaymentForm, "View your subscription information")."</li>";
echo "<li>".mailto($membershipEmail, "Send email to the Membership Officer")."</li>";
?>
</ul>
