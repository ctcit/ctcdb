<?php
    ob_end_clean();
    header("Content-Disposition: attachment; filename=\"{$gpxfilename}\"");
	header("Content-Type: text/gpx");
	echo $gpx;

