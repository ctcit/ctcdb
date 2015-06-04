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
    public $map_copyright = '';
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
        }

        if ($isNew) {
            $this->log('debug', "Inserting trip report: '" . print_r($this, True));
            if ($this->db->insert('jos_tripreport', $this) === FALSE) {
                throw new RuntimeException('Trip report insert failed');
            }
        } else { // Updating
            $this->log('debug', "Updating trip report: '" . print_r($this, True));
            $this->db->where('id', $this->id);
            if ($this->db->update('jos_tripreport', $this) === FALSE) {
                throw new RuntimeException('Trip report update failed');
            }
        }
        $this->log('debug', "Insert/update succeeded");
        $this->id = $this->db->insert_id();
        $this->saveEntities($this->id, 'image', $postData['images']);
        $this->saveEntities($this->id, 'gpx', $postData['gpxs']);
        $this->saveEntities($this->id, 'map', $postData['maps']);
    }
    
    
    public function getById($tripId) {
        // Initialise self from the row of the tripreports table corresponding
        // to the given trip report id, enhanced by lists of gpx, image and
        // map ids.
        $q = $this->db->get_where('jos_tripreport', 
            array('id' => $tripId, 'deleter_id' => NULL));
        if ($q->num_rows != 1) {
            throw new Exception("Tripreportmodel:getById($tripId) failed");
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
        $q = $this->db->get('jos_tripreport');
        $years = array();
        foreach($q->result() as $row) {
            $years[] = $row->year;
        }
        return $years;
    }
    
    public function getByYear($year) {
        // Return a list of all the trip reports for the given year.
        // Each is an object with a trip report ID, a date and a title
        $this->db->order_by('month desc, day desc');
        $q = $this->db->get_where('jos_tripreport',
            array('year'=>$year, 'deleter_id' => NULL));
        return $q->result();
    }
    
    private function loadEntities($tripId, $entity) {
        // Load the list of image, gpx or map rowss respectively for the
        // given tripId into $this, where $entity
        // is 'image', 'gpx' or 'map' respectively.
        // A hack is that there is no separate map table as maps are images,
        // so we do 'map' as a special case.
        // The entity list that gets plugged into $this is a list of 
        // objects, one per matching row of the jos_$entity table but without
        // any blob fields. Also, the ordering field from the bridging table
        // is added, too.
        $mainTable = $entity === 'map' ? 'jos_image' : 'jos_' . $entity;
        $fieldData = $this->db->field_data($mainTable);
        $fields = array();
        foreach ($fieldData as $field) {
            if (strpos(strtolower($field->type), 'blob') === FALSE) {
                $fields[] = "$mainTable.{$field->name}";
            }
        }
        $this->db->select(implode(',', $fields) . ',ordering');
        $this->db->from($mainTable);
        $entityId = $entity . '_id';
        $this->db->join("jos_tripreport_$entity", "$mainTable.id = jos_tripreport_$entity.$entityId");
        $this->db->where(array("jos_tripreport_$entity.tripreport_id" => $tripId));
        $this->db->order_by('ordering');
        $entities = $this->db->get();
        $listFieldName = $entity . 's';
        $this->$listFieldName = array();

        foreach ($entities->result() as $row) {
            array_push($this->$listFieldName, $row);
        }
    }
    
    
    private function saveEntities($tripId, $entityType, $entityList) {
        // Save the images, gpxs or maps (corresponding to $entityType = 'image',
        // 'gpx' and 'map' respectively) to the database. The supplied
        // $entityList is a list of records each with an id (the image, map or
        // gpx id) and an ordering.
        // It is assumed that the actual entities already exist and that
        // all that has to be done is delete all existing tripId->entityId
        // links and insert new ones.
        $bridgeTable = "jos_tripreport_$entityType";
        $this->db->delete($bridgeTable, array('tripreport_id'=>$tripId));
        foreach ($entityList as $entity) {
            $row = array("{$entityType}_id" => $entity['id'],
                         'tripreport_id'    => $tripId,
                         'ordering'         => $entity['ordering']);
            if ($this->db->insert($bridgeTable, $row) === FALSE) {
                throw new RuntimeException("Failed to insert $entityType");
            }
        }
    }
}

