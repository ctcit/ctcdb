<?php  if (!defined('BASEPATH')) exit('No direct script access allowed'); 


	if (isset($title)) {
		echo "<h1>$title</h1>";
	}
	else {
		echo "<h1>Generic query display</h1>";
	}
	
	echo form_open("queries/saveCsv/$resultid");
	echo "<p>" . form_submit('exportCSV', 'Save as CSV');
	echo form_close();
	
	echo form_open("queries/printMerge/$resultid");
	echo form_submit('printMerge', 'Print Merge');
	echo "&nbsp;with document: ";
	echo "<select name='doc_id'>";
	foreach ($mergeDocs as $doc) {
		echo "<option value=\"{$doc->id}\">{$doc->name}</option>";
	}
	echo "</select>";
	echo form_close('</p><p>');
	
	echo form_open("queries/emailMerge/$resultid");
	echo form_submit('emailMerge', 'Email Merge');
	echo "&nbsp;with document: ";
	echo "<select name='email_doc_id'>";
	foreach ($emailDocs as $doc) {
		echo "<option value=\"{$doc->id}\">{$doc->name}</option>";
	}
	echo "</select>";
	echo "&nbsp;&nbsp;Subject for email: <input name='subject' value='Email from CTC' />";
	echo form_close('</p><p>');
	
	$headings = $query->list_fields();
	array_unshift($headings,"Row#");
	$this->table->set_heading($headings);
	$rows = $query->result_array();
	$rowNum = 1;
	foreach ($rows as $row) {
		array_unshift($row, $rowNum++);
		$this->table->add_row($row);
	}
	$numCols = $query->num_fields() + 1;
	$tableOpenString = '<table id="genericQueryTable" class="oddEven" onload="selectRows(\'genericQueryTable\')">';
	for ($i=1; $i <= $numCols; $i++) {
		$tableOpenString .= "<col class=\"col$i\" />";
	}
	$tmpl = array ( 'table_open'  => $tableOpenString, 'row_start' => '<tr class="odd">' );
	$this->table->set_template($tmpl);
	echo $this->table->generate() . '</p>';
?>
<p></p>
<form action="0">
<input class="close_button" type="submit" value="Close Window" onclick="window.close()" />
</form>



