<?php

if (count($errors) != 0) {
	echo "<h2>**** Membership payments update had problems!! ****<h2>";
	echo "<ul>";
	foreach ($errors as $error) {
		echo "<li>$error</li>";
	}
	echo "</ul>";
}
else {
	echo "<h1>CDCDB: Payments Recorded</h1>Your payments have been recorded.";
}

?>
<form action="0">
<input class="close_button" type="submit" value="Close Window" onclick="window.close()" />
</form>