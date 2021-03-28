
<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

// This class provides an interface to the trip reports in the database.
// Intended for use by the rest API.


class Tripreportmodel extends CI_Model {
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
    
    protected function log($type, $message) {
        // Call log_message with the same parameters, but prefix the message
        // by *rest* for easy identification.
        log_message($type, '*tripreportmodel* ' . $message);
    }
    
    
    protected function error($message, $httpCode=400) {
        // Generate the http response containing the given message with the given
        // HTTP response code. Log the error first.
        $this->log('error', $message);
        $this->response($message, $httpCode);
    }
    
    
    public function create() {
        // Just returns a new empty trip report
        $this->year = strftime('%Y');
        $this->upload_date = strftime('%Y%m%d');
        $this->gpxs = array();
        $this->images = array();
        $this->maps = array();
        return $this;
    }
    
    
    public function delete($tripId) {
        // A pseudo-delete. Currently, we don't actually delete trip reports
        // or their associated resources. We just flag them as deleted.
        // This is so the webmaster can recover deleted trip reports
        // if users wail sufficiently loudly. Note however that individual
        // resources associated with a report can be deleted through the
        // client-side interface. So all the administrator can restore
        // is the state of the trip report at the time the user deleted it.
        global $userData;
        
        $deleter_id = $userData['userid'];
        if ($deleter_id == 0) {  // Shouldn't be possible
            throw new RuntimeException('Deletion by a non-logged in user?!');
        }
        $this->db->where(array('id'=>$tripId));
        $this->db->update('tripreport', array('deleter_id'=>$deleter_id));
    }
    
    
    public function saveReport($postData, $isNew) {
        // Create or update a trip report using the data just posted.
        // Return the id
        global $userData;

        if ($isNew) {
            unset($this->id);
        }
        unset($this->gpxs);
        unset($this->images);
        unset($this->maps);
        
        foreach ($this as $key=>$value) {
            if ($postData[$key]) {
                $this->$key = $postData[$key];
            }
        }

        if ($isNew) {
            $this->uploader_id = $userData['userid'];
            $this->uploader_name = $userData['name'];
            $this->upload_date = strftime('%Y%m%d');
            $this->log('debug', "Inserting trip report: '" . print_r($this, True));
            if (!$this->db->insert('tripreport', $this)) {
                throw new RuntimeException('Trip report insert failed');
            } else {
               $this->id = $this->db->insert_id();
            }
        } else { // Updating
            $this->log('debug', "Updating trip report: '" . print_r($this, True));
            $this->db->where('id', $this->id);
            if ($this->db->update('tripreport', $this) === FALSE) {
                throw new RuntimeException('Trip report update failed');
            }
        }
        $this->log('debug', "Insert/update succeeded");
        $this->saveEntities($this->id, 'image', $postData['images']);
        $this->saveEntities($this->id, 'gpx', $postData['gpxs']);
        $this->saveEntities($this->id, 'map', $postData['maps']);
        return $this->id;
    }
    
    
    public function getById($tripId) {
        // Initialise self from the row of the tripreports table corresponding
        // to the given trip report id, enhanced by lists of gpx, image and
        // map ids.
        $q = $this->db->get_where('tripreport', 
            array('id' => $tripId, 'deleter_id' => NULL));
        if ($q->num_rows != 1) {
            $this->id = 0; // This is the error indicator
            return $this;
        }
        foreach ($q->row_array() as $key=>$value) {
            $this->$key = $value;
        }
        
        $this->loadEntities($tripId, 'image');
        $this->loadEntities($tripId, 'gpx');
        $this->loadEntities($tripId, 'map');
        return $this;
    }
    
    public function getAllYears() {
        // Return a list of all the years for which trip reports exist,
        // in descending order.
        $this->db->select('year');
        $this->db->distinct();
        $this->db->order_by('year desc');
        $q = $this->db->get('tripreport');
        $years = array();
        foreach($q->result() as $row) {
            $years[] = $row->year;
        }
        return $years;
    }
    
    public function getByYear($year) {
        // Return a list of all the trip reports for the given year.
        // Each is an object with a trip report ID, a date and a title
        $this->db->select('id, trip_type, year, month, day, duration, date_display, user_set_date_display, title');
        $this->db->order_by('month desc, day desc');
        $q = $this->db->get_where('tripreport',
            array('year'=>$year, 'deleter_id' => NULL));
        return $q->result();
    }
    
    public function getAllTripReports() {
        // Return a list of all trip report ids in the database,
        // for use in constructing a pseudo site-map that can be used
        // by Google and other search engines to index trip reports.
        $query = 'id, year, title FROM tripreport WHERE deleter_id is NULL ' .
                  'ORDER BY year, title';
        $this->db->select($query);
        $result = $this->db->get();
        return $result->result();
    }

    public function getRecent($maxrecent, $maxdays) {
        $date = new DateTime();
        $date->sub( new DateInterval('P'.$maxdays.'D') );
        $lastDateOfInterest = date_format($date,"Y-m-d");

        $query = 'id, trip_type, year, month, day, duration, date_display, '.
                 'user_set_date_display, title, upload_date '.
                 'FROM tripreport '.
                 "WHERE deleter_id is NULL AND upload_date >='$lastDateOfInterest' ".
                 'ORDER BY upload_date DESC '.
                 'LIMIT '.$maxrecent;
                 
        $this->db->select($query);
        $result = $this->db->get();
        return $result->result();
   }
    
