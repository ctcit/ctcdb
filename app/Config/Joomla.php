<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Joomla extends BaseConfig
{
	/**
	*--------------------------------------------------------------------------
	* Joomla Base Directory
	*--------------------------------------------------------------------------
	*
	* This app is configured to run in conjunction with Joomla (for authenticating
	* users) so the Joomla site base directory is also required. [Added by RJL.]
	*
	* @var string
	*/
	public $joomlaBase = "/var/www/html";

	/**
	*--------------------------------------------------------------------------
	* Joomla Base URL
	*--------------------------------------------------------------------------
	*
	* The Joomla base URL
	* @var string
	*/
	public $baseURL = "http://localhost";
}
