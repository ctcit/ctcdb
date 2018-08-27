<?php
    function iframeresizer(){
        if (!isset($this->rest)){
            $source = config_item("base_url").'/scripts/iframeResizer.contentWindow.min.js';
            echo '<script type="text/javascript" src="'.$source.'" ></script>';
        }
    }

?>
