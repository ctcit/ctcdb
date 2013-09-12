<?php  if (!defined('BASEPATH')) exit('No direct script access allowed'); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
<META HTTP-EQUIV="Expires" CONTENT="-1">
<?php
	$this->load->helper('html');
	$this->load->helper('url');
	
	
	if (isset($css)) {
		$cssFile = "css/$css"; 
	}
	else {
		$cssFile = "css/ctcdbstyle.css"; 
	}
	
	// Hack to force CSS file reload whenever it's updated. Trick is
	// to append a string like "?version=12345667", where the digit string
	// at the end is the file's last modification time, to the end of the css
	// path so browser's caching is defeated whenever the modification time
	// changes.

	$cssFileModTime = filemtime($cssFile);
	$cssFile .= "?version=$cssFileModTime";

	echo link_tag($cssFile);
?>

<title>
<?php
	if (isset($title))
		echo $title;
	else
		echo "CTC Database Page";
?>
</title>

<script type="text/javascript" src="<?php echo base_url();?>scripts/selectRows.js" ></script>

<script type="text/javascript" src="<?php echo base_url();?>scripts/ts_picker.js" ></script>

</head>
<body>
<div id="wrap">
	<?php
		if (isset($menu)) {
			echo "<div class='menu'>";	
			$this->load->view($menu);
			echo "</div>";
		}
	?>
	
	<div class="main">
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
	?>
	</div>
</div>
<?php
if (isset($menu)) {  // If it's a full-on database page
	echo '<div class="footer">Built with <a href="http://codeigniter.com">Code Igniter</a>';
	echo '</div>';
}
?>
</body>
</html>
