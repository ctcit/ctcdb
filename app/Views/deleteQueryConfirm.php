<h3>Comfirm query deletion</h3>
You're about to delete the query <b>'<?php echo $queryName; ?></b>'.

<form>
<?php
	$deleteUrl = site_url("queries/deleteQuery2/$id");
	$cancelUrl = site_url("queries/manageQueries");
	$homeUrl = site_url();
	echo form_input(array(
		'type'=>'button','class'=>'button','value'=>'OK','onclick'=>"window.location.href='$deleteUrl'"));
	echo form_input(array(
		'type'=>'button','class'=>'button','value'=>'Cancel','onclick'=>"window.location.href='$cancelUrl'"));
?>
</form>


