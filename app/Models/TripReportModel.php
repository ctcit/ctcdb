<?php

namespace App\Models;

use CodeIgniter\Model;

class TripReportModel extends Model
{
    public $id = 0;
    public $trip_type = 'club';
    public $year = 0;
    public $month = 0;
    public $day = 0;
    public $duration = 0;  // In days
    public $date_display = '';
    public $user_set_date_display = 0;  // Boolean true iff $date_display set explicitly
    public $title = '';
    public $body = '';
    public $map_copyright = 'Topomap data is Crown Copyright Reserved.';
    public $uploader_id = 0;
    public $uploader_name = '';
    public $upload_date = '';

    // The next 3 attributes are arrays of gpx, image and map database rows
    // for this trip report, with the addition of ordering and the removal
    // of any blob fields.
    public $gpxs;
    public $images;
    public $maps;

    // Use the 'tripreports' database, ad defined in Config/Database.php
    protected $DBGroup = 'tripReports';
    protected $returnType = 'object';

    public function __construct()
    {
        // Call the Model constructor
        parent::__construct();
        $this->setTable('tripreport');

        $this->tripReportFields = $this->db->getFieldNames('tripreport');
        $this->gpxFields = $this->db->getFieldNames('gpx');
        $this->tripReportGPXFields = $this->db->getFieldNames('tripreport_gpx');
        $this->allowedFields = array_merge($this->tripReportFields, $this->gpxFields, $this->tripReportGPXFields);
    }


    // Just returns a new empty trip report
    public function create()
    {
        $this->year = strftime('%Y');
        $this->upload_date = strftime('%Y%m%d');
        $this->gpxs = array();
        $this->images = array();
        $this->maps = array();
        return $this;
    }

    // A pseudo-delete. Currently, we don't actually delete trip reports
    // or their associated resources. We just flag them as deleted.
    // This is so the webmaster can recover deleted trip reports
    // if users wail sufficiently loudly. Note however that individual
    // resources associated with a report can be deleted through the
    // client-side interface. So all the administrator can restore
    // is the state of the trip report at the time the user deleted it.
    public function deleteTripReport($tripId)
    {
        $deleter_id = session()->userID;
        if ($deleter_id == 0) {
            throw new RuntimeException('Deletion by a non-logged in user?!');
        }
        return $this->update($tripId, array('deleter_id'=>$deleter_id));
    }


    // Create or update a trip report using the data just posted.
    // Return the id
    public function saveTripReport($postData, $isNew)
    {
        $insertData = [];
        foreach ($this as $key=>$value) {
            // PENDING - Relies on the front-end to ensure all required fields are set
            if (array_key_exists($key, $postData)) {
                $this->$key = $postData[$key];
                $insertData[$key] = $postData[$key];
            }
        }

        if ($isNew) {
            $insertData['uploader_id'] = session()->userID;
            $insertData['uploader_name'] = session()->name;
            $insertData['upload_date'] = strftime('%Y%m%d');
            $this->log('debug', "Inserting trip report: '" . print_r($this, True));
            if (!$this->insert($insertData)) {
                throw new RuntimeException('Trip report insert failed');
            } else {
               $this->id = $this->db->insertID();
            }
        } else {
            // Updating
            $this->log('debug', "Updating trip report: '" . print_r($this, True));
            if ($this->update($this->id, $this) === FALSE) {
                throw new RuntimeException('Trip report update failed');
            }
        }
        $this->log('debug', "Insert/update succeeded");
        if (array_key_exists('images', $postData)) {
            $this->saveEntities($this->id, 'image', $postData['images']);
        }
        if (array_key_exists('gpxs', $postData)) {
            $this->saveEntities($this->id, 'gpx', $postData['gpxs']);
        }
        if (array_key_exists('maps', $postData)) {
            $this->saveEntities($this->id, 'map', $postData['maps']);
        }
        return $this->id;
    }


    // Initialise self from the row of the tripreports table corresponding
    // to the given trip report id, enhanced by lists of gpx, image and
    // map ids.
    public function getById($tripID)
    {
        $result = $this->find($tripID);
        if ($result == null || $result->deleter_id != null) {
            // This is the error indicator
            $this->id = 0;
            return $this;
        }
        foreach ($result as $key=>$value) {
            $this->$key = $value;
        }

        $this->loadEntities($tripID, 'image');
        $this->loadEntities($tripID, 'gpx');
        $this->loadEntities($tripID, 'map');
        return $this;
    }

    public function getAllYears()
    {
        // Return a list of all the years for which trip reports exist,
        // in descending order.
        $q = $this->builder()->select('year')
             ->distinct()
             ->orderBy('year desc')
             ->get();
        foreach($q->getResult() as $row) {
            $years[] = $row->year;
        }
        return $years;
    }

    // Return a list of all trip report ids in the database,
    // for use in constructing a pseudo site-map that can be used
    // by Google and other search engines to index trip reports.
    public function getAllTripReports()
    {
        $q = $this->builder()->select('id, year, title')
                  ->where('deleter_id is NULL')
                  ->orderBy('year, title')
                  ->get();
        return $q->getResult();
    }

    // Return a list of all the trip reports for the given year.
    // Each is an object with a trip report ID, a date and a title
    public function getByYear($year, $limit)
    {
        $q = $this->builder('view_tripreports')->select('*')
                  ->where(['year'=>$year])
                  ->orderBy('month desc, day desc')
                  ->limit($limit)
                  ->get();
        return $q->getResult();
    }

