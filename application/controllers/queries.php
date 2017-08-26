<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

/* This file is the controller for all queries. The 'user_queries' table contains the various
 * queries that can be run by a particular user. Queries owned by the special user _menu_
 * are used in the main menu, with a tooltip obtained from the first sentence of the description.
 */
class Queries extends MY_Controller {

    public function __construct()
    {
        global $userData;
        if (count($userData['roles']) == 0) {  // Does the user have at least one official role?
            die("Sorry, you don't have access to that function.");
        }
        parent::__construct();
        $this->load->database();
        $this->load->library('session');
        $this->load->model('Ctcmodel');
        $this->load->helper(array('url','form'));
        $this->isWebmaster = array_search('webmaster', $userData['roles']) !== false;
    }

    public function saveCsv($resultid)
    // Action from a generic query display view to save the query result to
    // a csv file on the user's desktop.
    {
        $result = $this->Ctcmodel->getSavedResult($resultid);
        if ($result === NULL) {
            $this->_loadPageInNewWindow('staleCSVData', 'CTCDB: Sorry, that result has been deleted from the cache. Please re-run the query.');
        }

        $csv = $this->_makeCsv($result);
        $this->load->helper('download');
        force_download("query_result$resultid.csv", $csv);
    }


    /**
     * Do a print merge of the currently-displayed table data with the .odt document
     * selected by the combo box
     */
    public function printMerge($resultId) {
        $tableData = $this->Ctcmodel->getSavedResult($resultId);
        if ($tableData === NULL) {
            $this->_loadPageInNewWindow('staleCSVData',
                'CTCDB: Sorry, that result has been deleted from the cache. Please re-run the query.');
            return;
        }
        $docId = $this->input->post('doc_id');
        $document = $this->Ctcmodel->getDocument($docId);
        require_once("../newsletter/generate_odt.php");
        $engine = new XmlTemplateEngine(null, $tableData);
        $output = $engine->processOdtTemplate($document);
        $this->load->helper('download');
        force_download('mergeOutput.odt', $output);
    }

    /**
     * Phase1 of email merge.
     * Process database query result $resultId in cache and template text document
     * with given ID as a potential bulk email. This phase builds a sample
     * document from the first row of the database and a list of the recipients
     * it will go to and displays that for user confirmation. If the user
     * confirms, phase2 repeats the process, emailing the document to all
     * recipients.
     */
    public function emailMerge($resultId) {
        $tableData = $this->Ctcmodel->getSavedResult($resultId);
        if ($tableData === NULL) {
            $this->_loadPageInNewWindow('staleCSVData',
                'CTCDB: Sorry, that result has been deleted from the cache. Please re-run the query.');
            return;
        }

        $docId = $this->input->post('email_doc_id');
        $subject = $this->input->post('subject');
        $document = $this->Ctcmodel->getDocument($docId);
        if ($document == '') {
            $this->_loadPageInNewWindow('emptyDocument',
            'CTCDB: Sorry, that document is non-existent or empty');
            return;
        }


        $nRecipients = count($tableData);
        if ($nRecipients == 0) {
            $this->_loadPageInNewWindow('operationOutcome',
            'CTCDB: Sorry, the recipient list appears to be empty!');
            return;
        }

        $batchId = $this->Ctcmodel->addEmailBatch($docId, $subject, $nRecipients);
        $fails = 0;
        foreach ($tableData as $row) {
            if (!property_exists($row, 'email') || empty($row->email)) {
                $fails += 1;
            }
        }

        if ($fails > 0) {
            $this->_loadPageInNewWindow('operationOutcome',
            'CTCDB: One or more query rows lacked an email address. Send aborted.');
        } else {
            $sampleMessage = '';

            $recipients = array();
            foreach ($tableData as $row) {
                $message = $this->expandTemplate($document, $row);
                if ($sampleMessage == '') {
                    $sampleMessage = $message; // For displaying the first message
                }
                $recipients[] = $row->email;
                $this->Ctcmodel->queueMailItem($batchId, $row->email, $subject, $message);
            }


            $this->_loadPage('confirmEmails', 'ConfirmEmailMerge',
            array(  'batchId' => $batchId,
                    'docId'   => $docId,
                    'subject' => $subject,
                    'message' => $sampleMessage,
                    'recipients' => $recipients));
        }
    }

    /**
     * Phase2 of the email merge. Simply sets the isConfirmed flag on all
     * emails in the queue to enable them to be sent via the
     * background CRON job (which calls open/processMailQueue every
     * 20 minutes).
     */
    public function emailMerge2($batchId)
    {
        $this->Ctcmodel->confirmMailBatch($batchId);
        $subject = $this->input->post('subject');
        $numRecipients = $this->input->post('nRecipients');
        $extraInfo = "Mail with subject '$subject' has been queued for delivery ".
             "to $numRecipients recipients. An email will be sent to the ".
             "webmaster when all messages have been sent. This may take up to an hour.";
        $this->_loadPage('operationOutcome', 'Mail queued for delivery',
                array('extraInfo' => $extraInfo));

    }


