<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
<META HTTP-EQUIV="Expires" CONTENT="-1">
<?php
    helper('html');
    helper('url');

    if (isset($css)) {
        $cssFile = "css/$css";
        // If we've specified custom css, this is generally pages embedded in the main site,
        // so use the CTC "Common" style as well as the specifed css
        // TODO - Adapt the ctcdbstyle to work well with this common style
        echo link_tag( "https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" );
        echo link_tag( base_url()."/../templates/ctctemplate/css/common.css" );
    } else {
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
    echo link_tag("https://fonts.googleapis.com/css2?family=Lato:wght@100;400;700;900&family=Open+Sans:wght@300;400;500;600;700;800&display=swap");
?>

<title>
<?php
    if (isset($title)) {
        echo $title;
    } else {
        echo "CTC Database Page";
    }
?>
</title>

<?php
    echo script_tag( base_url()."/scripts/selectRows.js" );
    echo script_tag( base_url()."/scripts/ts_picker.js" );
    echo script_tag( base_url()."/scripts/iframeResizer/js/iframeResizer.contentWindow.js" );
?>

</head>
<body>
<div id="wrap">
    <?php
        if (isset($menu)) {
            $menu_data = array(
                'joomlaBaseURL' => $joomlaBaseURL,
            );
            echo "<div class='menu'>";
            echo view($menu, $menu_data);
            echo "</div>";
        }
    ?>

    <div class="main">
    <?php
        if (isset($prebuiltPage)) {
            echo $prebuiltPage;
        } else if (is_array($contentPage)) {
            foreach ($contentPage as $page) {
                echo view($page);
            }
        } else {
            echo view($contentPage);
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
