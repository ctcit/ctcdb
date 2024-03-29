<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title><?php echo $title ?></title>
    <?php
       echo '<link rel="stylesheet" href="'.base_url('/third-party/leaflet.css').'" />';
    ?>
    <style>
        html { height: 100% }
        body { height: 100%; margin: 10px; padding: 10px;}
        .map { height: 100%; width: 100% }
        .sheet-div-icon { text-align: center; }
        .sheet-code { font-size: 11pt; font-weight: normal; }
        .sheet-name { font-size: 10pt; font-weight: normal }
    </style>
</head>
<body>
    <div id="map" class="map"></div>
    <input id="selected-maps" type="hidden" ></input>
    <input id="routeIds" type= "hidden" value = "<?php echo $routeIds ?>"></input>
    <?php
    echo '<script src="'.base_url('/third-party/jquery.min.js').'" type="text/javascript"></script>';
    echo '<script src="'.base_url('/third-party/proj4.js').'" type="text/javascript"></script>';
    echo '<script src="'.base_url('/third-party/leaflet.js').'" type="text/javascript"></script>';
    echo '<script src="'.base_url('/third-party/gpx.min.js').'" type="text/javascript"></script>';
    echo '<script src="'.base_url('/third-party/Leaflet.Editable.js').'" type="text/javascript"></script>';
    echo '<script src="'.base_url('/scripts/routeMapping.js').'" type="text/javascript"></script>';

    ?>
    <Script>
        document.getElementById('map').style.visibility = 'hidden';
        var map = InitialiseMap(L);
        var routeIds = $('#routeIds').val();
        if (routeIds.length > 0)
            AddGpxData(L, map, routeIds);
    </Script>
</body>
</html>