    public function switchUser()
    // Callback from showQueries form when webmaster chooses a different user whose
    // queries are to be edited.
    {
        global $userData;
        if (!$this->isWebmaster) {
            die("Security breach attempt");
        }
        $newUser = $_POST['NewUser'];
        $this->manageQueries($newUser);
    }

    public function manageQueries($user = NULL)
    // Function to allow arbitrary SQL queries on the database and maintain a library
    // of such queries for the specified user/login. If the user is NULL, the current user's
    // queries are managed. Otherwise the specifier's users queries are managed, but this
    // option is only available to the Webmaster. As a special case of the latter,
    // if the user is 0, the set of queries is the set displayed in the main menu.
    {
        global $userData;
        if ($user == NULL) {
            $user = $userData['userid'];
        }
        else {
            if ($user != $userData['userid'] && !$this->isWebmaster) {
                die("Security breach disallowed");
            }
        }
        $queryList = $this->Ctcmodel->getQueries($user);
        $table = array(array('','Name', 'Description','',''));
        $this->load->helper('url');
        foreach ($queryList as $query) {
            $id = $query['id'];
            $tableRow = array(
                anchor("queries/runQuery/$id", "Run", array('target' => "_blank")),
                $query['name'],
                $query['description'],
                anchor("queries/editQuery/$id", "Edit"),
                anchor("queries/deleteQuery/$id", "Delete")
            );
            array_push($table, $tableRow);
        }
        if ($user === '0') {
            $ownerName = "Main Menu";
        }
        else {
            $ownerName = $this->Ctcmodel->getMemberName($user);
        }
        $header = "Queries belonging to $ownerName";
        if ($this->isWebmaster) {
            // Webmaster gets presented with an option for changing to a different user's query set
            $loginList = $this->Ctcmodel->getQueryOwningMembers();
            $loginList[0] = "Main Menu"; // Add associative array element
            $this->_loadPage('showQueries', 'Queries',
                array('queryTable' => $table, 'header' => $header, 'switchUserList' => $loginList,
                        'currentUserId' => $user));
        }
        else {
            $this->_loadPage('showQueries', 'Queries',
                array('queryTable' => $table, 'header' => $header, 'currentUserId' => $user));
        }
    }

    public function runQuery($queryID)
    {
        $row = $this->Ctcmodel->getQuery($queryID);
        $name = $row['name'];
        $this->_displayQueryResult($row['sqlquery'], "Query '$name'");
    }

    public function editQuery($id = 0, $ownerId = NULL)
    // Called when
    // 1.  constructing a new query ($id = 0)
    // 2.  editing an existing query ($id != 0)
    // 3.  saving either of the above (postback)
    // $ownerId parameter is required only when creating a new query ($id == 0)
    // and when owner is not the current logged in user. [Can only be used by
    // the Webmaster].
    {
        global $userData;
        $this->_setQueryValidation();

        if ($id == 0) { // A new query
            $title = 'New query';
            $this->form_validation->queryName = '';
            $this->form_validation->description = '';
            $this->form_validation->query = $this->_sampleSql();
            if ($ownerId === NULL) {
                $ownerId = $userData['userid'];
            }
            else if ($ownerId != $userData['userid'] && !$this->isWebmaster) {
                die("Security breach disallowed");
            }
            $this->form_validation->queryOwnerId = $ownerId;
        }
        else { // Editing an existing query)
            $row = $this->Ctcmodel->getQuery($id);
            $title = 'Edit query ' . $row['name'];
            $this->form_validation->queryName = $row['name'];
            $this->form_validation->query = $row['sqlquery'];
            $this->form_validation->description = $row['description'];
            $this->form_validation->queryOwnerId = $row['userIdAdmin'];
        }

        $this->form_validation->id = $id;

        if ($this->form_validation->run()) { // Successful postback?
            global $userData;
            $currentUser = $userData['userid'];
            $queryOwnerId = $this->input->post('queryOwnerId');
            if ($currentUser != $queryOwnerId && !$this->isWebmaster) {
                die("Attempted security breach denied.");
            }

            $queryName = $this->input->post('queryName');
            $description = $this->input->post('description');
            $query = $this->input->post('query');
            $this->Ctcmodel->saveQuery($id, $queryName, $description, $query, $queryOwnerId);
            $this->manageQueries($queryOwnerId); // Take user back to query list form
        }
        else { // form_validation failed: (re)load form
            $this->_loadPage('editQuery', $title);
        }
    }

