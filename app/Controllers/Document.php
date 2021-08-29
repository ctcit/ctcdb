<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class Document extends BaseController
{
    /**
     * Constructor.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param LoggerInterface   $logger
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);

        if (!session()->hasFullAccess) {
            die('You must be a club officer logged in to the website to use this function!!!');
        }

        $this->documentModel = model('DocumentModel');
    }

    public function index($message = "")
    {
        return $this->loadPage('showDocuments', 'Document Management',
                               ['documents' => $this->documentModel->findAll(),
                                'message' => $message]);
    }

    public function delete($id)
    {
        if ($id == 0) {
            $result = "Invalid document ID";
        } else {
            $result = $this->documentModel->delete($id) ?
                    "Document deleted" :
                    "Deletion failed";
        }
        return $this->index($result);
    }

    public function upload()
    {
        $file = $this->request->getFile('document');

        if (!$file->isValid()) {
            $message = "Invalid file";

        } else if (count($this->documentModel->findByName($file->getName())) != 0) {
            $message = "File already exists. To update, please delete it then re-upload.";
        } else {
            $data = file_get_contents( $file->getTempName() );
            $document = new \App\Models\Document();
            $document->name = $file->getName();
            $document->data = $data;
            $document->size = $file->getSize();
            $document->uploaded = date("Y-m-d H:i:s");
            $message = $this->documentModel->save($document) ?
                  "Uploaded succesfully" :
                  "Upload failed";
        }
        return $this->index($message);
    }

    public function download($id)
    {
        $document = $this->documentModel->find($id);
        if ($document === null || $document->data === null)
        {
            return $this->loadPage('operationOutcome',
            'CTCDB: Sorry, that document is non-existent or empty');
        }
        return $this->response->download($document->name, $document->data);
    }
}
