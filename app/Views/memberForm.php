<?php
    helper('ctcforms');
    if (isset($validation)) {
        echo $validation->listErrors('ctcList');
    }
    echo form_open($postbackUrl);
    echo '<table class="memberDataForm">';
    displayFieldsInTable($fields);
    echo "</table>";
?>
    <input type="submit" value="Submit" class="submit" />
    <a class="back_button" href='javascript:history.go(-1)'>[ Back ]</a>

<?php echo form_close() ?>
