<?php
    ob_end_clean();
    header("Content-Disposition: attachment; filename=\"{$gpxfilename}\"");
    echo $gpx;

