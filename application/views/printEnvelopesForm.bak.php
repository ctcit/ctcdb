<?php  // The form for setting the parameters for a printEnvelopes query

if (!defined('BASEPATH')) exit('No direct script access allowed'); 


function sortBox($name, $sortKeys, $default) {
    echo "<select name=\"$name\">\n";
    foreach ($sortKeys as $opt) {
        $select = ($opt == $default ? 'selected' : '');
        echo "<option value=\"$opt\" $select>$opt</option>\n";
    }
    echo "</select>\n";
}

$boxes = array(
    'nl'    => array('CTC Newsletter','checked'),
    'fmcb'  => array('FMC Bulletin',''),
    'fmcc'  => array('FMC Card',''),
    'cookie' => array('Cookie (something sent to <em>all</em> members, e.g. membership card)','')
);

$sortKeys = array('-', 'items', 'mailName', 'nameBySurname', 'membershipType', 'unpaid');

?>
<script type="text/javascript">
function invokeInNewWindow()
{
    document.PrintEnvelopesForm.action = "<?php echo site_url("queries/printEnvelopes2"); ?>";
    document.PrintEnvelopesForm.target = "_blank";
    document.PrintEnvelopesForm.submit();
}
</script>

<h1>Envelope Printing</h1>
<form name="PrintEnvelopesForm" class="PrintEnvelopesForm" action="" method="post">

<?php 
if ($error) {
    echo "<p class=\"errorLeft\">$error</p>\n";
}
?>

<p>Tick the boxes of items to be included in <em>any</em> of the envelopes.</p>
<p>
<?php 
foreach($boxes as $key=>$value) {
    list($displayValue, $checked) = $value;
?>
<?php echo "<input type=\"checkbox\" name=\"$key\" $checked /> $displayValue"; ?>
<br />
<?php }?>
</p>
<p>
Sort envelopes first by <?php sortBox('sort1', $sortKeys, 'items'); ?>
then by <?php sortBox('sort2', $sortKeys, 'mailName'); ?> and then by <?php sortBox('sort3', $sortKeys, '-'); ?>
</p>
<p>
<input type="checkbox" name="showUnpaid" />Show "unpaid" status on envelope
</p>
<p>
<input type="checkbox" name="showQuery" />Show query (rather than its result)
</p>
<p>
<input type="button" value="Submit" onclick="invokeInNewWindow()" />
</p>
</form>
