// Title: Timestamp picker
// Description: See the demo at url
// URL: http://us.geocities.com/tspicker/
// Script featured on: http://javascriptkit.com/script/script2/timestamp.shtml
// Version: 1.0
// Date: 12-05-2001 (mm-dd-yyyy)
// Author: Denis Gritcyuk <denis@softcomplex.com>; <tspicker@yahoo.com>
// Notes: Permission given to use this script in any kind of applications if
//    header lines are left unchanged. Feel free to contact the author
//    for feature requests and/or donations

// Hacked by RJL to remove time input field and ti add the 'image_dir_url parameter,
// which is the url of the directory containing the next and previous icon images
// next.gif and prev.gif. URL shouldn't end with a slash.

// Further hacked to replace the first parameter with the ID of the field
// to which this calendar refers and to extend the meaning of the str_date
// field to include the case '-', denoting that the date to be displayed and
// initially selected should be read from the associated input field.

function show_calendar(fieldID, dateToDisplay, image_dir_url) {
//function show_calendar(field_name, form_name, image_dir_url) {
	var arr_months = ["January", "February", "March", "April", "May", "June",
		"July", "August", "September", "October", "November", "December"];
	var week_days = ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa"];
	var n_weekstart = 1;   // day week starts from (normally 0 or 1)
	var str_date = (dateToDisplay == '-' ? document.getElementById(fieldID).value : dateToDisplay);
	var dt_date = (str_date == null || str_date =="" ?  new Date() : str2dt(str_date));
	var dt_prev_month = new Date(dt_date);
	dt_prev_month.setMonth(dt_date.getMonth()-1);
	var dt_next_month = new Date(dt_date);
	dt_next_month.setMonth(dt_date.getMonth()+1);
	var dt_firstday = new Date(dt_date);
	dt_firstday.setDate(1);
	dt_firstday.setDate(1-(7+dt_firstday.getDay()-n_weekstart)%7);
	var dt_lastday = new Date(dt_next_month);
	dt_lastday.setDate(0);
	
	// html generation (feel free to tune it for your particular application)
	// print calendar header
	var str_buffer = new String (
		"<html>\n"+
		"<head>\n"+
		"	<title>Calendar</title>\n"+
		"</head>\n"+
		"<body bgcolor=\"White\">\n"+
		"<table class=\"clsOTable\" cellspacing=\"0\" border=\"0\" width=\"100%\">\n"+
		"<tr><td bgcolor=\"#4682B4\">\n"+
		"<table cellspacing=\"1\" cellpadding=\"3\" border=\"0\" width=\"100%\">\n"+
		"<tr>\n	<td bgcolor=\"#4682B4\"><a href=\"javascript:window.opener.show_calendar('"
		+fieldID+"', '"+ dt2dtstr(dt_prev_month)+"','"+image_dir_url+"');\">"+
		"<img src=\"" + image_dir_url + "/prev.gif\" width=\"16\" height=\"16\" border=\"0\""+
		" alt=\"previous month\"></a></td>\n"+
		"	<td bgcolor=\"#4682B4\" colspan=\"5\">"+
		"<font color=\"white\" face=\"tahoma, verdana\" size=\"2\">"
		+arr_months[dt_date.getMonth()]+" "+dt_date.getFullYear()+"</font></td>\n"+
		"	<td bgcolor=\"#4682B4\" align=\"right\"><a href=\"javascript:window.opener.show_calendar('"
		+fieldID+"', '"+dt2dtstr(dt_next_month)+"','"+image_dir_url+"');\">"+
		"<img src=\"" + image_dir_url + "/next.gif\" width=\"16\" height=\"16\" border=\"0\""+
		" alt=\"next month\"></a></td>\n</tr>\n"
	);

	var dt_current_day = new Date(dt_firstday);
	// print weekdays titles
	str_buffer += "<tr>\n";
	for (var n=0; n<7; n++)
		str_buffer += "	<td bgcolor=\"#87CEFA\">"+
		"<font color=\"white\" face=\"tahoma, verdana\" size=\"2\">"+
		week_days[(n_weekstart+n)%7]+"</font></td>\n";
	// print calendar table
	str_buffer += "</tr>\n";
	while (dt_current_day.getMonth() == dt_date.getMonth() ||
		dt_current_day.getMonth() == dt_firstday.getMonth()) {
		// print row heder
		str_buffer += "<tr>\n";
		for (var n_current_wday=0; n_current_wday<7; n_current_wday++) {
				if (dt_current_day.getDate() == dt_date.getDate() &&
					dt_current_day.getMonth() == dt_date.getMonth())
					// print current date
					str_buffer += "	<td bgcolor=\"#FFB6C1\" align=\"right\">";
				else if (dt_current_day.getDay() == 0 || dt_current_day.getDay() == 6)
					// weekend days
					str_buffer += "	<td bgcolor=\"#DBEAF5\" align=\"right\">";
				else
					// print working days of current month
					str_buffer += "	<td bgcolor=\"white\" align=\"right\">";

				if (dt_current_day.getMonth() == dt_date.getMonth())
					// print days of current month
					str_buffer += "<a href=\"javascript:window.opener.document.getElementById('"+fieldID+
					"').value='"+dt2dtstr(dt_current_day)+"'; window.close();\">"+
					"<font color=\"black\" face=\"tahoma, verdana\" size=\"2\">";
				else 
					// print days of other months
					str_buffer += "<a href=\"javascript:window.opener.document.getElementById('"+fieldID+
					"').value='"+dt2dtstr(dt_current_day)+"'; window.close();\">"+
					"<font color=\"gray\" face=\"tahoma, verdana\" size=\"2\">";
				str_buffer += dt_current_day.getDate()+"</font></a></td>\n";
				dt_current_day.setDate(dt_current_day.getDate()+1);
		}
		// print row footer
		str_buffer += "</tr>\n";
	}
	// print calendar footer
	str_buffer +=
		"<form name=\"cal\">\n<tr><td colspan=\"7\" bgcolor=\"#87CEFA\">"+
		"<font color=\"White\" face=\"tahoma, verdana\" size=\"2\">" +
		"</font></td></tr>\n</form>\n" +
		"</table>\n" +
		"</tr>\n</td>\n</table>\n" +
		"</body>\n" +
		"</html>\n";

	var vWinCal = window.open("", "Calendar", 
		"width=200,height=200,status=no,resizable=yes,top=200,left=200");
	vWinCal.opener = self;
	var calc_doc = vWinCal.document;
	calc_doc.write (str_buffer);
	calc_doc.close();
}
// date parsing and formatting routines. modify them if you wish other date format
// Modified by RJL to take an NZ standard dd-mm-yyyy date as input
function str2dt (str_date) {
	var re_date = /^([0123]?\d)[-/]([01]?\d)[-/]([12]\d\d\d|\d\d)$/;
	if (!re_date.exec(str_date)) {
		alert("Invalid date: "+ str_date + ". Using today's date.");
		return new Date();
	}
	var year = RegExp.$3.length == 4 ? RegExp.$3 : (RegExp.$3 >= 40 ? RegExp.$3 - 0 + 1900 : RegExp.$3 - 0 + 2000);
	return (new Date (year, RegExp.$2-1, RegExp.$1));
}
function dt2dtstr (dt_date) {
	return (new String (
			dt_date.getDate()+"-"+(dt_date.getMonth()+1)+"-"+dt_date.getFullYear()));
}


