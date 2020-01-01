/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
var error_messages = [];  // array of strings
var bad_file_names = [];  // files that are invalid xml + files that failed the download for some reason // todo log these
var good_file_names = []; // files that are valid xml
var nztm_bounds_data = [];// array of bounds (if valid gpx)
var cmtdesc_data = [];    // array of strings (if valid gpx)
var trackdate_data = [];
var projEPSG2193 = null;

// List View load
function OnLoad() {
    // Constant retrieved from server-side via JSP
    var maxVisibleRows = 14;

    var table = document.getElementById('archiveItems');
    var wrapper = table.parentNode;
    var rowsInTable = table.rows.length;
    var header = document.getElementById('header');
    var body = document.getElementById('body');
    var height = header.getBoundingClientRect().height;
    var cVisibleRows = 0;
    for (var i = 0; i < rowsInTable; i++) {
        if (table.rows[i].style.display !== "none"){
          height += table.rows[i].getBoundingClientRect().height;
          cVisibleRows++;
          if (cVisibleRows >= maxVisibleRows) // Scroll for more
              break;
        }
    }
    wrapper.style.height = table.getBoundingClientRect().top + height + 5 + "px";
    body.style.height = height + "px";
}

// Make header match data columns
function OnWindowResize(){
    var colNumber=10; //number of table columns
    for (var i=0; i<colNumber; i++){
        var thWidth=$("#archiveItems").find("th.col"+i).width();
        var tdWidth=$("#archiveItems").find("td.col"+i).width();      
        if (thWidth !== tdWidth)                    
          $("#archiveItems").find("th.col" + i).width(tdWidth);
    }  
}


function DoDeleteArchiveFiles(p_archive_ids){ // Array of id's to delete
    var formdata = new FormData();
    formdata.append('action', 'DeleteArchiveItems');
    var archive_ids = JSON.stringify(p_archive_ids);
    formdata.append('archive_item_ids', archive_ids);
    var getUrl = window.location;   
    var url = getUrl .protocol + "//" + getUrl.host + getUrl.pathname.split('index.php')[0] + "index.php/archiveRest/archiveItem";
    jQuery(function ($) {
        $.ajax({
            url: url,
            type: 'POST',
            data: formdata,
            cache: false,
            contentType: false,
            processData: false,
            success: function (data) {
                result = JSON.parse(data);
                if (result.success) {
                    alert(result.message);
                    location.reload(true); // TODO change to dom if can be bothered
                } else
                    alert(result.message);
            },
            error: function (data) {
                alert("Archive removal failed");
            }
        });
    });
}

function DoUpload(p_files, p_idArchive) {
    var archive_id = p_idArchive;
    if (p_files.length === 0)
        return;
    error_messages = []; 
    bad_file_names = []; 
    good_file_names = [];
    nztm_bounds = [];    
    cmtdesc_data = [];
    trackdate_data = [];
    projEPSG2193 = proj4('+proj=tmerc +lat_0=0 +lon_0=173 +k=0.9996 +x_0=1600000 +y_0=10000000 +ellps=GRS80 +towgs84=0,0,0,0,0,0,0 +units=m +no_defs');
    if (window.Worker){
        // Check if valid gpx files
        gpxworker = new Worker("../../Scripts/gpxvalidator.js");
        gpxworker.postMessage(p_files);
        gpxworker.onmessage = function(e){
            if (e.data.length ===2)
                // Check file is valid xml and search it for bounds,description and date data
                ValidateFile(e);
            else
                // Finished checking list
                DoUploadFiles(p_files, archive_id);
        };
    }else{
        // Or just try to upload them and leave it for the end user to discover the problem
        DoUploadFiles(p_files, archive_id);
    }
}

function DoUploadFiles(p_files, p_archive_id){
    if (error_messages.length > 0)
        if (!DoAlert(error_messages))
            return;
    var spinner = document.getElementsByClassName('spinner')[0];
    spinner.style.display = 'block';
    var aAjaxCalls = [];
    for (var i = 0; i < p_files.length; i++){
        var file = p_files[i];
        if (!IsBadFile(file)){
            GetUploadAjax(file, p_archive_id, aAjaxCalls);
        }
    };
    $.when.apply($, aAjaxCalls)
     .then(function() {
        var progress = document.getElementById('progress');
        progress.style.display = "none";
        cUploaded = p_files.length - bad_file_names.length;
        alert(cUploaded + " file" + ((cUploaded !== 1) ? "s":"") + " loaded");
        location.reload(true);
    });
}

