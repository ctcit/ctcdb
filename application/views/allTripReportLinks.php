<?php  if (!defined('BASEPATH')) exit('No direct script access allowed'); ?>

<h2>Links to all CTC Trip Reports</h2>
<p>This page exists only to act as a trip report site map for search engines,
    which currently (2016) can't follow links in Angular Apps.
<?php
$year = NULL;

foreach ($trips as $trip) {
    if ($trip->year !== $year) {
        $year = $trip->year;
        echo "<h3>$year</h3>";
    }
    $base = config_item('joomla_base_url');
    $url = "$base/index.php/trip-reports?goto=tripreports%2F{$trip->id}";
    echo "<a href=\"$url\">$trip->title</a><br>";
}





