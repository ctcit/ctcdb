<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Class BaseController
 *
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 * Extend this class in any new controllers:
 *     class Home extends BaseController
 *
 * For security be sure to declare any new methods as protected or private.
 */

#[\AllowDynamicProperties]
class BaseController extends Controller
{
    /**
     * An array of helpers to be loaded automatically upon
     * class instantiation. These helpers will be available
     * to all other controllers that extend BaseController.
     *
     * @var array
     */
    protected $helpers = [];

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

        $this->ctcModel = model('CTCModel');

        //--------------------------------------------------------------------
        // Preload any models, libraries, etc, here.
        //--------------------------------------------------------------------
        // E.g.: $this->session = \Config\Services::session();
    }

    // Support methods for loading pages
    // =================================
    protected function loadPage($contentPage, $title, $data = array(), 
                                $isEmbedded = false)
    {
        $data['title'] = $title;
        if ($this->validator !== null) {
            $data['validation'] = $this->validator;
        }
        if ($contentPage !== null) {
            $data['contentPage'] = $contentPage;
        }
        if (!$isEmbedded) {
            $data['menu'] = 'mainMenu';
        }
        $joomlaConfig = config('Joomla');
        $data['joomlaBaseURL'] = $joomlaConfig->baseURL;
        $template = $isEmbedded ? 'embeddedPageTemplate' : 'fullPageTemplate';
        return view($template, $data);
    }

    // Load a given page into a new (already created) window.
    // Only difference from _loadPage is that we need to set up
    // css for the new page.
    protected function loadPageInNewWindow($contentPage, $title, $data = array())
    {
        $data['css'] = 'ctcdbNewWindow.css';
        $data['title'] = $title;
        if (!($contentPage === NULL)) {
            $data['contentPage'] = $contentPage;
        }
        if ($this->validator !== null) {
            $data['validation'] = $this->validator;
        }
        return view('fullPageTemplate', $data);
    }

    // Support methods for processing data
    // =================================

    // Build a set of data with which to construct a form for a new member.
    // The returned value is an associative array indexed by fieldName.
    // Each entry is a (type, label, value [,values]) tuple, itself
    // represented as an associative array. Type is one of
    // 'text', 'textArea', 'bool' or 'enum'. As far as possible, this method
    // makes no assumptions about the DB schema -- it just builds an array of
    // the non-admin fields present in the members and memberships tables, using
    // the following conventions:
    //    1.  Fields ending in [Nn]otes are textArea fields.
    //    2.  Fields ending in Bool are 'bool' fields
    //    3.  Fields ending in Enum are 'enum' fields (currently used only
    //        at higher levels)
    //    4.  Fields ending in id or Admin are administrative fields, which are not
    //        for use in forms -- these are omitted
    //    5.  All other fields are 'text' fields.
    //    6.  fieldLabels are derivable from fieldNames by dropping any Bool
    //        suffix and then, assuming camel case, inserting spaces whenever
    //        the case changes from lower to upper.
    //    7.  The default value for bool fields is 'Yes', but all other fields
    //        is '' (except -- hack hack -- 'city' defaults to 'Christchurch').
    //
    // The 'values' member of the tuple is given only for 'enum' fields -- it is a
    // list of the possible values.
    //        TODO (low priority): use database schema to provide default values.
    function getNewMemberFormData()
    {
        $memberData = $this->makeFormData($this->ctcModel->getTableFields('members'));
        $membershipData = $this->makeFormData($this->ctcModel->getTableFields('memberships'));
        return array_merge($memberData, $membershipData);
    }

    // Extract from the database all the information required to display correctly
    // the member update form for a particular member (which includes
    // their current data values). Returns a set of form field definitions
    // as described in _getNewMemberFormData.
    function getUpdateFormDataFromDb($id)
    {
        $row = $this->ctcModel->getMemberDataByMemberId($id);
        $fields = array();
        foreach (array_keys($row) as $key) {
            $label = $this->makeLabel($key);
            $type = $this->formFieldType($key);
            $values = $this->formFieldValues($key);
            $field = array('type'=>$type, 'label' => $label, 'value' => $row[$key], 'values' => $values);
            $fields[$key] = $field;
        }
        $partner = $this->ctcModel->getPartnerName($id);
        if ($partner == '') {
            $partner = "N/A";
        }
        $fields['statusAdminHidden'] = array('type'=>'hidden', 'label'=>'', 'value'=>$row['statusAdmin']);
        $fields['partnerHidden'] = array('type'=>'hidden', 'label' =>'', 'value'=>$partner);
        $fields['memberNameHidden'] = array('type'=>'hidden', 'label' =>'', 'value'=> $row['firstName'].' '.$row['lastName']);
        return $fields;
    }

    // Build  set of form data for a new couple. This is a simple extension to the
    // getNewMemberFormData function (q.v.).
    function getNewCoupleFormData()
    {
        $member1Data = $this->makeFormData($this->ctcModel->getTableFields('members'),"__1");
        $member2Data = $this->makeFormData($this->ctcModel->getTableFields('members'),"__2");
        $membershipData = $this->makeFormData($this->ctcModel->getMembershipFields());
        return array_merge(array_merge($member1Data, $member2Data), $membershipData);
    }

    // Called, when a form validation check fails, to rebuild all the form-field
    // descriptions from the post request so that the form, when re-displayed,
    // contains all the user-entered data.
    // This function is used for new member, new couple and member update forms.
    // See getNewMemberFormData for a definition of the returned data.
    // PENDING - I think there's an easier way to do this in CodeIgniter 4!
    protected function getFormDataFromPost()
    {
        $fields = array();
        foreach (array_keys($_POST) as $fieldName) {
            $value = $this->request->getPost($fieldName);
            $type = $this->formFieldType($fieldName);
            if ($type == 'hidden') {
                $label = '';
            } else {
                $label = $this->makeLabel($fieldName);
            }
            $values = $this->formFieldValues($fieldName);
            $fields[$fieldName] = array('type'=>$type, 'label'=>$label, 'value'=>$value, 'values'=>$values);
        }
        return $fields;
    }

    // Constructs an array of form data (see '_getNewMemberFormData') from an array of DB fields.
    // For use with _getNewCoupleFormData, the suffix parameter is appended to all fieldNames.
    protected function makeFormData($fieldNames, $suffix = '')
    {
        $result = array();
        foreach ($fieldNames as $fieldName) {
            $label = $this->makeLabel($fieldName);
            $type = $this->formFieldType($fieldName);
            if ($type != 'admin')
            {
                $value = $type == 'bool' ? 'Yes' : '';
                if ($fieldName == 'city') {
                    $value = 'Christchurch'; // HACK!!!!
                }
                $name = $fieldName.$suffix;
                $values = $this->formFieldValues($fieldName);
                $result[$name] = array('type'=>$type, 'label'=>$label, 'value'=>$value, 'values'=>$values);
            }
        }
        return $result;
    }


    // Extract the type ('admin', 'text', 'textarea', 'enum', 'date', 'bool' or 'hidden') from a field
    // name, using the convention documented above.
    function formFieldType($fieldName)
    {
        if (preg_match('/Hidden$/', $fieldName))
        {
            $type = 'hidden';
        }
        else if (preg_match('/(Admin)|(id)|(Id)$/', $fieldName))
        {
            $type = 'admin';
        }
        else if (preg_match('/Date$|^date/', $fieldName))
        {
            $type = 'date';
        }
        else if (preg_match('/[nN]otes$/', $fieldName))
        {
            $type = 'textArea';
        }
        else if (preg_match('/Bool$/', $fieldName))
        {
            $type = 'bool';
        }
        else if (preg_match('/Enum$/', $fieldName))
        {
            $type = 'enum';
        }
        else
        {
            $type = 'text';
        }
        return $type;
    }

    // Return a list of the legitimate values for the given field (which must be
    // an 'enum' type. This is currently a special case hack, since there's currently
    // no way of knowing which table the field belongs to. Currently, it's only
    // expected to be called for the websiteUserTypeEnum or the preferredPhoneNum
    // fields -- other enumerated types either don't appear or
    // (e.g. membershipTypeEnum) are also handled with
    // special case code that restricts the options applicable to a given context.
    // TODO: Fix this hack
    protected function formFieldValues($fieldName)
    {
        $values = array();
        if ($fieldName == 'websiteUserTypeEnum') {
            $values = array('Registered', 'Editor', 'Author', 'Manager',
                'Administrator', 'Super Administrator');
        } else if ($fieldName == 'preferredPhoneEnum') {
            $values = array('Mobile', 'Home', 'Work');
        }
        return $values;
    }

    // Create a label from a fieldName, which may be either the name of a field in the
    // database or the name of a field from a POSTBACK form.
    // Labels are made by assuming a camelcase $field name and inserting spaces before
    // capitals, decapitalising the following word.
    // There are three special cases: the mailFMC label, the postbacks from couple
    // forms, for which the __[12] must be stripped, and fields with a suffix of
    // Bool, which is stripped.
    protected function makeLabel($fieldName)
    {
        if ($fieldName == 'mailFMCBool') {
            $label = 'Mail FMC';
        } else {
            $fieldName = preg_replace("/__[12]$/", "", $fieldName);
            $fieldName = preg_replace("/(Bool)|(Enum)$/","", $fieldName);  // Strip trailing bool or Enum
            $label = strToUpper($fieldName[0]);
            for ($i=1; $i < strlen($fieldName); $i++) {
                if (ctype_Upper($fieldName[$i])) {
                    $label .= ' ';
                }
                $label .= strtolower($fieldName[$i]);
            }
        }
        return $label;
    }
}