    public function getRecentCards($maxrecent, $maxdays){
        $date = new DateTime();
        $date->sub( new DateInterval('P'.$maxdays.'D') );
        $lastDateOfInterest = date_format($date,"Y-m-d");

        $query = 't.id, t.trip_type, t.year, t.month, t.day, t.duration, t.date_display, '.
                 't.user_set_date_display, t.title, t.upload_date, t.uploader_name, '.
                 '( SELECT image_id FROM tripreport_image ti JOIN image i ON ti.image_id = i.id '.
                 '  WHERE ti.tripreport_id = t.id '. 
                 '  AND t_width > t_height '.
                 '  ORDER BY ti.id ASC LIMIT 1 ) AS image_id '.
                 ' FROM tripreport t '.
                 "WHERE t.deleter_id is NULL AND t.upload_date >='$lastDateOfInterest' ".
                 'ORDER BY t.upload_date DESC '.
                 'LIMIT '.$maxrecent;
                 
        $this->db->select($query);
        $result = $this->db->get();
        return $result->result();
   }
    
    private function loadEntities($tripId, $entityType) {
        // Load the list of image, gpx or map rowss respectively for the
        // given tripId into $this, where $entityType
        // is 'image', 'gpx' or 'map' respectively.
        // A hack is that there is no separate map table as maps are images,
        // so we do 'map' as a special case.
        // The entity list that gets plugged into $this is a list of 
        // objects, one per matching row of the $entity table but without
        // any blob fields. Also, the ordering field from the bridging table
        // is added, too.
        $mainTable = $entityType === 'map' ? 'image' : $entityType;
        $fieldData = $this->db->field_data($mainTable);
        $fields = array();
        foreach ($fieldData as $field) {
            if (strpos(strtolower($field->type), 'blob') === FALSE) {
                $fields[] = "$mainTable.{$field->name}";
            }
        }
        $this->db->select(implode(',', $fields) . ',ordering');
        $this->db->from($mainTable);
        $entityId = $entityType . '_id';
        $this->db->join("tripreport_$entityType", "$mainTable.id = tripreport_$entityType.$entityId");
        $this->db->where(array("tripreport_$entityType.tripreport_id" => $tripId));
        $this->db->order_by('ordering');
        $entities = $this->db->get();
        $listFieldName = $entityType . 's';
        $this->$listFieldName = array();

        foreach ($entities->result() as $row) {
            array_push($this->$listFieldName, $row);
        }
    }
    
    
    private function saveEntities($tripId, $entityType, $entityList) {
        // Save the images, gpxs or maps (corresponding to $entityType = 'image',
        // 'gpx' and 'map' respectively) to the database. The supplied
        // $entityList is a list of records each with an id (the image, map or
        // gpx id). If the id is zero, the entity record must
        // include a dataUrl attribute containing the entity itself (image
        // or GPX) plus caption and name attributes; that is saved first, to
        // give an id.
        // The ordering of the saved entities is that of the list.
        $entityTable = $entityType === 'map' ? 'image' : $entityType;
        $bridgeTable = "tripreport_$entityType";
        
        // Firstly delete all existing link table entries, as their ordering 
        // attributes are probably wrong.
        $this->db->delete($bridgeTable, array('tripreport_id'=>$tripId));
        
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
            if (!$this->db->insert($bridgeTable, $row)) {
                throw new RuntimeException("Failed to insert $entityType");
            } else {
                $this->log('debug', "Saved $entityType id={$entity['id']} " .
                        "order=$ordering tripId=$tripId, table=$bridgeTable");
            }
            $ordering++;
        }
    }
    
    
    private function saveEntity($entityType, $entity) {
        // Add an entity (image, map or gpx) to the database and return its id
        $CI =& get_instance();
        if ($entityType === 'gpx') {
            $CI->load->model('gpxmodel');
            $id = $this->gpxmodel->create_from_dataurl($entity['name'], $entity['caption'], $entity['dataUrl']);
        } else if ($entityType === 'map' || $entityType === 'image') {
            $CI->load->model('imagemodel');
            $id = $this->imagemodel->create_from_dataurl($entity['name'], $entity['caption'], $entity['dataUrl']);
        } else {
            throw new RuntimeException("Unknown entity type: $entityType");
        }
        return $id;
    }
    
    
    private function updateEntity($entityType, $entity) {
        // Update an entity's name and caption (which may or may not have
        // been changed);
        $CI =& get_instance();
        if ($entityType === 'gpx') {
            $CI->load->model('gpxmodel');
            $id = $this->gpxmodel->update_name_and_caption($entity['id'], 
                $entity['name'], $entity['caption']);
        } else if ($entityType === 'map' || $entityType === 'image') {
            $CI->load->model('imagemodel');
            $id = $this->imagemodel->update_name_and_caption($entity['id'],
                $entity['name'], $entity['caption']);
        } else {
            throw new RuntimeException("Unknown entity type: $entityType");
        }
    }
        
    
    
    private function deleteEntities($tripId, $entityType) {
        // Delete the images, gpxs or maps (corresponding to $entityType = 'image',
        // 'gpx' and 'map' respectively) for the given TripReport from the database.
        // Currently this isn't used as we don't actually delete trip reports
        // but is left here in case it's needed in the future.
        // *** NEVER TESTED ***
        $entityTable = $entity === 'map' ? 'image' : $entity;
        $bridgeTable = "tripreport_$entityType";
        $tables = array($entityTable, $bridgeTable);
        
        $this->db->from($bridgeTable);
        $this->db->join($entityTable, "$bridgeTable.{$entityType}_id=$entityTable.id");
        $this->db->where(array("$bridgeTable.tripreport_id"=>$tripId));
        $this->db->delete($tables);
    }
}


