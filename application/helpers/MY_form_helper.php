<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

// form_date constructs a string for use (via 'echo' for example) as a date input field. 
// The input field consists of a standard text input field with the given '$fieldName'
// and '$id' followed by a small calendar icon that when clicked brings up a calendar that the
// use can use to enter a date graphically (via Javascript of course -- see the script
// show_calendar in the base scripts directory).
// $value is the initial setting for the date field, in NZ-style dd-mm-yyyy format e.g. 03-11-2007.
function form_date($fieldName, $id, $value, $class='dateinput') {
	$imageDirUrl = base_url() . "images";
	return "<input type=\"text\" class=\"$class\" name=\"$fieldName\" id=\"$id\" value=\"$value\">" .
	"<a href=\"javascript:show_calendar('$id', '-', '$imageDirUrl');\">" .
	"<img src=\"$imageDirUrl/cal.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"Calendar image\" title=\"Calendar helper\">".
	"</a>";
}
?>