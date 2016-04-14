<?php  // The view that allows editing and testing of a single user query.

if (!defined('BASEPATH')) exit('No direct script access allowed'); ?>

<h1><?php
$this->load->helper('url');
$id = $this->form_validation->id;
$ownerId = $this->form_validation->queryOwnerId;
if ($id == 0)
	echo "New query";
else
	echo "Editing query"
?></h1>
<script type="text/javascript">
function invoke(buttonNum)
{
	if (buttonNum == 1) {  // The save button
		document.QueryForm.action = "<?php echo site_url("queries/editQuery/$id/$ownerId"); ?>";
		document.QueryForm.target = "_self";
	}
	else if (buttonNum == 2) { // The test button
		document.QueryForm.action = "<?php echo site_url("queries/testQuery"); ?>";
		document.QueryForm.target = "_blank";
	}
	else { // buttonNum == 3. Test in same window button
		document.QueryForm.action = "<?php echo site_url("queries/testQuery"); ?>";
		document.QueryForm.target = "_self";
	}
	document.QueryForm.submit();
}
</script>

<?php echo validation_errors(); ?>

<form name="QueryForm" class="QueryForm" action="" method="post">
<?php
	echo form_hidden('id', $this->form_validation->id);
	echo form_hidden('queryOwnerId', $this->form_validation->queryOwnerId);
?>
<table class="QueryForm">
<col class="col1" /><col class="col2" />
<tr>
<td>Query name: </td><td><input
	name="queryName" value="<?php echo $this->form_validation->queryName; ?>" type="text" size="70" />
</td></tr>
<tr><td></td><td>(only alpha-numeric + underscore characters, please)</td></tr>
<tr><td>Description:</td><td><textarea name="description" class="QueryFormTA" rows="5" cols="100">
<?php
	echo $this->form_validation->description;
?>
</textarea></td></tr>
<tr><td>Query:</td><td>
<textarea name="query" class="QueryFormTA" rows="30" cols="200">
<?php
	echo $this->form_validation->query;
?>
</textarea></td></tr></table>

<table>
	<tr>
		<td><input type="button" value="Save it" onclick="invoke(1)" /></td>
		<td><input type="button" value="Test it" onclick="invoke(2)" title="Run the query, displaying results in a new window." /></td>
<!--	<td><input type="button" value="Test in same window" onclick="invoke(3)"
		title="Run the query, displaying results in the same window. Use browser back button to get back to here." /></td> --->
	</tr>
</table>
</form>
<p><a class="back_button" href='javascript:history.go(-1)'>[ Back ]</a>
</p>
