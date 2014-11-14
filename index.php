<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>PET</title> 
    
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css'>

    <link rel="stylesheet" href="index.css?<?php echo filectime('index.css') ?>" />    

    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>    
    <script src="index.js"></script>

</head>

<body>

<div id="header"><span>Point Extraction Tool</span></div>

<div id="data">

    <div id="data_info">
        <div id="data_type">
            <div>Select Point Data Type:</div>
            <label for="data_vector" class="input"><input id="data_vector" type="radio" name="data_type" value="vector" checked>Vector</label>
            <label for="data_raw" class="input"><input id="data_raw" type="radio" name="data_type" value="raw">Raw</label>
        </div>

        <div id="data_naming">
            <div>Coordinate Naming in Raw Data:</div>
            <label class="input">x: <input type="text" id="data_naming_x" value="x"></label>
            <label class="input">y: <input type="text" id="data_naming_y" value="y"></label>
        </div>

        <div id="data_upload">
            <div>Upload Your Data:</div>
            <input type="file" id="data_file"  class="input required" value="" multiple>
        </div>
    </div>

    <div id="data_warning"> 
        <p id="data_warning_title">Warning:</p>
        <p id="data_warning_content">Users are responsible for the quality of the data they are uploading.</p>
    </div>

</div>

<div id="raster">
    <div id="raster_options">
        <div class="input_name">Select a Raster:</div>
        <select id="raster_list" class="required"></select>
    </div>
    <div id="raster_meta">
        <table>
            <thead><tr><th colspan="2">Raster Meta Data</th></thead>
            <tbody id="raster_table_body"></tbody>
        </table>
    </div>
</div>

<div id="request">
    Email: <input type="text" id="request_email" class="required" value="">
    <br>
    <br>
    <button id="request_submit"  type="button" disabled="true">Submit Request</button> 
</div>

<div id="confirmation">
    
</div>

</body>

</html>