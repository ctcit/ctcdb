function selectRows(tableId, firstCol, lastCol) {
	if (typeof firstCol == 'undefined') firstCol = 1;
	var filter = document.getElementById("filter").value.toUpperCase();
	var tbl = document.getElementById(tableId);
	var len = tbl.rows.length;
	if (typeof lastCol == 'undefined') lastCol = tbl.rows[0].cells.length;
	var rowClass = "odd";
	for(var i=1 ; i < len; i++){ // Leave header row intact
		 var show = "none";
	     var cells = tbl.rows[i].cells;  // Check all cells in row for match with filter
	     for (var j = firstCol; j <= lastCol; j++) {
	     	var cell = cells[j];
	     	if (cell != null) {
	     		var cellData = cell.innerHTML;
	     		if (cellData != null && (cellData.toUpperCase().indexOf(filter) >= 0)) {
	     			show = "";
	     			break;
	     		}
	     	}
	     }
	     if (show == '') {
		     tbl.rows[i].className = rowClass;
		     if (rowClass == "odd") {
			     rowClass = "even";
		     }
		     else {
			     rowClass = "odd";
		     }
	     }
             tbl.rows[i].style.display = show;
	 }
}
