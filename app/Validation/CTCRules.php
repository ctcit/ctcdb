<?php
namespace App\Validation;

use CodeIgniter\Validation\FormatRules;

// Rules for validation members

class CTCRules
{
    // Checks if the given (new) login contains only legitimate characters
    // and is valid for the current user id (field of $this)
    // (extracted from the current session) or valid for a new member if that id is -1.
    public function loginCheck($login, string $currentMemberID, array $data, & $error = null ) : bool
    {
        $result = model('ctcModel')->isValidLogin($login, $currentMemberID);
        if ($result !== true) {
            $error = $result;
            return false;
        }
        return true;
    }

    // Checks if a date is in NZ standard form DD-MM-YYYY format (or, more generally, is
    // in any of the forms accepted by date_to_mysql).
    public function dateCheck($date, & $error)
    {
        helper('date');
        if (date_to_mysql($date) !== null) {
            return true;
        } else {
            $error = 'Invalid date. Must be in form DD-MM-YYYY.';
            return false;
        }
    }

    // Checks if a date is NULL or in NZ standard form DD-MM-YYYY format (or, more generally, is
    // in any of the forms accepted by date_to_mysql).
    // This version differs from the above in that it allows null dates, and is set up
    // for use with the new form_validation class, not the old one.
    public function dateCheck2($date, & $error)
    {
        helper('date');
        if ($date == null || date_to_mysql($date) !== null) {
            return true;
        } else {
            $error = "Invalid date. Must be empty (meaning 'today') or in form DD-MM-YYYY.";
            return false;
        }
    }

    public function checkReason($reason, & $error) {
        $result = true;
        if ($reason == "SelectOne") {
            $error = 'You must select one of the reasons for closing this membership';
            $result = false;
        }
        return $result;
    }

    public function valid_email_or_empty($value) {
        if ($value == "" || !$value) {
            return true;
        }
        $formatRules = new FormatRules;
        return $formatRules->valid_email($value);
    }

    public function password_is_correct($password, string $currentMemberID, array $data, & $error = null ) : bool
    {
        if (!model('ctcModel')->isCorrectPasswordForMember($password, $currentMemberID))
        {
            $error = "Incorrect password";
            return false;
        }
        return true;
    }

    // Checks if the given query begins with SELECT or UNION or SHOW or DESCRIBE (a very rudimentary check
    // to reduce the chances of one's shooting oneself in the foot.
    public function query_check($value, & $error)
    {
        $ok = preg_match('/SELECT\s.*|UNION\s.*|SHOW\s.*|DESCRIBE\s.*/s', strtoupper($value));
        if (!$ok) {
            $error = 'Query must begin with SELECT, UNION, SHOW or DESCRIBE';
            return false;
        }
        return true;
    }

}