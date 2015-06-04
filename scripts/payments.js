// Script for the subscription payments form, ensuring that the
// postback only send elements in rows whose values get changed.
// Written to deal with exceeding the max_input_vars limit of 1000
// in php.ini (14 May 2014).

/*** NOT DEBUGGED YET. Has problems because if you re-edit a form,
 *   you then lose all the previous edits from the resubmission. 
 *   No longer needed as max_input_vars has been raised by HostGator.
 */

(function () {
    "use strict";
    var changedRows = [],
        allRows = [];

    function extractId(element) {
        // Extract and return the 4-digit row id from the given element's name
        var elementName = element.attr('name'),
            rowMatch = elementName.match(/[A-Za-z]+(\d+)/);
        if (rowMatch.length === 2) {
            return rowMatch[1];
        }
        return -1;
    }

    function recordRowNum() {
        // Record a row number
        var rowNum = extractId($(this));
        if (rowNum !== -1) {
            allRows.push(rowNum);
        }
    }

    function recordChangedRow() {
        // Record row number of a row that just changed.
        // Only these rows get posted back.
        var rowNum = extractId($(this));
        if (rowNum !== -1) {
            changedRows.push(rowNum);
        }
    }


    function removeAllUntouched() {
        // Disable all form elements in rows that have
        // not been modified by the user.
        var i = 0, rowNum = 0;

        for (i = 0; i < allRows.length; i += 1) {
            rowNum = allRows[i];
            if (changedRows.indexOf(rowNum) === -1) {
                $("[name$='" + rowNum.toString() + "']").attr('disabled', 'disabled');
            }
        }
    }

    /* DISABLED IN CASE OF INADVERTENT DEPLOYMENT
    $("[name^='cb']").each(recordRowNum);
    $(".msrowel").change(recordChangedRow);
    $("#paymentsForm").submit(removeAllUntouched);
    */
}());