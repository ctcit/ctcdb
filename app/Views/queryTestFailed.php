<h3>Query Test Failed!</h3>
<?php
	if (isset($validation)) {
		echo $validation->listErrors('ctcList');
	}
?>

<form action="0">
<input class="close_button" type="submit" value="Close Window" onclick="window.close()" />
</form>
