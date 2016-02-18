<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

// This class deals with trip change notifications via text messages
// or the web interface.
define ('MAX_MESSAGES', 100);

class Tripchangenotificationmodel extends CI_Model {

    var $from = '';
    var $message = '';
    var $curlErrorNum = '';  // Curl response code on sending text via websms site
    var $curlError = '';     // Textual version of curl error

    public function __construct() {
        // Call the Model constructor
        parent::__construct();
    }


    public function keywords() {
        // List of keywords our message might begin with.
        // Anything else is bogus.
        return array("CTC", "TRIP");
    }

    public function insert($mob, $name, $message, $curlErrorNum=0, $curlError='') {
        // Insert the given message into the text-messages table.
        $this->db->insert('trip_change_notifications', array(
            'mob'         => $mob,
            'name'        => $name,
            'message'     => $message,
            'curlErrorNum'=> $curlErrorNum,
            'curlError'   => $curlError
        ));
    }


    public function getAllRecent() {
        // Return a list of text objects, each with fields from, message and
        // timestamps, ordered most-recent first, limited to 100.
        $this->db->order_by('timestamp', 'desc');
        $query = $this->db->get('trip_change_notifications', MAX_MESSAGES);
        return $query->result();
    }

}


