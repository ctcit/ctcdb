<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

// Convert a MySQL date to NZ dd-mm-yyyy format.
function date_to_nz($date) {
	$bits = array();
	if (!preg_match('/^(\d{4})\-(\d{1,2})\-(\d{1,2})$/',$date,$bits)) {
		return NULL;
	}
	else {
		return $bits[3].'-'.$bits[2].'-'.$bits[1];
	}	
}

// month_to_number($month) converts a 3-letter month code (jan, feb, ...) to a number 1 .. 12.
// Returns 0 if $month is not one of the standard 3-letter codes. Behaves case-insensitively.
function month_to_number($month) {
	$result = 0;
	$months = array('jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec');
	if (($index = array_search(strtolower($month),$months)) !== FALSE) {
		$result = $index + 1;
	}
	return $result;
}

// date_to_mysql converts an NZ style date format to a normal YYYY-MM-DD mysql format.
// The input date must be in one of the following formats (approx -- see patterns below for exact defn):
//
// 1. dd-mm-yyyy  (which includes 1 digit day and month fields).
// 2. dd-mm-yy    (where the year is taken to be 19yy if yy >=40, 20yy if yy < 40)
// 3. dd-MON-yyyy (where MON is one of jan, feb, mar, apr, may, jun, jul, aug, sep, oct, nov, dec)
// 4. dd-MON-yy   (where MON is as in 3, year as in 2).
//
// Slashes can be used instead of hyphens everywhere, e.g. 21/12/08, 3/Jul/1947.
// The function returns NULL if none of those patterns matches or if, after matching, the day and/or
// month are out of bounds.
// 
function date_to_mysql($date) {
	$bits = array();
	$month = -1;
	$result = NULL;
	if (preg_match('/^([0123]?\d)[-\/]([012]?\d)[-\/]([12]\d{3})$/', $date, $bits)) {
		$day = $bits[1]; 
		$month = $bits[2];
		$year = $bits[3];
	}
	else if (preg_match('/^([0123]?\d)[-\/]([012]?\d)[-\/](\d{2})$/', $date, $bits)) {
		$day = $bits[1];
		$month = $bits[2];
		$year = $bits[3] >= 40 ? 1900 + $bits[3] : 2000 + $bits[3];
	}
	else if (preg_match('/^([0123]?\d)[-\/](jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)[-\/]([12]\d{3})$/i', $date, $bits)) {
		$day = $bits[1];
		$month = month_to_number($bits[2]);
		$year = $bits[3];
	}
	else if (preg_match('/^([0123]?\d)[-\/](jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)[-\/](\d{2})$/i', $date, $bits)) {
		$day = $bits[1];
		$month = month_to_number($bits[2]);
		$year = $bits[3] >= 40 ? 1900 + $bits[3] : 2000 + $bits[3];
	}
	if ($month > 0 && $month <= 12) {
		$isLeap = ($year % 4) == 0;
		$daysInMonth = array(0, 31, 28 + ($isLeap ? 1 : 0), 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
		if ($day > 0 && $day <= $daysInMonth[(int)$month]) {
			$result = "$year-$month-$day";
		}
	}
	return $result;	
}

function test_date_to_mysql() {
	$data = array("1-2-1066","01-02-1066","1/2/1066","1-12-1066","29-2-2000","29-2-2001",
				  "1-2-08","01/02/08","29-2-00","29-2-01","17-7-47",
				  "2-jan-08","29-feb-08","30/sep/47","17-jul-47","31-dec-20",
				  "2-jan-2008","29-feb-1908","30-sep-1947","17-jul-1947","31-dec-2000",
				  "2-JaN-2008","29-FEB-1908","30-seP-1947","17-Jul-1947","31-DEC-2000",
				  "123-3-45","1-jna-2000"
	);
	foreach ($data as $date) {
		echo "$date -> " . date_to_mysql($date) . "<br />";
	}
}
?>
