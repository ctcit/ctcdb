<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Database\Query;

use CodeIgniter\Entity;

class Document extends Entity
{
    // ...
}

// A model for documents
class DocumentModel extends Model
{
    protected $table         = 'documents';
    protected $allowedFields = [
        'name', 'size', 'uploaded', 'data'
    ];
    protected $returnType    = 'App\Models\Document';

    public function __construct()
    {
        // Call the Model constructor
        parent::__construct();
    }

    public function findByName($name)
    {
        return $this->where(["name" => $name])->get()->getResult();
    }

}
