<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Database\Query;

use CodeIgniter\Entity;

class Member extends Entity
{
    // ...
}

// Model to access a single member
// Currently very basic and NOT used by CTCModel
class MemberModel extends Model
{
    protected $table = 'members';
    protected $allowedFields = [];
    protected $returnType = 'App\Models\Member';

    public function __construct()
    {
        parent::__construct();
    }

    public function findByMembershipId($id)
    {
        return $this->where(["member_id" => $id])->findAll();
    }
}
