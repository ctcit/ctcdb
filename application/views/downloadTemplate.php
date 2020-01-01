<?php  if (!defined('BASEPATH')) exit('No direct script access allowed'); ?>
<?php
    if (isset($prebuiltPage)) {
        echo $prebuiltPage;
    }
    else if (is_array($contentPage))
        foreach ($contentPage as $page) {
            $this->load->view($page);
        }
    else {
        $this->load->view($contentPage);
    }
