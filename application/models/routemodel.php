<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

// This class provides an interface to gpx files in the database.
// Intended for use by the rest API, e.g. for TripReports.


class ArchiveItemModel extends CI_Model {
    public $id = 0;           // Archive id (int)
    public $caption = '';     // Caption used to display it
    public $gpxfilename = ''; // The gpx file itself (bytes, stored as Blob)
    public $routenotes = '';  // Notes associated with route
    public $Originatorid = 0; // Member id who supplied the route
    public $bounds = null;    // overall NZTM bounds for gpx contents [L,T,R,B]
    public $gpx = null;       // the gpx file contents
    public $trackdate = null;
    
   
    public function create_new($caption, $gpxfilename, $gpx, $routenotes, $originatorid, $bounds, $date) {
        $this->caption = $caption;
        $this->gpxfilename = $gpxfilename;
        $this->gpx = $gpx;
        $this->routenotes = $routenotes;
        $this->originatorid = $originatorid;
        $this->bounds = $bounds;
        $this->trackdate = ($date === null) ? date("Y-m-d"): $date;
        $sql = $this->InsertString();
        $conn = $this->db->conn_id;
        $stmt = $this->db->call_function('prepare', $conn, $sql);
        $stmt->bind_param('ss', $routenotes, $gpx);
        $stmt->execute();
        return $this->db->insert_id();
    }
    
    public function update_to_database($p_id, $p_caption, $p_gpxfilename, $p_gpx, $p_routenotes, $p_originatorid, $p_bounds, $p_date){
        $this->caption = $p_caption;
        $this->gpxfilename = $p_gpxfilename;
        $this->gpx = $p_gpx;
        $this->routenotes = $p_routenotes;
        $this->originatorid = $p_originatorid;
        $this->bounds = $p_bounds;
        $this->trackdate = ($p_date === null) ? date("Y-m-d"): $p_date;
        $sql = $this->UpdateString($p_id);
        $conn = $this->db->conn_id;
        $stmt = $this->db->call_function('prepare', $conn, $sql);
        $stmt->bind_param('ss', $p_routenotes, $p_gpx);
        $stmt->execute();
    }
    
    private function UpdateString($p_id){
        $result = "UPDATE archiveitems SET ";
        $data = $this->archiveData();
        $fields = $this->archiveFields();
        $iData = 0;
        foreach ($fields as $field){
            $result.= $field."=".$data[$iData++].",";
        }
        $result = substr($result, 0, $result.length - 1);
        $result .= " WHERE id = ".$p_id;
        return $result;
    }
    
    private function InsertString(){
        $strFields = implode(",",$this->archiveFields());
        $strData = implode(",",$this->archiveData());
        $sql = 'Insert Into archiveitems ('.$strFields. ')Values('.$strData.')';
        return $sql;
    }
    
    public function archiveData(){
        return array("'".$this->caption."'",
                    "'".$this->gpxfilename."'",
                    '?',
                    $this->originatorid,
                    '?', //routenotes
                    $this->bounds['left'],
                    $this->bounds['top'],
                    $this->bounds['right'],
                    $this->bounds['bottom'],
                    "'".$this->trackdate."'"
               );
    }
    
    public function archiveFields(){
        $result =array(
                    "caption"=>"`caption`",
                    "gpxfilename"=>"`gpxfilename`",
                    "routenotes"=>"`routenotes`",
                    "originatorid"=>"`originatorid`",
                    "gpx"=>"`gpx`",
                    "`left`",
                    "`top`",
                    "`right`",
                    "`bottom`",
                    "trackdate" =>"`trackdate`"
              );
        return $result;
    }
 
    public function delete_from_database($id) {
        $sql = "UPDATE archiveitems SET hidden = 1 WHERE id=".$id;
        $this->db->query($sql);
    }
    
    public function get_all_archive_items($p_where){
        //Everything except the gppx file contents
        $where = ($p_where !== null) ? (" and (".$p_where.")") : "";
        $sql = "SELECT archiveitems.id, ".  implode(",", array_values($this->archiveFields())).",firstName,lastName".
                                  " FROM archiveitems LEFT JOIN members ON archiveitems.originatorid = members.id".
                                  " WHERE hidden = 0".$where." ORDER BY caption";
        $query = $this->db->query($sql);
        $rows = $query->result_array();
        return $rows;
    }
    
    public function get_archive_item($p_id){
        $query = $this->db->query("select * from archiveitems where id = ".$p_id);
        $rows = $query->result_array();
        if (count($rows) != 1)
            return null;
        $row = $rows[0];
        $this->id = $row["id"];
        $this->caption = $row["caption"];
        $this->gpxfilename = $row["gpxfilename"];
        $this->gpx = $row["gpx"];
        $this->routenotes = $row["routenotes"];
        $this->originatorid = $row["originatorid"];
        $this->bounds = array("left"=>$row["left"],"top"=>$row["top"],"right"=>$row["right"],"bottom"=>$row["bottom"]);
        $this->trackdate = $row["trackdate"];
        return $this;
     }
     
     // Updates a single property
     public function update_archive_item($p_id, $p_propname, $p_value){
        $archivefields = $this->archiveFields();
        $fieldname = $archivefields[$p_propname];
        $sql = 'UPDATE archiveitems SET '.$fieldname.' =  ? WHERE id='.$p_id.';';
        $conn = $this->db->conn_id;
        $stmt = $this->db->call_function('prepare', $conn, $sql);
        $stmt->bind_param('s', $p_value);
        $stmt->execute();
     }
    
}
