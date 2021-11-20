<?php

// Miscellaneous functions to help with the generation
// of various CTC forms, such as members, memberships and couples.

// Output all the given fields with 'normal' and hidden fields first,
// then bool fields and lastly textArea fields.
function displayFieldsInTable($fields, $filter=null)
{
    for ($pass = 1; $pass <= 3; $pass++) {
        foreach (array_keys($fields) as $fieldName) {
            if ($filter === null || preg_match($filter, $fieldName)) {
                displayFieldAsTableRow($fieldName, $fields[$fieldName], $pass);
            }
        }
    }
}

// Display the given field as a row of a table, but only
// when appropriate to the particular pass. Bool fields are
// drawn on pass 2, textArea fields on pass 3, all others on pass 1.
// Admin fields are not displayed.
function displayFieldAsTableRow($fieldName, $field, $pass)
{
    $type = $field['type'];
    if ( ($type == 'admin') ||
         ($pass ==1 && ($type == 'bool' || $type == 'textArea')) ||
         ($pass == 2 && $type != 'bool') ||
         ($pass == 3 && $type != 'textArea') ) {
             return;
    }
    $label = $field['label'];
    $value = $field['value'];
    echo "<tr><td>$label</td><td>";
    if ($type == 'enum') {
        $values = array();
        foreach ($field['values'] as $v) {
            $values[$v] = $v;
        }
        echo form_dropdown($fieldName, $values, $value);
    } else if ($type == 'date') {
        echo form_date($fieldName, $fieldName, $value);
    } else if ($type == 'bool') {
        echo form_dropdown($fieldName, array('Yes'=>'Yes','No'=>'No'), $value);
    } else if ($type == 'textArea') {
        echo form_textarea(
                    array('name'=>$fieldName, 'value'=>$value, 'rows'=>3, 'col'=>80));
    } else if ($type == 'text') {
        echo form_input($fieldName, $value ?? '');
    } else if ($type == 'hidden') {
        echo form_hidden($fieldName, $value);
    }
    echo "</td></tr>";
}

// form_date constructs a string for use (via 'echo' for example) as a date input field.
// The input field consists of a standard text input field with the given '$fieldName'
// and '$id' followed by a small calendar icon that when clicked brings up a calendar that the
// use can use to enter a date graphically (via Javascript of course -- see the script
// show_calendar in the base scripts directory).
// $value is the initial setting for the date field, in NZ-style dd-mm-yyyy format e.g. 03-11-2007.
function form_date($fieldName, $id, $value, $class='dateinput') {
	$imageDirUrl = base_url("images");
	return "<input type=\"text\" class=\"$class\" name=\"$fieldName\" id=\"$id\" value=\"$value\">" .
	"<a href=\"javascript:show_calendar('$id', '-', '$imageDirUrl');\">" .
	"<img src=\"$imageDirUrl/cal.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"Calendar image\" title=\"Calendar helper\">".
	"</a>";
}

?>