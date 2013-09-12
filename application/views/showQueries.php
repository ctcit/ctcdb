<?php  if (!defined('BASEPATH')) exit('No direct script access allowed'); ?>
<script type="text/javascript">
function switch_user()
{
	document.QueryList.action = "<?php echo site_url("queries/switchUser/"); ?>";
	document.QueryList.submit();
}
</script>

<h1><?php echo $header; ?></h1>
<?php
$this->load->helper('form');
if (isset($switchUserList) && count($switchUserList) > 0) {
	echo "<p>";
	echo '<form name="QueryList" class="QueryList" action="" method="post">';
	echo "Switch to user:";
	echo form_dropdown('NewUser', $switchUserList, $currentUserId, 'onchange="switch_user()"');
	echo '</form>';
	echo "</p>";
}
?>
<p>
Click 'New Query' to create a new query or the appropriate link in the table below to run,
edit or delete an existing query.

<?php
echo "<p>" . anchor("queries/editQuery/0/$currentUserId", "New Query") . "</p>";
$numCols = count($queryTable);
$tableOpenString = '<table id="querySelect" class="oddEven">';
for ($i=1; $i<=$numCols; $i++) {
	$tableOpenString .= "<col class=\"col$i\" />";
}
$tmpl = array ( 'table_open'  => $tableOpenString, 'row_start' => '<tr class="odd">' );
$this->table->set_template($tmpl);
echo $this->table->generate($queryTable);
?>
</p>
<p>
<a class="back_button" href='javascript:history.go(-1)'>[ Back ]</a>
</p>