function GetUploadAjax(p_file, p_archive_id, p_aAjaxCalls){
    // retrieve 
    var formdata = new FormData();
    formdata.append('action', 'UploadArchiveItem');
    formdata.append('gpxfile', p_file);
    formdata.append('archive_id', p_archive_id);
    var i = GoodFileIndex(p_file);
    formdata.append('gpxfilename', p_file.name);
    formdata.append('caption', RemoveExtension(p_file.name));
    formdata.append('routenotes', (i >= 0 && i < cmtdesc_data.length) ? cmtdesc_data[i]: '');
    if (i >= 0 && i < nztm_bounds_data.length){
        var bounds = nztm_bounds_data[i]; // Should be an array l,t,r,b
        formdata.append('left', bounds.left);
        formdata.append('top', bounds.top);
        formdata.append('right', bounds.right);
        formdata.append('bottom', bounds.bottom);
    }
    formdata.append('trackdate', (i >= 0 && i < trackdate_data.length) ? trackdate_data[i]: '');
    var getUrl = window.location;   
    var url = getUrl .protocol + "//" + getUrl.host + getUrl.pathname.split('index.php')[0] + "index.php/archiveRest/archiveItem";
    p_aAjaxCalls.push(
        $.ajax({
            url: url,
            type: 'POST',
            data: formdata,
            cache: false,
            contentType: false,
            processData: false,
            success: function (data) {
                result = JSON.parse(data);
                if (!result.success) {
                    bad_file_names.push(p_file.name);
                    error_messages.push("Database error");
                }
            },
            error: function (data) {
                bad_file_names.push(p_file.name);
                error_messages.push("Unknown error");
            }
        })
        .done(function(){
            var progress = document.getElementById('progress');
            var progresstext = document.getElementById("progresstext");
            progresstext.textContent = p_file.name + ' processed';
            progress.style.display = "table";
         })
    );

}


function Select(p_sender){ // Not used at the moment
    // See how many currently selected
    lSelected = document.querySelectorAll('td.col0 input');
    var cSelected = 0;
    for (var i = 0; i < lSelected.length; i++){
        var checkbox = lSelected[i];
        if (checkbox.checked)
            cSelected++;
    }
}

function SelectNearby(p_sender){
    base_coords = p_sender.innerText.split(" ");
    var proximity = prompt("Enter proximity in km", "10.0");
    if (proximity !== null){
        var d = parseFloat(proximity);
        if (isNaN(d) || !isFinite(d) || d > 100)
            alert('Invalid proximity: ' + d);
        else{
            var coords_col = "." + p_sender.parentNode.className;
            table_rows = document.querySelectorAll('table#archiveItems tbody tr');
            for (var i = 0; i < table_rows.length; i++){
                row = table_rows[i];
                if (row.style.display !== "none"){
                    coordscol = row.querySelector(coords_col);
                    coords = coordscol.innerText.split(" ");
                    if (Math.sqrt((base_coords[0] - coords[0]) * (base_coords[0] - coords[0]) + (base_coords[1] - coords[1]) * (base_coords[1] - coords[1])) <= d * 1000){
                        checkbox = row.firstChild.firstChild;
                        checkbox.checked = true;
                    }
                }
            }
        }
    }
    
}

function SelectAll(p_sender){
    // See how many currently selected
    lSelected = document.querySelectorAll('td.col0 input');
    for (var i = 0; i < lSelected.length; i++){
        var checkbox = lSelected[i];
        if (checkbox.parentElement.parentElement.style.display !== "none")
            checkbox.checked = p_sender.checked;
    }
    p_sender.title = p_sender.checked? "Unselect all visible items":"Select all visible items";
}

function Delete(p_sender){
    if (confirm(p_sender.title + '? This will delete all route data. Do you wish to continue?')){
        DoDeleteArchiveFiles([p_sender.id]);
    }
}

function DeleteSelected(p_sender){
   // See how many currently selected
    lSelected = document.querySelectorAll('td.col0 input');
    var cSelected = 0;
    var aSelected = [];
    for (var i = 0; i < lSelected.length; i++){
        var checkbox = lSelected[i];
        if (checkbox.checked && checkbox.parentElement.parentElement.style.display !== "none"){
            cSelected++;
            aSelected.push(checkbox.id);
        }
    }
    var bConfirmed = false;
    if (cSelected === 0)
        alert("Nothing selected");
    else if (confirm("Delete " + cSelected + " route" + ((cSelected !== 1) ? "s" : ""))){
        bConfirmed = true
        if (cSelected > 5){
            if (!confirm("Are you REALLY REALLY sure you want to delete " + cSelected + " route" + ((cSelected !== 1) ? "s" : "")))
              bConfirmed = false;
        }
        if (bConfirmed)
            DoDeleteArchiveFiles(aSelected);
    }    
}

