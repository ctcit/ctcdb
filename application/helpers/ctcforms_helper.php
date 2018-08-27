<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

// Miscellaneous functions to help with the generation
// of various CTC forms, such as members, memberships and couples.

function displayFieldsInTable($fields, $filter=NULL)
// Output all the given fields with 'normal' and hidden fields first,
// then bool fields and lastly textArea fields.
{
	for ($pass = 1; $pass <= 3; $pass++) {
		foreach (array_keys($fields) as $fieldName) {
			if ($filter === NULL || preg_match($filter, $fieldName)) {
				displayFieldAsTableRow($fieldName, $fields[$fieldName], $pass);
			}
		}
	}
}

function displayFieldAsTableRow($fieldName, $field, $pass)
// Display the given field as a row of a table, but only
// when appropriate to the particular pass. Bool fields are
// drawn on pass 2, textArea fields on pass 3, all others on pass 1.
// Admin fields are not displayed.
{
	$type = $field['type'];
	if ($type == 'admin') return;
	if ($pass ==1 && ($type == 'bool' || $type == 'textArea')) return;
	if ($pass == 2 && $type != 'bool') return;
	if ($pass == 3 && $type != 'textArea') return;
	$label = $field['label'];
	$value = $field['value'];
	echo "<tr><td>$label</td><td>";
	if ($type == 'enum') {
		$values = array();
		foreach ($field['values'] as $v) {
			$values[$v] = $v;
		}
		echo form_dropdown($fieldName, $values, $value);
	}
	else if ($type == 'date') echo form_date($fieldName, $fieldName, $value);
	else if ($type == 'bool') echo form_dropdown($fieldName, array('Yes'=>'Yes','No'=>'No'), $value);
	else if ($type == 'textArea') echo form_textarea(
					array('name'=>$fieldName, 'value'=>$value, 'rows'=>3, 'col'=>80));
	else if ($type == 'text') echo form_input($fieldName, $value);
	else if ($type == 'hidden') echo form_hidden($fieldName, $value);
	echo "</td></tr>";
}

?>
