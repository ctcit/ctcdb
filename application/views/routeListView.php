<?php  if (!defined('BASEPATH')) exit('No direct script access allowed'); ?>
<?php
    $base_url = config_item("base_url");
    $joomla_base_url = config_item("joomla_base");
    echo '<script src="'.$base_url.'/third-party/jquery.min.js" type="text/javascript"></script>';
    echo '<script src="'.$base_url.'/third-party/proj4.js" type="text/javascript"></script>';
    echo '<script type="text/javascript" src= "'.$base_url.'/scripts/route.js"></script>';
    echo '<link rel="stylesheet" type="text/css" href="'.$base_url.'/css/scrollingtable.css" />';
    global $userData, $can_edit_any, $can_edit_own, $userid;
    $userid = $userData['userid'];
    $roles = $userData['roles'];
    $can_edit_any = ($roles !== null) && count($roles) > 0;
    $can_edit_own = ($userid !== null) && ($userid !== 0);
?>
<h2>CTC Route Archive</h2>
<p><i>A GPX route shows the approximate route taken by a particular party on a particular day.
It should not be regarded as a recommended route or even necessarily a good route.
Also, permission from land-owners may be required.</p>
<table  style = "height:25px; border-spacing:0px; border-collapse: collapse; padding: 5px; ">
    <tr>
        <td>Filter:</td> 
        <td><div id = "filter" onchange = "OnSearch()" style="border:solid 1px gray;cursor:text;overflow:auto;width:200px;margin:0px;" contenteditable = "true" ></div></td>
        <td style = "display: none" id = "filterx"><div style="border:solid 1px gray;cursor: default; width:20px; text-align:center; " title = "Remove filter." onclick = "OnNoFilterClick()">X</div></td>
    </tr>
</table>
<table id = "progress" style = "height: 25px; border-spacing:0px; border-collapse: collapse; padding: 5px; display:none;">
    <tr>
        <td><div class="spinner"></td>
        <td><div type = "text" id = "progresstext" ></div></td>
    </tr>
</table>
<div class="scrollingtable">
    <div>
        <table class ="fixedheader">
            <thead id = "header">
                <tr style = "cursor: default">
                    <?php EmitEditHeader()?>
                </tr>
            </thead>
        </table>
        <div>
            <table id="routes">
                <tbody id = "body">
                    <?php EmitEditRows($routes)?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script type="text/javascript">
    $('body').on('focus', '[contenteditable]', function() {
        const $this = $(this);
        $this.data('before', $this.html());
    }).on('blur keyup paste input', '[contenteditable]', function() {
        const $this = $(this);
        if ($this.data('before') !== $this.html()) {
            $this.data('before', $this.html());
            $this.trigger('change');
        }
    });
    var filterText = sessionStorage.getItem("filter");
    if (filterText !== null){
        var filter = document.getElementById('filter');
        filter.innerText = filterText;
    }
    var body = document.getElementsByTagName("body")[0];
    //body.onload = OnLoad();
    window.addEventListener('resize', OnWindowResize);
    OnSearch();
    OnWindowResize();
</script>

<?php

function EmitEditHeader()
{
    global $can_edit_any;
    global $can_edit_own;
    $row  = '<th class = "col0"><input type = "checkbox" onclick="SelectAll(this)" title = "Select all items" style = "cursor:pointer;"></input></td>';
    $row .= '<th class="col1" align="left" id="caption">Description</th>'; // Brief description
    $row .= '<th class="col2"><a class="downloadfiles" title="Download selected files" onclick = "DownloadSelected(this)"></a></th>'; // Download selected
    $row .= '<th class="col3"><div id = 0 class="mapview" title="View selected items on map"  onclick = "ViewSelectedOnMap(this)"></div></th>';
    if ($can_edit_own)
      $row .= '<th class="col4"><div id = "0" class = "choosefiles" onclick = "SelectFiles(this)" title="Upload new Gpx files"></div> </th>'; // Upload
    else
        $row .= '<th class="col4"> </th>'; // Upload
    if ($can_edit_any)
        $row .= '<th class="col5"><div class="trashselected" title= "Delete selected items" onclick = "DeleteSelected(this)"></div></td>'; // Trash selected
    else 
        $row .= '<th class="col5"> </th>'; // Trash
    $row .= '<th class="col6" align="left" id = "bounds">Coords</th>'; // Average coords
    $row .= '<th class="col7" align="left" id = "trackdate">Date</th>'; // Approx date
    $row .= '<th class="col8" align="left" id = "originatorid">Owner</th>'; // Contributor
    $row .= '<th class="col9" align="left" id = "routenotes">Route notes</th>'; // Notes
    echo $row;
}

