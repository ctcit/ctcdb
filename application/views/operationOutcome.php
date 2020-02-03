<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
 
// This page outputs a report on whether or not the previous operation was
// successful. It displays a $message parameter as a level 3 header if
// $message is given, or uses the page title otherwise. The header is
// optionally followed by a paragraph of $extra info and/or a standard
// warning message to contact the webmaster (triggered by $tellWebmaster).

	if (isset($message))
		echo "<h2>$message</h2>";
	else
		echo "<h2>$title</h2>";

	if (isset($extraInfo))
		echo "<p>$extraInfo</p>";
		
	if (isset($tellWebmaster))
		echo '<p>Something has gone seriously wrong. Please report' .
	' this problem to the <a href="mailto:christchurchtrampingclub@gmail.com">webmaster</a></p>';
		
	// echo $this->load->helper('form');  // What was this all about???
	
?>


