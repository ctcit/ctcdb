<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');
?>
<h1>Print Envelopes Query</h1>
<?php 
$query = htmlspecialchars($query);
$query = str_replace("\n", "<br />", $query);
echo $query . "<br />";
?>
