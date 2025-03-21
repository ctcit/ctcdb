 <script src="https://www.google.com/recaptcha/api.js"></script>
 <script>
   function onSubmit(token) {
     document.getElementById("prospective-member-form").submit();
   }
 </script>

<?php echo form_open($postbackUrl, ['id' => 'prospective-member-form']); ?>
<h2>Prospective Member Form</h2>
<p>Most of the information you will need to know about the club can be found on the <a href="index.php/about/about-ctc" rel="alternate">About the CTC</a> page of this website.</p>
<p>Once you have read that, assuming you still want to join the club, please fill in the form below.</p>
<table style="width: 75%;">
<tbody>
<tr>
<td style="width: 48.4523%;">Name(s)</td>
<td style="width: 51.4849%;"><input name="_name" size="40" type="text" /></td>
</tr>
<tr>
<td style="width: 48.4523%;">Email address</td>
<td style="width: 51.4849%;"><input name="email" size="40" type="text" /></td>
</tr>
<tr>
<td style="width: 48.4523%;">Retype your email address here</td>
<td style="width: 51.4849%;"><input name="email2" size="40" type="text" /></td>
</tr>
<tr>
<td style="width: 48.4523%;">Phone</td>
<td style="width: 51.4849%;"><input name="phone" size="12" type="text" /></td>
</tr>
<tr>
<td style="width: 48.4523%;">Cell phone</td>
<td style="width: 51.4849%;"><input name="mobile" size="12" type="text" /></td>
</tr>
<tr>
<td style="width: 48.4523%;">How did you hear about CTC?</td>
<td style="width: 51.4849%;"><input name="howdidyouhear" size="40" type="text" /></td>
</tr>
<tr>
<td style="width: 48.4523%;">Address</td>
<td style="width: 51.4849%;"><textarea cols="30" name="address" rows="3"></textarea></td>
</tr>
<tr>
<td style="width: 48.4523%;">Post code</td>
<td style="width: 51.4849%;"><input name="postcode" size="6" type="text" /></td>
</tr>
</tbody>
</table>
<p>You can enter additional information in the "Notes" field below to:</p>
<ul>
<li>Ask a specific question about the club.</li>
<li>Ask a specific question about a particular tramp, e.g., can you put me in touch with the leader?</li>
<li>Tell us a bit about yourself : e.g. your previous tramping , your reasons for wanting to join the club, trips you aspire to when you are a member.</li>
<li>Anything else ...</li>
</ul>
<p>Notes:<textarea cols="50" name="notes" rows="5"></textarea></p>
<input class="captcha-validated" name="captcha-validated" type="hidden" value="false" />
<div class="recaptcha-error">
<!--<div id="prospective-member" class="g-recaptcha" data-sitekey="6Ld8F6YoAAAAAOKsw2dOvJfY6rDBzSfgDArOCHSy" data-callback="onSuccess"></div>
</div>-->
<!--<div class="pt-3"><input class="btn ctc-button" type="submit" value="Submit" default/></div>-->
<button class="g-recaptcha btn ctc-button" 
        data-sitekey="6Ld8F6YoAAAAAOKsw2dOvJfY6rDBzSfgDArOCHSy" 
        data-callback='onSubmit' 
        data-action='submit'>Submit</button>
</form>