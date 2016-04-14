<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

	function loadPage($controller, $contentPage, $title, $data = array())
	// Builds a full page for a given content page (which can also
	// be an array of pages).
	{
		$controller->load->library('table');
		$data['title'] = $title;
		if (!($contentPage === NULL)) {
			$data['contentPage'] = $contentPage;
		}
		$data['menu'] = 'mainMenu';
		$controller->load->view('fullPageTemplate', $data);
	}
?>