function EmitEditRows($routes)
{
    // Todo edit vs display
    if ($routes !== null) {
        foreach($routes as $route) {
            EmitRow($route);
        }
    }
}

function EmitRow($route)
{
    global $can_edit_any, $userid;
    $base_url = config_item("base_url");
    $owner_id = $route["originatorid"];
    $id = '"'.$route["id"].'"';
    $caneditthis = ($owner_id === $userid) || $can_edit_any;
    $downloadtitle = '"Download '.$route["gpxfilename"].'"';
    $viewonmaptitle = '"View '.$route["gpxfilename"].' on map"';
    $trashtitle = '"Delete '.$route["gpxfilename"].'"';
    $firstname = $route["firstName"];
    $lastname = $route["lastName"];
    $downloadhref = '"'.$base_url.'/index.php/routes/downloadGpx/'.$route["id"].'"';
    $caption = $route['caption'];
    $routenotes = $route['routenotes'];
    $trackdate = date('d/m/y', strtotime($route['trackdate']));
    if ($lastname != null && $firstname != null) {
        if (strlen($firstname) > 1) {
            $firstname = substr($firstname ,0, 1);
        }
        $contributor = $firstname." ".$lastname;
    } else {
        $contributor = "unknown";
    }
    /* @var $route type */
    $east = (int)(($route["left"] + $route["right"]) / 2) ;
    $north = (int)(($route["top"] + $route["bottom"]) / 2);
    $row = '<tr class = "route" style="display: none;">';
    $row .= '<td class = "col0"><input type = "checkbox" id='.$id
                                    .' title = "Click to select this route" data-caption="'
                                    .$caption.'"></input></td>';
    if ($caneditthis) {
        $row .= '<td class = "col1"><div contenteditable = "true" id = '.$id
                                     .' style = "cursor: text;"'
                                     .' class = "caption"'
                                     .' data-original="'.$caption.'"'
                                     .' onfocusout="FocusOutCaption(this)">'
                                     .$caption.'</div></td>';
    } else { 
        $row .= '<td class = "col1"><div id = '.$id
                                     .' class = "caption"'
                                     .' style = "cursor: default;">'
                                     .$route['caption']
                                     .'</div></td>';
    }
    $row.=  '<td class = "col2"><a id = '.$id.' class="downloadfile" title='.$downloadtitle.' href= '.$downloadhref.'></a></td>'; 
    // Todo escape $caption here
    $row .= '<td class = "col3"><div id = '.$id.' class="mapview" title='.$viewonmaptitle.'data-caption = "'.$caption.'" onclick = "ViewOnMap(this)"></div></td>';
    if ($caneditthis) {
        $row .= '<td class = "col4"><div id = '.$id.
                ' class = "choosefile" title = "Replace this gpx file" onclick = "SelectFiles(this)"></div></td>';
    } else { 
        $row .= '<td class = "col4"></td>';
    }
    if ($caneditthis) {
        $row .= '<td class = "col5"><div id = '.$id.' class="trashfile" title='.$trashtitle.' onclick = "Delete(this)"></div></td>';
    } else {
        $row .= '<td class = "col5"></td>';
    }
    $row .= '<td class = "col6" ><div onclick = "SelectNearby(this)" title = "Click to select items nearby to these coords">'.$east.' '.$north.'</div></td>';
    $row .= '<td class = "col7" ><div>'.$trackdate.'</div></td>';
    $row .= '<td class = "col8"><div>'.$contributor.'</div></td>';
    if ($caneditthis) {
        $row .= '<td class = "col9"><div contenteditable = "true" id = '.$id
                                     .' style = "cursor: text;"'
                                     .' class="routenotes"'
                                     .' data-original="'.$routenotes.'"'
                                     .' onfocusout="FocusOutRouteNotes(this)">'
                                     .$routenotes.'</div></td>';
    } else { 
        $row .= '<td class = "col9"><div class = "routenotes">'.$route["routenotes"].'</div></td>';
    }
    $row .= '</tr>';
    echo $row;
}
?>
