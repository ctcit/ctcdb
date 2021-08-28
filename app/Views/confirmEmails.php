<h3>Confirm pending bulk email</h3>
<p>
You are about to send an email like the following
to <?php echo count($recipients); ?> recipients (listed below).
</p><hr />
Subject: <?php echo $subject; ?>
<pre>
<?php  echo $message; ?>
</pre>
<hr />
<p>That email will go to the following recipients:
<?php
$separator = '';
foreach ($recipients as $recipient) {
   	echo $separator.$recipient;
   	$separator = ", ";
}
?>
</p>
<?php
	echo form_open("queries/emailMerge2/$batchId");
	helper('form');
	echo form_hidden('subject', $subject);
        echo form_hidden('nRecipients', count($recipients));
	echo form_submit("submit", "OK");
	echo form_close();
	echo "<p>Note: if you click OK, the mails will be queued for transmission
            by a background task. Sending an email to the whole club may take
            up to an hour due to the webhosting service limits on outgoing email traffic.</p>";
?>


