<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Database\Query;

use CodeIgniter\Entity;

class DoorCode extends Entity
{
    // ...
}

class HutDoorCodesModel extends Model
{
    // Use the 'bookings' database, ad defined in Config/Database.php
    protected $DBGroup = 'hutbooking';
    protected $table = 'doorcodes';

    protected $primaryKey = 'id';
    protected $allowedFields = [
        'effective', 'code',
    ];
    protected $returnType = 'App\Models\DoorCode';
    protected $useTimestamps = true;
    protected $protectFields = true;
    protected $useSoftDeletes = true;

    public function __construct()
    {
        parent::__construct();
    }

    public function current()
    {
        $current = $this->where("effective < NOW()")
                    ->orderBy("effective", "desc")
                    ->limit(1)
                    ->first();
        return $current;
    }

    public function future()
    {
        $codes  = $this->where("effective > NOW()")
                     ->orderBy("effective", "asc")
                     ->findAll();
        return $codes;
    }

    public function atDate($date)
    {
        $code = $this->where("effective <= '$date'")
                     ->orderBy("effective", "desc")
                     ->limit(1)
                     ->first();
        return $code;
    }

    public function tryAdd($codeRecord)
    {
        $effective = new DateTime($booking->effective);
        $yesterday = new DateTime("yesterday");
        if ($effective->format('Y-m-d') < $now->format('Y-m-d')) {
            return ["result" => "Can only notify date changes for future dates"];
        }
        $success = $this->insert($codeRecord);
        if ($success) {
            return ["result" => "OK", "codeEntry" => $this->find($this->getInsertID())];
        }
        return ["result" => "Unknown failure"];
    }

}
