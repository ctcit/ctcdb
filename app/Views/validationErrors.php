Errors!
<?php
    foreach ($errors as $error) {
        echo '<p style="font-size: 18px; color: red">'.esc($error).'</p>';
    }
?>