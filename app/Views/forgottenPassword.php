<head>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>

<div class="container">
  <div class="row">
    <div class="col">
      <h1>Forgotten Password</h1>
      <?php echo form_open($postbackUrl); ?>
      <p>Please enter your login name, or the email address or phone number associated with your CTC membership.
      The phone number could be either mobile or home phone.
      For this to work, whatever you enter must identify you uniquely within the club.</p>
      <p>A new password will be emailed to the address on file.</p>
      <div class="form-group">
          <label class="required"  title="Login name, email address or phone number" >
          Login, Email or Phone Number</label>
          <input type="text" value="" name="search_data" class="form-control" required aria-required="true" autofocus />
      </div>
      <input type="hidden" class = "captcha-validated" name="captcha-validated" value="false" />
      <div class="recaptcha-error">
          <div id="forgotten-password" class="g-recaptcha" data-sitekey="6LeZ6kQUAAAAAHqD1WE97lF6gkAXO0hFhKhjU4L0" data-callback="onSuccess">
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
      </div>
      <div class="pt-3"><input class="btn ctc-button" type="submit" value="Submit" default/></div>
      <?php echo form_close(); ?>
    </div>
  </div>
</div>