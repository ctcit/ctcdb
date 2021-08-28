<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class Subs extends BaseController
{
    /**
     * Constructor.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param LoggerInterface   $logger
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);

        if (!session()->hasFullAccess) {
            die('You must be a club officer logged in to the website to use this function!!!');
        }
    }

    // Present a spreadsheet-like interface for recording multiple membership
    // payments for a given year.
    public function recordPayments($year = null)
    {
        $isPostBack = count($_POST) > 0;
        if (!$isPostBack) {
            if ($year === null) {
                $year = $this->getSubsYear();
            }
            $membershipQuery = model('ctcModel')->getMembershipPaymentStatus($year);
            return $this->loadPageInNewWindow('paymentEntryForm','CTCDB: Enter subs payments',
                                              ['year'=>$year, 'membershipQuery'=>$membershipQuery]);
        } else {
            // Postback when the user clicks "submit"
            assert($year != null);
            $confirmationData = array('subsYear'=>$year, 'table'=>$this->processPaymentsForm($year));
            return $this->loadPageInNewWindow('confirmPayments','CDCDB: Payments confirmation', $confirmationData);
        }
    }

    // Process payment list, when confirmed by user or when reedit clicked
    // TODO: consider if this needs more security
    public function recordPayments2($year)
    {
        if ($this->request->getPost('reedit')!=null) {
            // Postback when the user clicks "re-edit"
            assert($year != null);
            $membershipQuery = model('ctcModel')->getMembershipPaymentStatus($year);
            $changeTable = $this->request->getPost('changeTable');
            $reloadValues = $this->getReloadValues(unserialize($changeTable));
            return $this->loadPageInNewWindow('paymentEntryForm','CTCDB: Enter subs payments',
                ['year'=>$year, 'message'=>'Edit or update payment information. '.
                    'Changed fields are blue, suspect fields are red.',
                    'membershipQuery'=>$membershipQuery,
                    'reloadValues'=>$reloadValues]);
        } else {
            // Postback when "save to DB" clicked
            $keyMap = array('Amount'=>'amountPaid', 'CardNum' => 'cardNumber', 'Card2Num'=>'secondaryCardNumber',
                             'DatePaid' => 'paymentDate', 'Notes'=>'notes');
            $errors = array();

            // Work through post data, building table of updates.
            $rows = array();
            foreach ($this->request->getPost() as $key=>$value) {
                $bits = array();
                if (in_array($key, array('submit', 'reedit','IDs','changeTable'))) {
                    continue;
                }
                if (!preg_match('/([a-zA-Z]+[a-zA-Z0-9]*[a-zA-Z]+)([0-9]+)/', $key, $bits)) {
                    $errors[] = "INTERNAL ERROR: Unrecognised key ($key). Please report.";
                } else {
                    $field = $bits[1];
                    $membershipId = $bits[2];
                    $rows[$membershipId][$keyMap[$field]] = $value;
                }
            }

            foreach(array_keys($rows) as $membershipId) {
                $row = $rows[$membershipId];
                $numRowsAffected = model('ctcModel')->addOrAlterPayment($membershipId, $row, $year);
                if ($numRowsAffected != 1) {
                    $errors[] = "INTERNAL ERROR: attempt to insert or alter payment details for MSID $membershipId and year $year ".
                        " resulted in the addition or alteration of $numRowsAffected rows, not 1 as expected.";
                }
            }
            return $this->loadPageInNewWindow('paymentsRecorded','CTCDB: Payments recorded', array('errors' => $errors));
        }
    }

    public function deletePayment($year = null)
    {
        if ($year === null) {
            $year = $this->getSubsYear();
        }
        $paymentsQuery = model('ctcModel')->getPaymentsList($year);
        return $this->loadPage('deletePayment','CTCDB: Payment deletion form',
                               ['year'=>$year, 'paymentsQuery'=>$paymentsQuery]);
    }

    // Entry point from 'delete payment' form when a 'Delete' link has been clicked.
    public function deletePayment2($year, $paymentId)
    {
        $membershipName = model('ctcModel')->getMembershipNameFromSubsPayment($paymentId);
        return $this->loadPage('confirmDeletePayment', 'CTCDB: Payment deletion confirmation',
                               ['year'=>$year, 'paymentId'=>$paymentId, 'membershipName'=>$membershipName]);
    }

    // Entry point from the 'delete payment confirmation' page
    public function deletePayment3($paymentId)
    {
        $rows = model('ctcModel')->deletePayment($paymentId);
        $message = $rows == 1 ? "Deletion has been done" : "Deletion failed!";
        $data = array('message'=>$message);
        if ($rows != 1) {
            $data['tellWebmaster'] = true;
        }
        return $this->loadPage('operationOutcome','CTCDB: Deletion complete', $data);
    }


    // Private methods

    // Compares the posted payments form with a form loaded from the database.
    // Makes a new table containing just the rows that have been changed. Each row
    // of the table is an associative array mapping column names (from the 'record
    // payments' table to (value, changed, suspect) tuples. The tuples are themselves
    // an associative array with keys 'value', 'changed' and 'suspect'. A colum
    // (or field, really) is suspect if it breaches any of the following consistency
    // checks:
    //
    //  1. DatePaid should be within the subscription year extended by 4 months
    //     at each end.
    //  2. CardNumber must be in the range 1000 - 9999 for a paid-member (defined
    //     by the Paid box being checked in the submitted form or the existence
    //     of a Payment record in the subs_payments table for that member).
    //  3. CardNumber must be empty or zero for unpaid members.
    //  4. Amount must be 0 for Paid LifeMembers or a number in the range 5 - 95 for
    //     Paid non-life members.
    //  5. Card2Num must be a number in the range 1000 - 9999 for paid couple
    //     memberships and zero or blank otherwise.
    private function processPaymentsForm($year)
    {
        $membershipQuery = model('ctcModel')->getMembershipPaymentStatus($year);
        $memberships = $membershipQuery->getResultArray();
        $table = array();
        $now = date('d-m-Y');

        foreach ($memberships as $membership) {
            $id = $membership['MSID'];
            $transaction = $membership["DatePaid"] != null ? 'Edit' : 'New';
            $row = array('Transaction'=>array('value'=>$transaction, 'changed'=>false, 'suspect'=>false));
            $rowChanged = false;
            foreach (array_keys($membership) as $key) {
                if ($key == 'Paid') {
                    continue;
                }
                $oldValue = $membership[$key];
                if ($this->request->getPost($key.$id) == null) {
                    // Copy non-input fields across
                    $row[$key] = array('value'=>$oldValue, 'changed'=>false, 'suspect'=>false);
                } else {
                    $newValue = $this->request->getPost($key.$id);

                    $changed = $oldValue != $newValue;
                    if ($changed) {
                        $rowChanged = true;
                    }
                    $suspect = $this->isSuspect($key, $newValue, $membership['Type'], $year);
                    $row[$key] = array('value'=>$newValue, 'changed'=>$changed, 'suspect'=>$suspect);
                }
            }
            if ($rowChanged) {
                // When a row is changed, if there is no payment date explicitly entered
                // or copied from the previous entry, use today's date.
                if ($row['DatePaid']['value'] == '') {
                    $row['DatePaid'] = array('value'=>$now, 'changed'=>true, 'suspect'=>false);
                }
                $table[] = $row;
            }
        }
        return $table;
    }

    // Compute a map of reload values for the paymentEntryForm from the
    // change table computed earlier and passed back via recordPayments3.
    // The map is from fieldName.msid to (value, status).
    private function getReloadValues($changes)
    {
        $reloadMap = array();
        foreach ($changes as $row) {
            assert(isset($row['MSID']['value']));
            $msid = $row['MSID']['value'];
            foreach ($row as $key=>$fieldState) {
                if ($key == 'MSID') continue;
                $status = '';
                if ($fieldState['changed'] || $fieldState['suspect']) {
                    $status = $fieldState['suspect'] ? 'ColumnSuspect' : 'ColumnChanged';
                    $reloadMap[$key.$msid] = array('value'=>$fieldState['value'], 'status'=>$status);
                }
            }
        }
        return $reloadMap;
    }

    // Return the best guess at what subscription year is most likely to be of interest
    // at the current date/time!
    private function getSubsYear()
    {
        helper('utilities');
        return getSubsYear();
    }

    // Return false if the given ($key, $value) breaches
    // any of the conditions listed in processPaymentsForm for a given membershipType.
    private function isSuspect($key, $value, $membershipType, $subsYear)
    {
        $suspect = false;
        if ($key == 'DatePaid' && $value != null) {  // DatePaid checks
            helper('date');
            $mysqlDate = date_to_mysql($value);
            if ($mysqlDate === null) {	 // Bad syntax: can't convert to MySQL form
                $suspect = true;
            } else {
                $paidYear = substr($mysqlDate, 0, 4);  // Check payment date within subs year ...
                $paidMonth = substr($mysqlDate, 5, 2); // ... extended by 4 months each end.
                if (!($paidYear == $subsYear || ($paidYear == ($subsYear + 1) && $paidMonth <= 8))) {
                    $suspect = true;
                }
            }
        }

        if ($key == 'CardNum' && ($value < 1000 || $value > 9999)) {
            $suspect = true;
        }

        if ($key == 'Amount') {
            if (strpos($membershipType,'Life') !== FALSE) {
                if ($value != 0) {
                    $suspect = true;
                }
            } else /* Not lifer */ {
                if ($value < 5 || $value > 95) {
                    $suspect = true;
                }
            }
        }

        if ($key == 'Card2Num') {
            if (strpos($membershipType, 'Couple') !== FALSE) {
                if ($value < 1000 || $value > 9999) {
                    $suspect = true;
                }
            } else /* Not a couple */ {
                if ($value != null && $value != 0) {
                    $suspect = true;
                }
            }
        }

        return $suspect;
    }
}
