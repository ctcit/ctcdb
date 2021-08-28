<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Access extends BaseConfig
{
	/**
	*--------------------------------------------------------------------------
	* Full Access Roles
	*--------------------------------------------------------------------------
	*
	* As a general rule only club members can access the database. Authentication
	* is via the Joomla website -- users must login to that before accessing the
	* ctcdb package. the database. Their access rights are determined by their
	* roles within the DB tables.
	*
	* @var string
	*/
	public $fullAccessRoles = array('webmaster', 'dbadmin', 'secretary',
	                                'treasurer', 'editor', 'new members rep');

}
