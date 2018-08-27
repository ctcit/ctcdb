<?php  if (!defined('BASEPATH')) exit('No direct script access allowed'); ?>  
<head>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>

<h1>Forgotten user name</h1>

<?php
  $posturl = config_item("base_url")."/index.php/open/forgottenUserNameSubmit";
  echo '<form action="'.$posturl.'" method="post">';
?>
<p>Please enter the email address or phone number associated with your user account.</p>
<p>The phone number could be either mobile or home phone.</p>
<p>For this to work, whatever you enter must identify you uniquely within the club.</p>
<p>Your login name will be emailed to the address on file.</p>
<div class="control-label" style="white-space:nowrap">
    <label class="required"  title="Email address or phone number" >
    Email or phone number </label>
    <input type="text" value="" name = "search_data" size="60" required aria-required="true" autofocus />							                
</div>
<br/>
<div class="recaptcha-error-message">Verify you are a real person
</div>
<input type="hidden" class = "captcha-validated" name="captcha-validated" value="false" />
<br/><br/>
<div class="recaptcha-error">
    <div id="forgotten-user-name" class="g-recaptcha" data-sitekey="6LeZ6kQUAAAAAHqD1WE97lF6gkAXO0hFhKhjU4L0" data-callback="onSuccess">
    </div>
    <script nonce="X3rBaxv3oHGHAB8zZEkGpBLUnNM">
        var onSuccess = function(response) {
          var errorDivs = document.getElementsByClassName("recaptcha-error");
          if (errorDivs.length) {
            errorDivs[0].className = "";
          }
          var captchaValidated = document.getElementsByClassName("captcha-validated");
          if (captchaValidated.length)
              captchaValidated[0].value = "true";
          var errorMsgs = document.getElementsByClassName("recaptcha-error-message");
          if (errorMsgs.length) {
            errorMsgs[0].parentNode.removeChild(errorMsgs[0]);
          }
        };
    </script>
    <br/>
</div>
<div><input type="submit" value="Submit" default/></div>
</form>
