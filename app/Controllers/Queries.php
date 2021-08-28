<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/* This file is the controller for all queries. The 'user_queries' table contains the various
 * queries that can be run by a particular user. Queries owned by the special user _menu_
 * are used in the main menu, with a tooltip obtained from the first sentence of the description.
 */
class Queries extends BaseController
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

        helper(['url','form','date','pageload']);
        $this->isWebmaster = array_search('webmaster', session()->roles) !== false;
    }

    // Action from a generic query display view to save the query result to
    // a csv file on the user's desktop.
    public function saveCSV($resultid)
    {
        $result = $this->ctcModel->getSavedResult($resultid);
        if ($result === null) {
            return $this->loadPageInNewWindow('staleCSVData', 'CTCDB: Sorry, that result has been deleted from the cache. Please re-run the query.');
        }

        $csv = $this->makeCSV($result);
        return $this->response->download("query_result$resultid.csv", $csv);
    }


    /**
     * Do a print merge of the currently-displayed table data with the .odt document
     * selected by the combo box
     */
    public function printMerge($resultId)
    {
        $tableData = $this->ctcModel->getSavedResult($resultId);
        if ($tableData === null) {
            return $this->loadPageInNewWindow('staleCSVData',
                'CTCDB: Sorry, that result has been deleted from the cache. Please re-run the query.');
        }
        $docId = $this->request->getPost('doc_id');
        $document = $this->ctcModel->getDocument($docId);
        require_once("../newsletter/generate_odt.php");
        $engine = new XmlTemplateEngine(null, $tableData);
        $output = $engine->processOdtTemplate($document);
        return $this->response->download('mergeOutput.odt', $output);
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
    public function emailMerge($resultId)
    {
        $tableData = $this->ctcModel->getSavedResult($resultId);
        if ($tableData === null) {
            return $this->loadPageInNewWindow('staleCSVData',
                'CTCDB: Sorry, that result has been deleted from the cache. Please re-run the query.');
        }

        $docId = $this->request->getPost('email_doc_id');
        $subject = $this->request->getPost('subject');
        $document = $this->ctcModel->getDocument($docId);
        if ($document == '') {
            return $this->loadPageInNewWindow('emptyDocument',
            'CTCDB: Sorry, that document is non-existent or empty');
        }

        $nRecipients = count($tableData);
        if ($nRecipients == 0) {
            return $this->loadPageInNewWindow('operationOutcome',
            'CTCDB: Sorry, the recipient list appears to be empty!');
        }

        $batchId = $this->ctcModel->addEmailBatch($docId, $subject, $nRecipients);
        $fails = 0;
        foreach ($tableData as $row) {
            if (!property_exists($row, 'email') || empty($row->email)) {
                $fails += 1;
            }
        }

        if ($fails > 0) {
            return $this->loadPageInNewWindow('operationOutcome',
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
                $this->ctcModel->queueMailItem($batchId, $row->email, $subject, $message);
            }


            return $this->loadPage('confirmEmails', 'ConfirmEmailMerge',
                array( 'batchId' => $batchId,
                       'docId'   => $docId,
                       'subject' => $subject,
                       'message' => $sampleMessage,
                       'recipients' => $recipients) );
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
        $this->ctcModel->confirmMailBatch($batchId);
        $subject = $this->request->getPost('subject');
        $numRecipients = $this->request->getPost('nRecipients');
        $extraInfo = "Mail with subject '$subject' has been queued for delivery ".
             "to $numRecipients recipients. An email will be sent to the ".
             "webmaster when all messages have been sent. This may take up to an hour.";
        return $this->loadPage('operationOutcome', 'Mail queued for delivery',
                array('extraInfo' => $extraInfo));

    }

    // Callback from showQueries form when webmaster chooses a different user whose
    // queries are to be edited.
    public function switchUser()
    {
        if (!$this->isWebmaster) {
            die("Security breach attempt");
        }
        $newUser = $this->request->getPost('NewUser');
        if ($newUser === null)
        {
            die('No user specified in POST');
        }
        return $this->manageQueries($newUser);
    }

    // Function to allow arbitrary SQL queries on the database and maintain a library
    // of such queries for the specified user/login. If the user is null, the current user's
    // queries are managed. Otherwise the specifier's users queries are managed, but this
    // option is only available to the Webmaster. As a special case of the latter,
    // if the user is 0, the set of queries is the set displayed in the main menu.
    public function manageQueries($user = null)
    {
        if ($user == null) {
            $user = session()->userID;
        } else {
            if ($user != session()->userID && !$this->isWebmaster) {
                die("Security breach disallowed");
            }
        }
        $queryList = $this->ctcModel->getQueries($user);
        $table = array(array('','Name', 'Description','',''));
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
        } else {
            $ownerName = $this->ctcModel->getMemberName($user);
        }
        $header = "Queries belonging to $ownerName";
        if ($this->isWebmaster) {
            // Webmaster gets presented with an option for changing to a different user's query set
            $loginList = $this->ctcModel->getQueryOwningMembers();
            // Add associative array element
            $loginList[0] = "Main Menu";
            return $this->loadPage('showQueries', 'Queries',
                array('queryTable' => $table, 'header' => $header, 'switchUserList' => $loginList,
                        'currentUserId' => $user));
        } else {
            return $this->loadPage('showQueries', 'Queries',
                array('queryTable' => $table, 'header' => $header, 'currentUserId' => $user));
        }
    }

    public function runQuery($queryID)
    {
        $row = $this->ctcModel->getQuery($queryID);
        $name = $row->name;
        return $this->displayQueryResult($row->sqlquery, "Query '$name'");
    }

    // Called when
    // 1.  constructing a new query ($id = 0)
    // 2.  editing an existing query ($id != 0)
    // 3.  saving either of the above (postback)
    // $ownerId parameter is required only when creating a new query ($id == 0)
    // and when owner is not the current logged in user. [Can only be used by
    // the Webmaster].
    public function editQuery($id = 0, $ownerId = null)
    {
        $data = [];
        $data['id'] = $id;

        $isPostBack = count($_POST) > 0;
        if ($isPostBack) {
            $fields = $this->getFormDataFromPost();
            if ($this->validate($this->queryValidationRules())) {
                // Successful postback?
                $currentUser = session()->userID;
                $queryOwnerId = $this->request->getPost('queryOwnerId');
                if ($currentUser != $queryOwnerId && !$this->isWebmaster) {
                    die("Attempted security breach denied.");
                }

                $queryName = $fields['queryName']['value'];
                $description = $fields['description']['value'];
                $query = $fields['query']['value'];
                $this->ctcModel->saveQuery($id, $queryName, $description, $query, $queryOwnerId);
                // Take user back to query list form
                return $this->manageQueries($queryOwnerId);
            }
            foreach($fields as $fieldName => $field)
            {
                $data[$fieldName] = $field['value'];
            }
        } else {
            // Not a post - create new data
            if ($id == 0) {
                // A new query
                $title = 'New query';
                $data['queryName'] = '';
                $data['description'] = '';
                $data['query'] = $this->sampleSQL();
                if ($ownerId === null) {
                    $ownerId = session()->userID;
                } else if ($ownerId != session()->userID && !$this->isWebmaster) {
                    die("Security breach disallowed");
                }
                $data['queryOwnerId'] = $ownerId;
            } else {
                // Editing an existing query)
                $row = $this->ctcModel->getQuery($id);
                $title = 'Edit query ' . $row->name;
                $data['queryName'] = $row->name;
                $data['query'] = $row->sqlquery;
                $data['description'] = $row->description;
                $data['queryOwnerId'] = $row->userIdAdmin;
            }
        }

        // Validation failed or wasn't a postback: (re)load form
        return $this->loadPage('editQuery', $title, ['data' => (object)$data]);
    }

    public function deleteQuery($id)
    {
        $row = $this->ctcModel->getQuery($id);
        $name = $row->name;
        return $this->loadPage('deleteQueryConfirm', 'Confirm query deletion',
            array('id' => $id, 'queryName' => $name));
    }

    public function deleteQuery2($id)
    {
        $this->ctcModel->deleteQuery($id);
        return $this->loadPage('home','CTCDB: Home');
    }

    // Tests if query is valid and if so displays generic query result
    // (which will be in a new window)
    public function testQuery()
    {
        if ($this->validate($this->queryValidationRules())) {
            $name = $this->request->getPost('queryName');
            $query = $this->request->getPost('query');
            return $this->displayQueryResult($query, "CTCDB: '$name' query output");
        } else {
            return $this->loadPageInNewWindow('queryTestFailed', 'Bad query');
        }
    }


    // ENVELOPE-PRINTING QUERY STUFF
    // =============================

    // Starting point for envelope printing -- just display the query builder form.
    public function printEnvelopes($error = '')
    {
        return $this->loadPage('printEnvelopesForm', 'CTCDB: Print Envelopes',
            array('error'=> ''));
    }

    // Return a subquery that selects the membership subset that receives the given item,
    // as specified by the given where clause (empty to select all members).
    public function subQuery($item, $where)
    {
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
    public function printEnvelopes2()
    {
        // Build array of tuples (formFieldName, itemName, whereClause).

        $items = array(
            array('nl', 'Newsletter', "mailNewsletter = 'Yes'"),
            array('fmcb', 'FMC&nbsp;Bulletin', "mailFMC = 'Yes'"),
            array('fmcc', 'FMC&nbsp;Card', "isFmcMember"),
            array('subs', 'Subs&nbsp;Invoice', "paid = 'No'"),
            array('cookie', 'Cookie', '')
        );

        $subQueries = '';
        $sep = '';
        $showUnpaid = $this->request->getPost('showUnpaid');
        if ($showUnpaid) {
            $unpaid = "IF(paid='No', 'Unpaid','')";
        } else {
            $unpaid = "''";
        }
        foreach ($items as $item) {
            list($field, $itemName, $where) = $item;
            if ($this->request->getPost($field)) {
                $subQueries .= $sep . $this->subQuery($itemName, $where, $showUnpaid);
                $sep = "\n        UNION\n";
            }
        }

        if ($subQueries == '') {
            return $this->loadPage('printEnvelopesForm', 'CTCDB: Print Envelopes',
                array('error'=> 'You have to select <em>something</em> to put in the envelopes!'));
        } else {
            $ordering = '';
            $sep = '';
            foreach (array('sort1', 'sort2', 'sort3') as $field) {
                $key = $this->request->getPost($field);
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
      isFmcMember,
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

            if ($this->request->getPost('showQuery')) {
                return $this->loadPageInNewWindow('displayQuery', 'Print Envelopes Query',
                    array('query' => $mainQuery));
            } else {
                return $this->displayQueryResult($mainQuery, "CTCDB: Envelope printing query output");
            }
        }
    }

    // SUPPORT METHODS
    // ===============

    // Set up the form_validation to apply to a query form.
    // Used by both editQuery and testQuery
    private function queryValidationRules()
    {
        return [
            "query" =>
            [
                'label' => "SQL Query",
                'rules' => 'query_check'
            ],
            "queryName" =>
            [
                'label' => "Query Name",
                'rules' => 'required|alpha_dash'
            ],
        ];
    }


    // Returns the string $s in toto if less than $fieldWidth else the string $s
    // truncated and with " ..." appended to exactly fit a field of $fieldWidth
    private function truncated($s, $fieldWidth)
    {
        if (strlen($s) > $fieldWidth) {
            $s = substr($s, 0, $fieldWidth - 4) . " ...";
        }
        return $s;
    }


    /**
     * Display the query result in a new window, saving all rows to the database
     * in the savedquery_results table for use if "save CSV" is clicked. [Why
     * save the whole table instead of just the query that generated it?
     * Because under some circumstances (I don't remember exactly what they were)
     * there can be a nasty race problem, where the data that gets saved isn't actually
     * the data showing in the screen when the user clicks saved CSV.]
     *
     * @param $sql
     * @param $title
     */
    private function displayQueryResult($sql, $title)
    {
        $query = $this->ctcModel->genericQuery($sql);
        $queryResultId = $this->ctcModel->saveResult($query);
        $query = $this->ctcModel->genericQuery($sql);  // Re-run to rebuild field list :-(
        $docs = $this->ctcModel->getMergeDocuments('odt');  // Get a list of candidate .odt documents
        $emailDocs = $this->ctcModel->getMergeDocuments('txt');  // Get a list of candidate .txt documents
        return $this->loadPageInNewWindow('genericQueryDisplay',
            $title, array('query' => $query, 'resultid' => $queryResultId,
                          'mergeDocs' => $docs, 'emailDocs' => $emailDocs));
    }


    private function sampleSQL()
    {
        $sql = <<<query_
SELECT *
FROM view_members
query_;
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
    private function makeCSV($queryResult, $delim = ",", $newline = "\n", $enclosure = '"')
    {
        $columnNames = array_keys($queryResult[0]);
        $out = '';

        // First generate the headings from the table column names
        foreach ($columnNames as $name) {
            $out .= $enclosure.str_replace($enclosure, $enclosure.$enclosure, $name).$enclosure.$delim;
        }

        $out = rtrim($out);
        $out .= $newline;

        // Next blast through the result array and build out the rows
        foreach ($queryResult as $row)
        {
            foreach ($row as $item) {
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
    private function expandTemplate($template, $row)
    {
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
            } else {
                return $this->expandTemplate($bits[1], $row) .
                       $this->expandTemplate($bits[5], $row);
            }
        } else {
            foreach ($row as $field=>$value) {
                $template = str_replace('{{'.$field.'}}', $value, $template);
            }
            return $template;
        }
    }
}
