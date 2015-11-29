<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Extending the default errors to always give JSON errors if this is
 * a REST request, otherwise behave like the parent.
 * A tweaked version of Oliver Smith's code from 
 * http://oliversmith.io/technology/2012/04/09/json-error-exception-messages-in-codeigniter-2-0/
 * 
 * @author Oliver Smith, Richard Lobb
 */

class MY_Exceptions extends CI_Exceptions
{
    function __construct()
    {
        parent::__construct();
    }
    
    
    /* Return true if this is a request to the rest controller */
    private function is_rest()
    {
        $ci =& get_instance();
        return  isset($ci->uri) && $ci->uri->segment(1,'') === 'rest';
    }
        

    /**
    * 404 Page Not Found Handler
    *
    * @param   string  the page
    * @param   bool    log error yes/no
    * @return  string
    */
    function show_404($page = '', $log_error = TRUE)
    {
        if (!$this->is_rest()) {
            return parent::show_404($page, $log_error);
        } else {
            // By default we log this, but allow a dev to skip it
            if ($log_error)
            {
                log_message('error', '404 Page Not Found --> '.$page);
            }

            header('Cache-Control: no-cache, must-revalidate');
            header('Content-type: application/json');
            header('HTTP/1.1 404 Not Found');

            echo json_encode(
                array(
                    'status' => FALSE,
                    'error'  => 'Unknown method or object',
                )
            );

            exit;
        }
    }

    /**
    * General Error Page
    *
    * This function takes an error message as input
    * (either as a string or an array) and displays
    * it using the specified template.
    *
    * @access  private
    * @param   string  the heading
    * @param   string  the message
    * @param   string  the template name
    * @param   int     the status code
    * @return  string
    */
    function show_error($heading, $message, $template = 'error_general', $status_code = 500)
    {
        if (!$this->is_rest()) {
            return $firstBit . parent::show_error($heading, $message, $template, $status_code);
        } else {
            header('Cache-Control: no-cache, must-revalidate');
            header('Content-type: application/json');
            header('HTTP/1.1 500 Internal Server Error');

            echo json_encode(
                array(
                    'status' => FALSE,
                    'error' => 'Internal Server Error',
                )
            );

            exit;
        }
    }

    /**
    * Native PHP error handler
    *
    * @access  private
    * @param   string  the error severity
    * @param   string  the error string
    * @param   string  the error filepath
    * @param   string  the error line number
    * @return  string
    */
    function show_php_error($severity, $message, $filepath, $line)
    {
        if (!$this->is_rest()) {
            return parent::show_php_error($severity, $message, $filepath, $line);
        } else {
            header('Cache-Control: no-cache, must-revalidate');
            header('Content-type: application/json');
            header('HTTP/1.1 500 Internal Server Error');

            echo json_encode(
                array(
                    'status' => FALSE,
                    'error' => 'Internal Server Error',
                )
            );

            exit;
        }
    }
}