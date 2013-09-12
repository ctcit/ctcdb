<h1>Notify Trip Change</h1>
<p>For use only by trip leaders or people acting on their behalf, please.</p>
<p>This form is an alternative to txting +27 79 454 4321 with a message
    starting with the word CTC (or TRIP).</p>
<?php
    echo form_open("member/processTripChange");
?>

<p>Message sender: <?php echo $memberName; ?>
</p>
<h2>Message</h2>
<p>Enter your message below. Keep it short - think "txt message"!</p>
<textarea id="tripChangeMessageId" name="tripChangeMessage" rows="6" cols="60">
</textarea>
<br />
<input type="submit" value="Submit" class="submit" />
<?php
	global $CI;
	$ctcHome = $CI->config->item('joomla_base_url');
	$base = trim(base_url(),'/');
	echo "&nbsp;&nbsp;<button type=\"button\"
		  onclick=\"if(confirm('Return to CTC home page, without sending notification?')) top.location.href='$ctcHome'\">
		  Cancel</button>";

	echo form_close();
    ?>
<hr>
<p>Recent trip change notifications are listed
    <a href="http://www.ctc.org.nz/db/index.php/member/listTripChangeNotifications/0">here</a>.
    </p.