function DoViewOnMap(p_archive_ids, p_title){
    archive_ids = p_archive_ids.join(":");    
    mapwindow = window.open('showArchiveMapping/' + archive_ids + '/' + p_title, '_blank');
}

function ViewOnMap(p_sender){
    caption = p_sender.getAttribute("data-caption");
    caption = caption.replace(/[^a-zA-Z0-9~%.:_-]/g, " ");
    DoViewOnMap([p_sender.id], caption);
}

function ViewSelectedOnMap(p_sender){
    // Todo check caption validity for title purposes
    lSelected = document.querySelectorAll('td.col0 input');
    var cSelected = 0;
    var aSelected = [];
    caption = '';
    for (var i = 0; i < lSelected.length; i++){
        var checkbox = lSelected[i];
        if (checkbox.checked && checkbox.parentElement.parentElement.style.display !== "none"){
            cSelected++;
            aSelected.push(checkbox.id);
            if (cSelected > 0  && cSelected <= 3)
                caption += ': ';
            if (cSelected <= 3)
              caption += checkbox.getAttribute("data-caption");
        }
    }
    if (cSelected >3)
        caption += ": " + (cSelected - 3) + " others";
    caption = caption.replace(/[^a-zA-Z0-9~%.:_-]/g, " ");
    if (cSelected === 0)
        alert("Nothing selected");
    else
        DoViewOnMap(aSelected, caption); 
}

function DeleteIframe (iframe) {
    iframe.remove(); 
}

function Timeout(func, time) {
      var args = [];
      if (arguments.length >2) {
           args = Array.prototype.slice.call(arguments, 2);
      }
      return setTimeout(function(){ return func.apply(null, args); }, time);
 }


function CreateIFrame(p_a){
    var progresstext = document.getElementById('progresstext');
    progresstext.textContent = p_a.getAttribute("title");
    var iframe = $('<iframe style="display:none"></iframe>');
    iframe[0].src= p_a.getAttribute("href");
    $('body').append(iframe);
    // This is a bit kludgy - some talk about using cookies to tell when actual download happens
    Timeout(DeleteIframe, 60000, iframe);             
}

function HideProgress(){
    var progress = document.getElementById('progress');
    progress.style.display = 'none';    
}

function DownloadSelected(p_sender){
    var isIE = (navigator.userAgent.indexOf("MSIE") !== -1);
    lSelected = document.querySelectorAll('td.col0 input');
    var cSelected = 0;
    var wait = 1000;//(isIE ? 1000 : 0);
    for (var i = 0; i < lSelected.length; i++){
        var checkbox = lSelected[i];
        if (checkbox.checked && checkbox.parentElement.parentElement.style.display !== "none"){
            cSelected++;
            if (cSelected === 1){
                progress.style.display = 'table';
            }                
            var a = checkbox.parentElement.parentElement.querySelector('a.downloadfile');
            Timeout(CreateIFrame,wait * cSelected, a);
        }
    }
    if (cSelected === 0)
        alert("Nothing visible selected");
    else
        Timeout(HideProgress, wait * (cSelected + 1));
    
 }

var g_choosefiles;
function SelectFiles(p_sender){
    idChooser = p_sender.id;
    //choosefiles doesn't need to be visible
    g_choosefiles = document.createElement('input');
    //g_choosefiles.id = idChooser;
    g_choosefiles.type = 'file';
    g_choosefiles.class = 'inputFile';
    g_choosefiles.title = "Choose gpx file" + (idChooser === "0") ? "s": "";
    g_choosefiles.style = "display:none";
    if (idChooser === "0")
        g_choosefiles.setAttribute('multiple', '');
    //g_choosefiles.value = "";
    g_choosefiles.addEventListener('change', UploadGpxFiles.bind(null, idChooser), false/*{once : false}*/);
    uploadbutton = document.activeElement;
    uploadbutton.parentElement.appendChild(g_choosefiles);
    g_choosefiles.click();
};

function UploadGpxFiles(p_idArchive) {
    if (window.File && window.FileReader && window.FileList && window.Blob) {
        // Great success! All the File APIs are supported.
        if (p_idArchive !== "0")
            if (!confirm('This will replace this gpx file and associated data with ' + g_choosefiles.files[0].name + '\nContinue upload and replacement?'))
                return;
        DoUpload(g_choosefiles.files, p_idArchive);
    } else {
      alert('The File APIs are not fully supported in this browser.');
      return;
    }
}