    public function deleteQuery($id)
    {
        $row = $this->Ctcmodel->getQuery($id);
        $name = $row['name'];
        $this->_loadPage('deleteQueryConfirm', 'Confirm query deletion',
            array('id' => $id, 'queryName' => $name));
    }

    public function deleteQuery2($id) {
        $this->Ctcmodel->deleteQuery($id);
        $this->_loadPage('home','CTCDB: Home');
    }

    public function testQuery()
    // Tests if query is valid and if so displays generic query result
    // (which will be in a new window)
    {
        $name = $this->input->post('queryName');
        $query = $this->input->post('query');
        $this->_setQueryValidation();
        if ($this->form_validation->run()) {
            $this->_displayQueryResult($query, "CTCDB: '$name' query output");
        }
        else {
            $this->_loadPageInNewWindow('queryTestFailed', 'Bad query');
        }
    }


// ENVELOPE-PRINTING QUERY STUFF
// =============================

    // Starting point for envelope printing -- just display the query builder form.
    public function printEnvelopes($error = '') {
        $this->_loadPage('printEnvelopesForm', 'CTCDB: Print Envelopes',
            array('error'=> ''));
    }

    // Return a subquery that selects the membership subset that receives the given item,
    // as specified by the given where clause (empty to select all members).
    public function subQuery($item, $where) {


        $query =
'        SELECT membershipId as msid, \''. $item . '\' AS item
         FROM view_memberships
         WHERE status = \'Active\'';
        if ($where != '') {
            $query .= " AND $where ";
        }
        return $query;
    }

