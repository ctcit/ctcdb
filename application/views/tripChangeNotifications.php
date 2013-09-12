<?php

/*
 * View to display recent text messages
 */

echo "<h1>Recent trip change notifications</h1>\n";
echo "<table class='tripNotificationsTable'><tr><th>Time</th><th>Mob</th><th>Name</th><th>Message</th></tr>\n";
$texas = new DateTimeZone("America/Mexico_City");
$nz = new DateTimeZone("Pacific/Auckland");
foreach($texts as $text) {
    $dateTime = new DateTime($text->timestamp, $texas);
    $dateTime->setTimeZone($nz);
    $dateTimeDisplay = $dateTime->format('Y-m-d H:i:s');
    echo "<tr><td>$dateTimeDisplay</td><td>{$text->mob}</td><td>{$text->name}</td><td>{$text->message}</td></tr>\n";
}
echo "</table>\n";

?>
