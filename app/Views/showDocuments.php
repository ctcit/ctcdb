
<script>
function confirmDialog(name) {
    return confirm("Are you sure you want to delete "+name+" ?")
}
</script>

<h1>Document Management</h1>
<?php
    if ($message !== "") {
        echo("<p>$message</p>");
    }
?>

<h2>Upload New</h2>

<?php
helper('form');
echo form_open_multipart("document/upload");
echo form_input(["name" => "document", "type" => "file"]);
echo form_submit("submit", "Upload");
echo form_close();
?>

<h2>Existing Documents</h2>

<p>
<?php
    $rows[] = ["Name", "Size", "Uploaded Date", ""];
    foreach($documents as $document) {
        $onClickCall = "delete(".$document->id.",'".$document->name."')";
        $rows[] = [
            anchor( site_url("/document/download/".$document->id), $document->name ),
            $document->size,
            $document->uploaded,
            anchor( 'document/delete/'.$document->id, 'Delete', 
                    ['onclick'=>"return confirmDialog('".$document->name."');"])
        ];
    }

    $numCols = count($rows[0]);
    $tableOpenString = '<table id="documentSelect" class="oddEven">';
    for ($i=1; $i<=$numCols; $i++) {
        $tableOpenString .= "<col class=\"col$i\" />";
    }
    $template = [
        'table_open' => $tableOpenString,
        'row_start' => '<tr class="odd">'
    ];

    $table = new \CodeIgniter\View\Table();
    $table->setTemplate($template);
    echo $table->generate($rows);
?>
</p>
