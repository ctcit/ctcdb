<h2>Personal Details</h2>
<p>Shown below are your personal details from the current club database.
If any of the details are wrong, please correct them and click the <b><i>Submit</i></b>
button at the bottom.
</p><p>Note that if you're in a couple membership and change your
address or home phone, your partner's details will be automatically updated as well.</p>
<?php
    if (isset($validation)) {
        echo $validation->listErrors('ctcList');
    }
    helper('ctcforms');
    echo form_open($postbackUrl);
    echo '<table class="memberDataForm">';
    displayFieldsInTable($fields);
    echo "</table>";
?>
    <input type="submit" value="Submit" class="submit" />
<?php
    global $CI;
    $ctcHome = config('Joomla')->baseURL;
    $base = trim(base_url(),'/');
    echo "&nbsp;&nbsp;<button type=\"button\"
          onclick=\"if(confirm('Return to CTC home page, without changing your details?')) top.location.href='$ctcHome'\">
          Cancel</button>";

    echo form_close();
?>
