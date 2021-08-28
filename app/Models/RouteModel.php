<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Database\Query;

// This class provides an interface to route information in the database.
// Intended for use by the REST API, e.g. for the route archive.
class RouteModel extends Model
{
    public $id = 0;           // Route id (int)
    public $caption = '';     // Caption used to display it
    public $gpxfilename = ''; // The gpx file itself (bytes, stored as Blob)
    public $routenotes = '';  // Notes associated with route
    public $originatorid = 0; // Member id who supplied the route
    public $bounds = null;    // overall NZTM bounds for gpx contents [L,T,R,B]
    public $gpx = null;       // the gpx file contents
    public $trackdate = null;

    function __construct()
    {
        // Call the Model constructor
        parent::__construct();
        $this->setTable('routes');

        $this->allowedFields = $this->db->getFieldNames('routes');
    }

    public function createNew($caption, $gpxfilename, $gpx, $routenotes, $originatorid, $bounds, $date)
    {
        $this->caption = $caption;
        $this->gpxfilename = $gpxfilename;
        $this->gpx = $gpx;
        $this->routenotes = $routenotes;
        $this->originatorid = $originatorid;
        $this->bounds = $bounds;
        $this->trackdate = ($date === null) ? date("Y-m-d"): $date;
        $sql = $this->insertString();

        $query = $this->db->prepare(function($db) {
            return (new Query($db))->setQuery($sql);
        });

        $query->execute($routenotes, $gpx);
        return $this->db->insertID();
    }

    public function updateToDatabase($id, $caption, $gpxfilename, $gpx, $routenotes, $originatorID, $bounds, $date)
    {
        $this->caption = $p_caption;
        $this->gpxfilename = $gpxfilename;
        $this->gpx = $gpx;
        $this->routenotes = $routenotes;
        $this->originatorid = $originatorID;
        $this->bounds = $bounds;
        $this->trackdate = ($date === null) ? date("Y-m-d"): $date;
        $sql = $this->updateString($id);

        $query = $this->db->prepare(function($db) {
            return (new Query($db))->setQuery($sql);
        });

        $query->execute($routenotes, $gpx);
    }

    private function updateString($id)
    {
        $result = "UPDATE routes SET ";
        $data = $this->routeData();
        $fields = $this->routeFields();
        $iData = 0;
        foreach ($fields as $field) {
            $result .= $field."=".$data[$iData++].",";
        }
        $result = substr($result, 0, ($result.length) - 1);
        $result .= " WHERE id = ".$id;
        return $result;
    }

    private function insertString()
    {
        $strFields = implode(",",$this->routeFields());
        $strData = implode(",",$this->routeData());
        $sql = 'INSERT INTO routes ('.$strFields. ')Values('.$strData.')';
        return $sql;
    }

    public function routeData()
    {
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

    public function routeFields()
    {
        $result = array(
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

    public function deleteFromDatabase($id)
    {
        $this->update($id, ['hidden' => 1]);
    }

    public function getAllRoutes($p_where)
    {
        // Everything except the gpx file contents
        $q = $this->select( [ "routes.id",
                 "caption",
                 "gpxfilename",
                 "routenotes",
                 "originatorid",
                 "left",
                 "top",
                 "right",
                 "bottom",
                 "trackdate",
                 "firstName",
                 "lastName"
                ])
                ->join('members', 'routes.originatorid = members.id', 'left')
                ->orderBy('caption')
                ->where('hidden', 0)
                ->get();

        return $q->getResultArray();
    }

    public function getRoute($id)
    {
        $row = $this->find($id);
        if ($row === null) {
            return null;
        }
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
     public function updateRoute($id, $propname, $value)
     {
        $routefields = $this->routeFields();
        $fieldname = $routefields[$propname];
        $sql = 'UPDATE routes SET '.$fieldname.' =  ? WHERE id='.$id.';';
        $query = $this->db->prepare(function($db) {
            return (new Query($db))->setQuery($sql);
        });

        $query->execute($routenotes, $gpx);
     }
}