function GetCmtDescDataFromElement(p_element){
    var result = '';
    var cmts = p_element.getElementsByTagName('cmt'); // Should only be one at most
    if (cmts.length > 0 && cmts[0].innerHTML !== '')
        result += cmts[0].innerHTML;
    else{
        // cmts was originally intended for verbose descriptions but everyone seems very confused by this
        // and often software incorrectly uses desc for this purpose so we will return desc if there is no cmt
        // and desc element is different from the name element
        var desc = p_element.getElementsByTagName('desc');
        var name = p_element.getElementsByTagName('name');
        var descText = (desc.length === 0) ? "": desc[0].innerHTML;
        if (descText.length > 0 && name.length > 0 && name[0].innerHTML.length > 0){
            if (descText.toLowerCase() === name[0].innerHTML.toLowerCase())
                result = "";
            else
                result = descText;
        } else 
            result = descText;
    }
    return result;    
}

// This attempts to gather up internal documentation from the gpx
// It is likely the contributor will need to edit this later
function GetCmtDescData(p_document){
    var result = '';
    var wpts = p_document.getElementsByTagName('wpt');
    for (var i = 0; i < wpts.length; i++){
        var wpt = wpts[i];
        result += GetCmtDescDataFromElement(wpt);
    }        
    var trks = p_document.getElementsByTagName('trk');
    for (var i = 0; i < trks.length; i++){
        var trk = trks[i];
        result += GetCmtDescDataFromElement(trk);
    }  
    return result;
}

function AdjustBounds(p_pt, p_bounds){
    // Find lat - lon and convert to NZTM and adjust p_bounds as needed
    var attrs = p_pt.attributes;
    var lat = 0.0;
    var lon = 0.0;
    for(var i = attrs.length - 1; i >= 0; i--) {
        if (attrs[i].nodeName === 'lat')
            lat = parseFloat(attrs[i].nodeValue);
        else if (attrs[i].nodeName === 'lon')
            lon = parseFloat(attrs[i].nodeValue);
    }
    if (lat !== 0.0 && lon !== 0.0){
      var pt = {x: lon, y: lat};
      var nztm = projEPSG2193.forward(pt);
      var e = nztm.x.toFixed(0);
      var n = nztm.y.toFixed(0);
      if (e < p_bounds.left)
          p_bounds.left = e;
      if (e > p_bounds.right)
          p_bounds.right = e;
      if (n > p_bounds.top)
          p_bounds.top = n;
      if (n < p_bounds.bottom)
          p_bounds.bottom = n;
    }
}

function GetBoundsData(p_document){
    var result = {left:6000000,top:4700000,right:1000000,bottom:6500000};
    var wpts = p_document.getElementsByTagName('wpt');
    for (var i = 0; i < wpts.length; i++){
        var wpt = wpts[i];
        AdjustBounds(wpt, result);
    }        
    var trkpts = p_document.getElementsByTagName('trkpt');
    for (var i = 0; i < trkpts.length; i++){
        var trkpt = trkpts[i];
        AdjustBounds(trkpt, result);
    } 
    if (result.left !== 6000000)
        return result;
    else 
        return {left:0,top:0,right:0,bottom:0};
}

function GetTrackDateData(p_document){
     var options = {
        timeZone: "Pacific/Auckland",
        year: 'numeric', month: '2-digit', day: '2-digit'
    };
    var formatter  = new Intl.DateTimeFormat([], options);
    var result = formatter.format(new Date());
    var datetimes = p_document.getElementsByTagName('time');
    for (var i = 0; i < datetimes.length; i++){
        var datetime = new Date(datetimes[i].textContent);
        // 2007-11-02T19:17:42Z
        parts = formatter.formatToParts(datetime);
        result = parts[4].value + "-" + parts[0].value+ "-" + parts[2].value;
        break;
    }
    return result;
}