    // Handle postback from printEnvelopesForm.
    // This is the guts of the job -- builds the appropriate query as specified
    // by the form, then runs that query.
    public function printEnvelopes2() {
        // Build array of tuples (formFieldName, itemName, whereClause).

        $items = array(
            array('nl', 'Newsletter', "mailNewsletter = 'Yes'"),
            array('fmcb', 'FMC&nbsp;Bulletin', "mailFMC = 'Yes'"),
            array('fmcc', 'FMC&nbsp;Card', "type not like 'Associate%' and type not like 'Free%' and type <> 'Prospective'"),
            array('subs', 'Subs&nbsp;Invoice', "paid = 'No'"),
            array('cookie', 'Cookie', '')
        );

        $subQueries = '';
        $sep = '';
        $showUnpaid = $this->input->post('showUnpaid');
        if ($showUnpaid) {
            $unpaid = "IF(paid='No', 'Unpaid','')";
        } else {
            $unpaid = "''";
        }
        foreach ($items as $item) {
            list($field, $itemName, $where) = $item;
            if ($this->input->post($field)) {
                $subQueries .= $sep . $this->subQuery($itemName, $where, $showUnpaid);
                $sep = "\n        UNION\n";
            }
        }

        if ($subQueries == '') {
            $this->_loadPage('printEnvelopesForm', 'CTCDB: Print Envelopes',
                array('error'=> 'You have to select <em>something</em> to put in the envelopes!'));
        }
        else {

            $ordering = '';
            $sep = '';
            foreach (array('sort1', 'sort2', 'sort3') as $field) {
                $key = $this->input->post($field);
                if ($key != '-') {
                    $ordering .= $sep . $key;
                    $sep = ',';
                }
            }

            $mainQuery =
'SELECT membershipId, mailName, nameBySurname, address1, address2, city, postcode,
      mailNewsletter, type as membershipType,
      sub, reducedSub,
      sub + latePenalty as subIfLate,
      reducedSub + latePenalty as reducedSubIfLate,
      login1, login2,
      primaryEmail as email,
      items,
      ' . $unpaid . ' as unpaid
FROM  view_memberships
JOIN
   (SELECT msid, group_concat(item separator "<br />") as items
    FROM
    (
' . $subQueries .
    ') AS items
    GROUP BY msid
   ) AS mailees
ON view_memberships.membershipId = mailees.msid
';
            if ($ordering != '') {
                $mainQuery .= " ORDER BY $ordering";
            }

            if ($this->input->post('showQuery')) {
                $this->_loadPageInNewWindow('displayQuery', 'Print Envelopes Query',
                    array('query' => $mainQuery));
            }
            else {
                $this->_displayQueryResult($mainQuery, "CTCDB: Envelope printing query output");
            }
        }
    }

// SUPPORT METHODS
// ===============

    function _setQueryValidation()
    // Set up the form_validation to apply to a query form.
    // Used by both editQuery and testQuery
    {
        $this->load->library('form_validation');
        $this->form_validation->set_rules('query', 'SQL Query', 'callback__queryCheck');
        $this->form_validation->set_rules('queryName', 'Query Name', 'required|alpha_dash');
        // TODO: why did I also used to define description, id and queryOwnerId fields??
        $this->form_validation->set_error_delimiters('<p style="font-size: 15px; color: red">', '</p>');
    }

    function _truncated($s, $fieldWidth)
    // Returns the string $s in toto if less than $fieldWidth else the string $s
    // truncated and with " ..." appended to exactly fit a field of $fieldWidth
    {
        if (strlen($s) > $fieldWidth) {
            $s = substr($s, 0, $fieldWidth - 4) . " ...";
        }
        return $s;
    }

    function _queryCheck($query)
    // Checks if the given query begins with SELECT or UNION or SHOW or DESCRIBE (a very rudimentary check
    // to reduce the chances of one's shooting oneself in the foot.
    {
        $ok = preg_match('/SELECT\s.*|UNION\s.*|SHOW\s.*|DESCRIBE\s.*/s', strtoupper($query));
        if (!$ok) {
            $this->form_validation->set_message('_queryCheck', 'Query must begin with SELECT, UNION, SHOW or DESCRIBE');
            return FALSE;
        }
        return TRUE;
    }


    /**
     * Display the query result in a new window, saving all rows to the database
     * in the saved_query_results table for use if "save CSV" is clicked. [Why
     * save the whole table instead of just the query that generated it?
     * Because under some circumstances (I don't remember exactly what they were)
     * there can be a nasty race problem, where the data that gets saved isn't actually
     * the data showing in the screen when the user clicks saved CSV.]
     *
     * @param $sql
     * @param $title
     */
    function _displayQueryResult($sql, $title)
    {
        $query = $this->Ctcmodel->genericQuery($sql);
        $queryResultId = $this->Ctcmodel->saveResult($query);
        $query = $this->Ctcmodel->genericQuery($sql);  // Re-run to rebuild field list :-(
        $docs = $this->Ctcmodel->getMergeDocuments('odt');  // Get a list of candidate .odt documents
        $emailDocs = $this->Ctcmodel->getMergeDocuments('txt');  // Get a list of candidate .txt documents
        $this->_loadPageInNewWindow('genericQueryDisplay',
            $title, array('query' => $query, 'resultid' => $queryResultId,
                          'mergeDocs' => $docs, 'emailDocs' => $emailDocs));
    }


    function _sampleSql()
    {
        $sql = <<<_query_
SELECT *
FROM view_members
_query_;
        return $sql;
    }

    /**
     * A modified version of code igniter's csv_from_result method of the dbutil class
     * taking an array of row objecdts as a parameter rather than a db result object.
     * @param $queryResult
     * @param $delim
     * @param $newline
     * @param $enclosure
     */
    function _makeCsv($queryResult, $delim = ",", $newline = "\n", $enclosure = '"')
    {
        $columnNames = array_keys(get_object_vars($queryResult[0]));
        $out = '';

        // First generate the headings from the table column names
        foreach ($columnNames as $name)
        {
            $out .= $enclosure.str_replace($enclosure, $enclosure.$enclosure, $name).$enclosure.$delim;
        }

        $out = rtrim($out);
        $out .= $newline;

        // Next blast through the result array and build out the rows
        foreach ($queryResult as $row)
        {
            foreach ($row as $item)
            {
                $out .= $enclosure.str_replace($enclosure, $enclosure.$enclosure, $item).$enclosure.$delim;
            }
            $out = rtrim($out);
            $out .= $newline;
        }

        return $out;
    }


    /**
     * Given a template text document as a string and a database row as an associative array, return a
     * version of the template with all fields, represented as {{fieldName}}, replaced with the
     * corresponding name from the database row.
     * Further expanded to handle a rudimentary
     * {{if fieldName = value}} ... {{endif}} syntax.
     */
    function expandTemplate($template, $row) {
        $bits = array();
        $match = preg_match("|(.*){{ *if +([^ =}]+) *= *([^ }]+)}}(.*){{endif}}(.*)|s", $template, $bits);

        if ($match) {
            $value = $bits[3];
            if (preg_match("|'.*'|", $value)) {
                $value = substr($value, 1, strlen($value) - 2);
            }
            $field = $bits[2];
            if ($row->$field == $value) {
                return $this->expandTemplate($bits[1], $row) .
                       $this->expandTemplate($bits[4], $row) .
                       $this->expandTemplate($bits[5], $row);
            }
            else {
                return $this->expandTemplate($bits[1], $row) .
                       $this->expandTemplate($bits[5], $row);
            }
        }
        else {
            foreach ($row as $field=>$value) {
                $template = str_replace('{{'.$field.'}}', $value, $template);
            }
            return $template;
        }
    }
}
?>
