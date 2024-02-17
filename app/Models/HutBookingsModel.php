<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Database\Query;

use CodeIgniter\Entity;

class HutBooking extends Entity
{
    // ...
}

class HutBookingsModel extends Model
{
    // Use the 'bookings' database, ad defined in Config/Database.php
    protected $DBGroup = 'hutbooking';

    private const MaxBunks = 14;

    // We sneakily switch between the view and the underlying table as needed
    private const ViewTable = 'view_bookings';
    private const EditTable = 'bookings';

    protected $primaryKey = 'id';
    protected $allowedFields = [
        'member_id', 'start_date', 'nights', 'bunks',
        'status', 'notes', 'name', 'email', 'phone'
    ];
    protected $returnType = 'App\Models\HutBooking';
    protected $useTimestamps = true;
    protected $protectFields = true;

    protected $beforeInsert = ['SetEdit'];
    protected $beforeUpdate = ['SetEdit'];
    protected $beforeInsertBatch = ['SetEdit'];
    protected $beforeUpdateBatch = ['SetEdit'];

    protected $afterInsert = ['SetView'];
    protected $afterUpdate = ['SetView'];
    protected $afterInsertBatch = ['SetView'];
    protected $afterUpdateBatch = ['SetView'];

    public function __construct()
    {
        $this->table = HutBookingsModel::ViewTable;
        parent::__construct();
    }

    public function findByMember($id, $pageSize)
    {
        $today = (new \DateTime("today"))->format('Y-m-d');
        return $this->where(["member_id" => $id, "start_date >=" => $today])
                    ->orderBy("start_date", "asc")
                    ->paginate($pageSize);
    }

    public function findById($id, $onlyForUser=null)
    {
        $params = [ 'id' => $id ];
        if ($onlyForUser) {
            $params['member_id'] = $onlyForUser;
        }
        return $this->where($params)->findAll();
    }

    public function tryCreate($booking, $forUser=null)
    {
        // Check that there are sufficient bunks
        $dt = \DateTime::createFromFormat("Y-m-d", $booking->start_date);
        $available = true;
        for($i=0; $i<$booking->nights; $i++) {
            $date = $dt->format('Y-m-d');
            if($this->bunksAvailableOnDate($date) < $booking->bunks) {
                $available = false;
                break;
            }
            $dt->modify('+1 day');
        }
        if(!$available) {
            return ["result" => "Insufficient bunks available on one or more nights"];
        }
        if ($forUser != null) {
            $booking->member_id = $forUser;
            $booking->type = 'Member';
        }
        $success = $this->insert($booking);
        $this->table = HutBookingsModel::ViewTable;
        if ($success) {
            return ["result" => "OK", "booking" => $this->find($this->getInsertID())];
        }
        return ["result" => "Unknown failure"];
    }

    // date is like 2023-08-11
    public function bunksAvailableOnDate($date)
    {
        $q = $this->db->query("SELECT (".self::MaxBunks." - COALESCE(SUM(bunks),0)) as availability FROM `bookings` WHERE
                               start_date <= '$date' AND DATE_ADD(start_date, INTERVAL nights DAY) > '$date'");
        return $q->getRow()->availability;
    }

    protected function SetEdit($data)
    {
        $this->table = HutBookingsModel::EditTable;
        $this->builder = null;
        return $data;
    }

    protected function SetView($data)
    {
        $this->table = HutBookingsModel::ViewTable;
        $this->builder = null;
        return $data;
    }
}
