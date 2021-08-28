<h2>What would you like to do?</h2>
<p>Please click one of the following links.</p>
<ul>
<?php
echo "<li>".anchor($changePasswordUrl, "Change your password")."</li>";
echo "<li>".anchor($changeProfileUrl, "View/edit your personal details")."</li>";
echo "<li>".anchor($subsPaymentForm, "View your subscription information")."</li>";
echo "<li>".anchor($sendEmailUrl, "Send email to the club database administrator",
                    array("target"=>"_top"))."</li>";
?>
</ul>
Use the last option if you wish to change your newsletter-subscription option (which affects
your annual membership fee) or if you prefer to deal with a real human.
