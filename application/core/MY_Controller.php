<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * This extension to controller has page-load functions that build pages
 * using a template approach, with a fixed header and footer and user-selectable
 * menus and main content page.
 * It also has various other support functions for building forms from
 * data from ctcModel.
 */
class MY_Controller extends CI_Controller {


    public function MY_Controller() {
        parent::__construct();
    }

    // Support methods for loading pages
    // =================================

    function _loadPage($contentPage, $title, $data = array(), $menuReqd = True)
    {
        $this->load->library('table');
        $data['title'] = $title;
        if (!($contentPage === NULL)) {
            $data['contentPage'] = $contentPage;
        }
        if ($menuReqd) {
            $data['menu'] = 'mainMenu';
        }
        $source = config_item("base_url").'/scripts/iframeResizer/js/iframeResizer.contentWindow.min.js';
        echo '<script type="text/javascript" src="'.$source.'" ></script>';
        $this->load->view('fullPageTemplate', $data);
    }


    function _loadTemplate($template, $title, $templateData)
    {
        $this->load->library('table');
        $this->load->library('parser');
        $page = $this->parser->parse($template.'.tmpl', $templateData, True);
        $this->_loadPage(NULL, $title, array('prebuiltPage'=>$page));
    }

    // Load a given page into a new (already created) window.
    // Only difference from _loadPage is that we need to set up
    // css for the new page.
    function _loadPageInNewWindow($contentPage, $title, $data = array())
    {
        $data['css'] = 'ctcdbNewWindow.css';
        $this->load->library('table');
        $data['title'] = $title;
        if (!($contentPage === NULL)) {
            $data['contentPage'] = $contentPage;
        }
        $this->load->view('fullPageTemplate', $data);
    }

    function _getNewMemberFormData()
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
    {
        $memberData = $this->_makeFormData($this->Ctcmodel->getTableFields('members'));
        $membershipData = $this->_makeFormData($this->Ctcmodel->getTableFields('memberships'));
        return array_merge($memberData, $membershipData);
    }

    function _getUpdateFormDataFromDb($id)
    // Extract from the database all the information required to display correctly
    // the member update form for a particular member (which includes
    // their current data values). Returns a set of form field definitions
    // as described in _getNewMemberFormData.
    {
        $row = $this->Ctcmodel->getMemberDataByMemberId($id);
        $fields = array();
        foreach (array_keys($row) as $key) {
            $label = $this->_makeLabel($key);
            $type = $this->_formFieldType($key);
            $values = $this->_formFieldValues($key);
            $field = array('type'=>$type, 'label' => $label, 'value' => $row[$key], 'values' => $values);
            $fields[$key] = $field;
        }
        $partner = $this->Ctcmodel->getPartnerName($id);
        if ($partner == '') $partner = "N/A";
        $fields['statusAdminHidden'] = array('type'=>'hidden', 'label'=>'', 'value'=>$row['statusAdmin']);
        $fields['partnerHidden'] = array('type'=>'hidden', 'label' =>'', 'value'=>$partner);
        $fields['memberNameHidden'] = array('type'=>'hidden', 'label' =>'', 'value'=> $row['firstName'].' '.$row['lastName']);
        return $fields;
    }

    function _getNewCoupleFormData()
    // Build  set of form data for a new couple. This is a simple extension to the
    // getNewMemberFormData function (q.v.).
    {
        $member1Data = $this->_makeFormData($this->Ctcmodel->getTableFields('members'),"__1");
        $member2Data = $this->_makeFormData($this->Ctcmodel->getTableFields('members'),"__2");
        $membershipData = $this->_makeFormData($this->Ctcmodel->getMembershipFields());
        return array_merge(array_merge($member1Data, $member2Data), $membershipData);
    }

    function _getFormDataFromPost()
    // Called, when a form_validation check fails, to rebuild all the form-field
    // descriptions from the post request so that the form, when re-displayed,
    // contains all the user-entered data.
    // This function is used for new member, new couple and member update forms.
    // See _getNewMemberFormData for a definition of the returned data.
    {
        $fields = array();
        foreach (array_keys($_POST) as $fieldName) {
            $value = $this->input->post($fieldName, True);
            $type = $this->_formFieldType($fieldName);
            if ($type == 'hidden') {
                $label = '';
            }
            else {
                $label = $this->_makeLabel($fieldName);
            }
            $values = $this->_formFieldValues($fieldName);
            // echo $value." ".$label." ".$type." ".$values."<br />";
            $fields[$fieldName] = array('type'=>$type, 'label'=>$label, 'value'=>$value, 'values'=>$values);
        }
        return $fields;
    }

    function _makeFormData($fieldNames, $suffix = '')
    // Constructs an array of form data (see '_getNewMemberFormData') from an array of DB fields.
    // For use with _getNewCoupleFormData, the suffix parameter is appended to all fieldNames.
    {
        $result = array();
        foreach ($fieldNames as $fieldName) {
            $label = $this->_makeLabel($fieldName);
            $type = $this->_formFieldType($fieldName);
            if ($type == 'admin') continue;
            $value = $type == 'bool' ? 'Yes' : '';
            if ($fieldName == 'city') $value = 'Christchurch'; // HACK!!!!
            $name = $fieldName.$suffix;
            $values = $this->_formFieldValues($fieldName);
            $result[$name] = array('type'=>$type, 'label'=>$label, 'value'=>$value, 'values'=>$values);
        }
        return $result;
    }

    function _formFieldType($fieldName)
    // Extract the type ('admin', 'text', 'textarea', 'enum', 'date', 'bool' or 'hidden') from a field
    // name, using the convention documented above.
    {
        if (preg_match('/Hidden$/', $fieldName))
            $type = 'hidden';
        else if (preg_match('/(Admin)|(id)|(Id)$/', $fieldName))
            $type = 'admin';
        else if (preg_match('/Date$|^date/', $fieldName))
            $type = 'date';
        else if (preg_match('/[nN]otes$/', $fieldName))
            $type = 'textArea';
        else if (preg_match('/Bool$/', $fieldName))
            $type = 'bool';
        else if (preg_match('/Enum$/', $fieldName))
            $type = 'enum';
        else
            $type = 'text';
        return $type;
    }

    function _formFieldValues($fieldName)
    // Return a list of the legitimate values for the given field (which must be
    // an 'enum' type. This is currently a special case hack, since there's currently
    // no way of knowing which table the field belongs to. Currently, it's only
    // expected to be called for the websiteUserTypEnum field -- other enumerated
    // types either don't appear or (e.g. membershipTypeEnum) are also handled with
    // special case code that restricts the options applicable to a given context.
    // TODO: Fix this hack
    {
        if ($fieldName == 'websiteUserTypeEnum') {
            return array('Registered', 'Editor', 'Author', 'Manager',
                'Administrator', 'Super Administrator');
        }
    }

    function _makeLabel($fieldName)
    // Create a label from a fieldName, which may be either the name of a field in the
    // database or the name of a field from a POSTBACK form.
    // Labels are made by assuming a camelcase $field name and inserting spaces before
    // capitals, decapitalising the following word.
    // There are three special cases: the mailFMC label, the postbacks from couple
    // forms, for which the __[12] must be stripped, and fields with a suffix of
    // Bool, which is stripped.
    {
        if ($fieldName == 'mailFMCBool') {
            $label = 'Mail FMC';
        }
        else {
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

?>
