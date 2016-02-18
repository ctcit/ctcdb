<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
?>

<h2>Subscription details for <?php echo $name; ?></h2>
<?php

	if ($paid == 'Yes') {
		echo "<p>Your subscription payment of \$$sub has already been received.</p>";
		echo "<p>Many thanks!</p>";
	}
	else {
		echo "<p>Your subscription details are:</p>\n";
		echo "<table style=\"padding-left: 20pt\" id=\"subDetails\"><tr><td>Membership type:</td><td>$msType</td></tr>\n";
		echo "<tr><td>Subscription amount:</td><td>\$$sub</td></tr>\n";
		$date = getdate();
		if ($date['mon'] > 6) {
			echo "<tr><td>Late payment fee:</td><td>$10</td></tr>\n";
			$total = $sub + 10;
			echo "<tr><td>Total:</td><td>\$$total</td></tr>\n";
		}
		echo "</table>";
		echo "<p>Internet banking is the preferred payment method, using:</p>\n";
		echo "<table  style=\"padding-left: 20pt\" id=\"internetPaymentDetails\">\n";
		echo "<tr><td>Payee Name:</td><td>The Christchurch Tramping Club</td></tr>\n";
		echo "<tr><td>Payee A/N:</td><td>38-9017-0279838-00</td></tr>\n";
		echo "<tr><td>Particulars:</td><td>$login</td></tr>\n";
		echo "<tr><td>Code:</td><td>Subs</td></tr>\n";
		echo "<tr><td>Reference:</td><td>$msid</td></tr>\n";
		echo "</table>\n";
		echo "<p>However, if you're unable to do Internet banking you can send a cheque to:</p>".
		'<p style="padding-left: 20pt">The Treasurer<br />P.O. Box 527<br />Christchurch 8140.</p>';
		
		echo "<p>Please note that subscriptions don't show on our website as having been\n"
		."paid until we have manually entered the payment. This can take up to a week or two.</p>\n";
	}
?>