function ValidateFile(e){
    var strName = e.data[0];
    var iLastDot = strName.lastIndexOf('.');
    var strExt = strName.substring(iLastDot + 1).toUpperCase();
    if (strExt !== 'GPX'){
        error_messages.push(strName + ' is not a gpx file');
        bad_file_names.push(strName);
        return;
    }
    var strGpx = e.data[1];
    var oParser = new DOMParser();
    var oDOM = oParser.parseFromString(strGpx, "application/xml");
    // print the name of the root element or error message
    var errors = oDOM.documentElement.getElementsByTagName('parsererror');
    if (errors.length > 0){
        for (var i = 0; i < errors.length; i++){
          var error = errors[i];
          // unfortunately DOMParser tends to assume it's message will be displayed on a new page
          // but there is sometimes some useful text
          error_messages.push(strName + ' invalid gpx format: ' + error.innerText);
          bad_file_names.push(strName);
        }
        return;
    } 
    // File seems to be ok - attampt to extract a bit of useful summary data from it
    // This fairly awful style but these three arrays should always sync with corresponding info
    good_file_names.push(strName);
    cmtdesc_data.push(GetCmtDescData(oDOM.documentElement));
    nztm_bounds_data.push(GetBoundsData(oDOM.documentElement));
    trackdate_data.push(GetTrackDateData(oDOM.documentElement));
    // TODO extract date info
    return;
}

function DoAlert(p_messages){
    var messages = '';
    for (var i = 0; i < p_messages.length; i++){
        var msg = p_messages[i];
        messages += msg + "\n";
    }
    messages += '\nContinue loading valid files?'
    return window.confirm(messages);
}

function IsBadFile(p_file){
    for (var i = 0; i < bad_file_names.length; i++){
        var badfilename = bad_file_names[i];
        if (badfilename === p_file.name)
            return true;
    }
    return false;
}

function GoodFileIndex(p_file){
   for (var i = 0; i < good_file_names.length; i++){
        var goodfilename = good_file_names[i];
        if (goodfilename === p_file.name)
            return i;
    }
    return -1;
}

function RemoveExtension(p_filename){
   var lastDotPosition = p_filename.lastIndexOf(".");
   if (lastDotPosition === -1) return p_filename;
   else return p_filename.substr(0, lastDotPosition);
}

function FocusOutRouteNotes(p_sender){
    content = p_sender.textContent;
    if (content !== p_sender.getAttribute("data-original")){
        // Content changed - write to database
        id = p_sender.id;
        UpdateArchiveItem(id, 'routenotes', content);
    }
}

function FocusOutCaption(p_sender){
    content = p_sender.textContent;
    if (content !== p_sender.getAttribute("data-original")){
        // Content changed - write to database
        id = p_sender.id;
        UpdateArchiveItem(id, 'caption', content);
        p_sender.setAttribute("data-original", content);        
    }
}

function UpdateArchiveItem(p_id, p_propname, p_value){
    var formdata = new FormData();
    formdata.append('action', 'UpdateArchiveItem');
    formdata.append('propname', p_propname);
    formdata.append('value', p_value);
    formdata.append('id', p_id);
    var getUrl = window.location;   
    var url = getUrl .protocol + "//" + getUrl.host + getUrl.pathname.split('index.php')[0] + "index.php/archiveRest/archiveItem";
    jQuery(function ($) {
        $.ajax({
            url: url,
            type: 'POST',
            data: formdata,
            cache: false,
            contentType: false,
            processData: false,
            success: function (data) {
                result = JSON.parse(data);
                if (!result.success)
                    alert(result.message);
            },
            error: function (data) {
                alert("Update failed");
            }
        });
    });
}

function OnNoFilterClick(){
    document.getElementById('filter').innerText = "";
    OnSearch();
}

function OnSearch(){
    var filter = document.getElementById('filter').innerText;
    sessionStorage.setItem("filter", filter);
    var elementx = document.getElementById("filterx");
    if (filter !== ""){
        elementx.style.display = "block";
    }else{
        elementx.style.display = "none";
    }
    ShowFilteredRows(filter);
}

// Simple minded filter splits search string into blank separated words a tests for presence of all between caption and notes
// Maybe extend to allow quoted strings or whatever
function PassesFilter(p_filter, p_row){
     if (p_filter !== ""){
        var afilter = p_filter.toLowerCase().split(" ");
        var test = (p_row.querySelectorAll('.caption')[0].innerHTML + " " + p_row.querySelectorAll('.routenotes')[0].innerHTML).toLowerCase();
        for (var i = 0; i < afilter.length; i++){
            var filter_word = afilter[i].trim(); // split behaves unexpectedly if it finds adjoining spaces
            if (filter_word !== " "){
                if (test.indexOf(filter_word) < 0)
                    return false;
            }        
        }
    }
    return true;
}

function ShowFilteredRows(p_filter){
    allrows = document.getElementsByClassName('archiveitem');
    for (var i = 0; i < allrows.length - 1; i++){
        var row = allrows[i];
        if (PassesFilter(p_filter, row))
            row.style.display = 'table-row';
        else 
            row.style.display = 'none';
    } 
    OnLoad(); // Resize to fit viaible rows
}







