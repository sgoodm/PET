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

<div id="main">

    <div id="data">

        <div id="data_info">
            <div id="data_type">
                <div>Select Point Data Type:</div>
                <label for="data_vector" class="input"><input id="data_vector" type="radio" name="data_type" value="vector" checked>Vector (OGR Format - Shapefile, GeoJSON, etc.)</label>
                <label for="data_raw" class="input"><input id="data_raw" type="radio" name="data_type" value="raw">Raw (CSV File Only)</label>
            </div>

            <div id="data_identifier">
                <div>Feature ID Field Name (optional -  defaults to 0,1,2,.. if no field exists)</div>
                <label class="input"><input type="text" id="data_id" value="project_ID"></label>               
            </div>

            <div id="data_include">
                <div>Name of Feature to Include with Results (comma separated for multiple fields)</div>
                <label class="input"><input type="text" id="data_inc" value=""></label>               
            </div>

            <div id="data_naming">
                <div>Coordinate Naming in Raw Data:</div>
                <label class="input">longitude: <input type="text" id="data_naming_longitude" value="longitude"></label>
                <label class="input">latitude: <input type="text" id="data_naming_latitude" value="latitude"></label>
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
            <div class="input_name">Select a Raster:</div><br>
            <select id="raster_list" class="required" size="10" multiple></select>
        </div>
        <div id="raster_meta">
            <table>
                <thead><tr><th colspan="2">Raster Meta Data</th></tr></thead>
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

</div>

<div id="confirmation">
        
    <div id="confirmation_text"></div>
    <br>
    <button id="confirmation_return" type="button">Return</button>

</div>

</body>

</html>