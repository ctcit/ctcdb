<?php

namespace App\Models;

use CodeIgniter\Model;

// This class provides an interface to gpx files in the database.
// Intended for use by the rest API, e.g. for TripReports.
class GPXModel extends Model
{
    public $id = 0;         // Gpx id (int)
    public $name = '';      // The name given to this gpx record (usu. the orig filename)
    public $caption = '';   // Caption used to display it
    public $gpx = null;     // The gpx file itself (bytes, stored as Blob)

    protected $DBGroup = 'tripReports';

    private $tripReportGPXFields = '';
    private $gpxFields = '';

    public function __construct()
    {
        // Call the Model constructor
        parent::__construct();
        $this->setTable('gpx');

        $this->gpxFields = $this->db->getFieldNames('gpx');
        $this->tripReportGPXFields = $this->db->getFieldNames('tripreport_gpx');
        $this->allowedFields = array_merge($this->gpxFields, $this->tripReportGPXFields);
    }

    public function create($name, $caption, $gpx)
    {
        // Create a gpx record.
        $this->name = $name;
        $this->gpx = $gpx;
        $this->caption = $caption;
        if ($this->insert($this) === false) {
            throw new RuntimeException("Failed writing GPX $name to DB");
        }
        return $this->db->insertID();
    }


    // Create a gpx record with the given (file)name, the given
    // caption and the given dataurl.
    // The dataurl should be of the form 'data:;base64,abababbbcdde012 ...';
    //
    // Return the id of the new record.
    public function create_from_dataurl($name, $caption, $dataurl)
    {
        list($type, $data) = explode(';', $dataurl);
        list($scheme, $media_type) = explode(':', $type);
        if (strtolower($scheme) !== 'data') {
            throw new InvalidArgumentException("dataurl for gpx file $name has unexpected format (scheme=$scheme)");
        }
        list($encoding, $data) = explode(',', $data);
        if (strtolower($encoding) !== 'base64') {
            throw new InvalidArgumentException("dataurl for gpx file $name has unexpected format (encoding=$encoding)");
        }
        $gpx = base64_decode($data);
        $id = $this->create($name, $caption, $gpx);
        return $id;
    }


    // Update just the name and caption of a gpx file, given its id.
    public function update_name_and_caption($id, $name, $caption)
    {
        $this->update($id, ['name'=>$name, 'caption'=>$caption]);
    }


    public function deleteGPX($gpx_id)
    {
        $this->delete($gpx_id);
        $this->db->table('tripreport_gpx')->delete(['gpx_id'=>$gpx_id]);
    }

}