    public function getRecent($limit) {
        $q = $this->builder('view_tripreports')->select('*')
                  ->orderBy('upload_date desc')
                  ->limit($limit)
                  ->get();
        return $q->getResult();
   }

    // Load the list of image, gpx or map rowss respectively for the
    // given tripId into $this, where $entityType
    // is 'image', 'gpx' or 'map' respectively.
    // A hack is that there is no separate map table as maps are images,
    // so we do 'map' as a special case.
    // The entity list that gets plugged into $this is a list of
    // objects, one per matching row of the $entity table but without
    // any blob fields. Also, the ordering field from the bridging table
    // is added, too.
    private function loadEntities($tripId, $entityType)
    {
        $mainTable = $entityType === 'map' ? 'image' : $entityType;
        $fieldData = $this->db->getFieldData($mainTable);
        $fields = array();
        foreach ($fieldData as $field) {
            if (strpos(strtolower($field->type), 'blob') === FALSE) {
                $fields[] = "$mainTable.{$field->name}";
            }
        }
        $entityId = $entityType . '_id';
        $q = $this->db->table($mainTable)->select(implode(',', $fields) . ',ordering')
                 ->join("tripreport_$entityType", "$mainTable.id = tripreport_$entityType.$entityId")
                 ->where(array("tripreport_$entityType.tripreport_id" => $tripId))
                 ->orderBy('ordering')
                 ->get();
        $listFieldName = $entityType . 's';
        $this->$listFieldName = array();
        foreach ($q->getResult() as $row) {
            array_push($this->$listFieldName, $row);
        }
    }


    // Save the images, gpxs or maps (corresponding to $entityType = 'image',
    // 'gpx' and 'map' respectively) to the database. The supplied
    // $entityList is a list of records each with an id (the image, map or
    // gpx id). If the id is zero, the entity record must
    // include a dataUrl attribute containing the entity itself (image
    // or GPX) plus caption and name attributes; that is saved first, to
    // give an id.
    // The ordering of the saved entities is that of the list.
    private function saveEntities($tripId, $entityType, $entityList)
    {
        $entityTable = $entityType === 'map' ? 'image' : $entityType;
        $bridgeTable = "tripreport_$entityType";

        // Firstly delete all existing link table entries, as their ordering
        // attributes are probably wrong.
        $this->db->table($bridgeTable)->delete(array('tripreport_id'=>$tripId));

        // Now process each entity in turn
        $ordering = 0;
        foreach ($entityList as $entity) {
            $this->log('debug', "Saving $entityType, id={$entity['id']}, name='{$entity['name']}");
            if ($entity['id'] == 0) {
                $entity['id'] = $this->saveEntity($entityType, $entity);
            } else {
                $this->updateEntity($entityType, $entity);
            }
            $row = array("{$entityType}_id" => $entity['id'],
                         'tripreport_id'    => $tripId,
                         'ordering'         => $ordering);
            if (!$this->db->table($bridgeTable)->insert($row)) {
                throw new RuntimeException("Failed to insert $entityType");
            } else {
                $this->log('debug', "Saved $entityType id={$entity['id']} " .
                        "order=$ordering tripId=$tripId, table=$bridgeTable");
            }
            $ordering++;
        }
    }


    // Add an entity (image, map or gpx) to the database and return its id
    private function saveEntity($entityType, $entity)
    {
        if ($entityType === 'gpx') {
            $gpxModel = model('GPXModel');
            $id = $gpxModel->create_from_dataurl($entity['name'], $entity['caption'], $entity['dataUrl']);
        } else if ($entityType === 'map' || $entityType === 'image') {
            $imageModel = model('ImageModel');
            $id = $imageModel->create_from_dataurl($entity['name'], $entity['caption'], $entity['dataUrl']);
        } else {
            throw new RuntimeException("Unknown entity type: $entityType");
        }
        return $id;
    }


    // Update an entity's name and caption (which may or may not have
    // been changed);
    private function updateEntity($entityType, $entity)
    {
        if ($entityType === 'gpx') {
            $gpxModel = model('GPXModel');
            $id = $gpxModel->update_name_and_caption($entity['id'],
                $entity['name'], $entity['caption']);
        } else if ($entityType === 'map' || $entityType === 'image') {
            $imageModel = model('ImageModel');
            $id = $imageModel->update_name_and_caption($entity['id'],
                $entity['name'], $entity['caption']);
        } else {
            throw new RuntimeException("Unknown entity type: $entityType");
        }
    }


    // Delete the images, gpxs or maps (corresponding to $entityType = 'image',
    // 'gpx' and 'map' respectively) for the given TripReport from the database.
    // Currently this isn't used as we don't actually delete trip reports
    // but is left here in case it's needed in the future.
    // *** NEVER TESTED ***
    private function deleteEntities($tripId, $entityType)
    {
        $entityTable = $entity === 'map' ? 'image' : $entity;
        $bridgeTable = "tripreport_$entityType";
        $tables = array($entityTable, $bridgeTable);

        $this->db->table($bridgeTable)
                 ->join($entityTable, "$bridgeTable.{$entityType}_id=$entityTable.id")
                 ->where(array("$bridgeTable.tripreport_id"=>$tripId))
                 ->delete($tables);
    }

    // Call log_message with the same parameters, but prefix the message
    // by *tripreportmodel* for easy identification.
    private function log($type, $message)
    {
        log_message($type, '*tripreportmodel* ' . $message);
    }


    // Generate the http response containing the given message with the given
    // HTTP response code. Log the error first.
    private function error($message, $httpCode=400)
    {
        $this->log('error', $message);
        $this->response($message, $httpCode);
    }
}


