<?php

// ++ 
// ++ include file for getQueue.php used to generate documentation
// ++ 

//init markdown lib
require_once $COM_DIR . '/libs/Michelf/MarkdownExtra.inc.php';
use \Michelf\MarkdownExtra;
$parser = new MarkdownExtra();

//html input as raw text
$doc_string = "";
$doc_string .= "<style>
					table {
						border-collapse:collapse;
						cell-spacing: 0px;
						cell-padding: 0px;
						width:100%;
						margin-bottom:50px;
					}

					.rh {
						width:25%;
					}

					td,th {
						border:solid 1px black;
						padding:5px;

					}

					h1 {
						text-align:center;
					}

					#logo{
						text-align:center;
					}
				</style>";

$doc_string .= "<div id='logo'><img src='".dirname($COM_DIR)."/DET/www/img/logo.jpg'/></div>\n";				
$doc_string .= "<h1>DOCUMENTATION</h1> \n";

// 1.0 General
$doc_string .= "##1.0 General## \n"; 
$doc_string .= "<table><thead><tr><th colspan='2'>1.1 AidData Information</th></tr></thead><tbody>";
$doc_string .= "<tr><td>Facility:</td><td>AidData Data Center</td></tr>";
$doc_string .= "<tr><td>Email:</td><td>data@aiddata.org</td></tr>";
$doc_string .= "<tr><td>Phone:</td><td>757-221-5396</td></tr>";
$doc_string .= "<tr><td>Location:</td><td>427 Scotland Street<br>Williamsburg, VA 23185</td></tr>";
$doc_string .= "</tbody></table>";

$doc_string .= "<table><thead><tr><th colspan='2'>1.2 Terms and Conditions of Use</th></tr></thead><tbody>";
$doc_string .= "<tr><td colspan='2'>";
$doc_string .= "By using this site or downloading data from AidData, users agree to the following:<br>";

$doc_string .= "<ul>";
$doc_string .= "<li>To use and/or download the data only for private or personal, non-commercial purposes;\n";
$doc_string .= "<li>To cite the source of the data; and\n";
$doc_string .= "<li>To accept disclaimers and restrictions of rights and liability concerning the data.\n";
$doc_string .= "</ul>";

$doc_string .= "<br>Please refer to the <a href='http://aiddata.org/user-guide'>AidData User Guide</a> for additional guidance on usage rights and obligations.";

$doc_string .= "</td></tr>";
$doc_string .= "</tbody></table>";

// 2.0 Study Description
$doc_string .= "##2.0 Study Description## \n";
$doc_string .= "<table><tbody>";
$doc_string .= "<tr><td class='rh'>Overiew:</td><td>This data provided is based on the point data provided by the user.</td></tr>";

$doc_string .= "</tbody></table>";

// 3.0 Files
$doc_string .= "##3.0 Files## \n";
$doc_string .= "Geospatial files (rasters) can be opened in an open source GIS program such as QGIS (<a href='http://www.qgis.org'>http://www.qgis.org</a>) \n";
$doc_string .= "<table><tbody>";
$doc_string .= "<tr><td class='rh'>Main Zip File (zip):</td><td>Zip file containing all the files listed below</td></tr>";
$doc_string .= "<tr><td class='rh'>Complete Results (csv):</td><td>CSV which can be loaded into any spreadsheet program (i.e., the open source LibreOffice - <a href='http://www.libreoffice.org'>http://www.libreoffice.org</a>)</td></tr>";

$doc_string .= "<tr><td class='rh'>Documentation (pdf):</td><td>This file</td></tr>";
$doc_string .= "<tr><td class='rh'>Request Details (json):</td><td>JSON file containing details of the user's request</td></tr>";
	
	for ($i=0; $i<count($q_data["raster"]); $i++) {
		$doc_string .= "<tr><td class='rh'>Individual Results (csv) - ". $q_data["request"]."_".$q_data["raster"][$i] .".csv:</td><td>Result file for ".$q_data["raster"][$i]."</td></tr>";
		$meta_summary = json_decode(file_get_contents(dirname($COM_DIR)."/DET/uploads/globals/processed/".$q_data["raster"][$i]."/meta_info.json"), true)["meta_summary"];
		$doc_string .= "<tr><td class='rh'>Raster ".($i+1)." - ".$raster_files[$i].":</td><td>".$meta_summary;
		if ( $rterms[$i] != "true"){
			$doc_string .= "<br> (File not included due to license terms associated with the file.)";
		}
		$doc_string .= "</td></tr>";
	}

$doc_string .= "</tbody></table>";


// 4.0 Data Selection
$doc_string .= "##4.0 Data Selection## \n";
for ($i=0; $i<count($q_data["raster"]); $i++) {
	$doc_meta = json_decode(file_get_contents(dirname($COM_DIR)."/DET/uploads/globals/processed/".$q_data["raster"][$i]."/meta_info.json"), true);

	$doc_string .= "###4.".($i+1)." Variable: *".$q_data["raster"][$i]."* ### \n";
	$doc_string .= "<table><thead><tr><th class='rh'> Raster: </th><th>". $raster_files[$i] ."</th></tr></thead><tbody>";
	foreach ($doc_meta as $key => $value) {	
		$key = str_replace("raster_", "", $key);
		$key = str_replace("meta_", "", $key);
		$key = str_replace("_", " ", $key);
		if ($value == ""){$value = "N/A";}
		if ($key == "created" || $key == "modified"){
			$value =  gmdate("M d Y H:i:s", $value) .' GMT';
		}
		$doc_string .= "<tr><td class='rh'> ". $key ." </td><td> ". $value. "</td></tr>";
	}
	$doc_string .= "</tbody></table>";
}


//OUTPUT

//save text (for reference)
// file_put_contents($COM_DIR . "/www/documentation.txt", $doc_string);

//parse raw text and output to html
$doc_html = $parser->transform($doc_string);
file_put_contents($COM_DIR ."/queue/available/". $q_data["request"] ."/documentation.html", $doc_html);

//convert html to pdf
include $COM_DIR . '/libs/mpdf/mpdf.php';
$mpdf = new mPDF();
$mpdf->WriteHTML($doc_html);
$mpdf->Output($COM_DIR ."/queue/available/". $q_data["request"] ."/documentation.pdf", "F");

?>