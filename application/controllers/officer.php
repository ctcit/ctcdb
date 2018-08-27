<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

// This controller contains functions available to club officers logged into the
// joomla website.

class Officer extends MY_Controller {

	public function __construct()
	{
		global $userData;
		if (!isset($userData['roles']) || count($userData['roles']) == 0) {
			die('You must be a club officer logged in to the website to use this function');
		}
		parent::__construct();
		$this->load->database();
		$this->load->helper(array('url','form','date','pageload'));
		$this->load->model('Ctcmodel');
		$this->load->library('form_validation');
		$this->form_validation->set_error_delimiters('<p style="font-size: 18px; color: red">', '</p>');
	}

	public function index()
	{
		$this->_loadPage('home','CTC Database Home');
	}

}

?>
