<?php

// ++ 
// ++ handles queue processing when triggered by Cron jobs
// ++ 

//associate cron job 
// * * * * * flock -n /var/www/html/aiddata/PET/getQueue.php /usr/bin/php5 /var/www/html/aiddata/PET/getQueue.php

//sets maximum execution time (prevent time out when executing R script)
set_time_limit(0);


$COM_DIR = __DIR__; //local path to PET dir

$DOMAIN = "128.239.119.254";
$app = basename($COM_DIR);
$MAIL_DIR = $DOMAIN . substr($COM_DIR, 13, strpos($COM_DIR, $app)-1); //http to PET dir


//load queue log and prepare contents
$file = $COM_DIR  . "/queue/pending.csv"; 
$csv = file_get_contents($file);
$rows = array_map("str_getcsv", explode("\n", $csv));
$header = array_shift($rows);
$end = count($rows)-1;
if ($rows[$end][0] == NULL){
	array_pop($rows);
}
$r_queue = array();
foreach ($rows as $row) {
	$r_queue[] = $row[0];
}

//check if there is a request in queue
if (count($r_queue) > 0){

	//determine next request to handle
	$next_request = min($r_queue);

	//get request info for selected request
	$q_file = $COM_DIR ."/queue/pending/". $next_request .".json"; 
	$q_raw = file_get_contents($q_file);
	$q_data = json_decode($q_raw, true);

	//create directory and file for request output
	$outAvailable = $COM_DIR ."/queue/available/". $q_data["request"] ."/". $q_data["request"] . ".csv"; 
	$outDir = dirname($outAvailable);
	if (!is_dir($outDir)){
		$old_mask = umask(0);
		mkdir($outDir,0775,true);
	}

	//get point data file
	$file_points = $COM_DIR ."/uploads/". $q_data["request"] ."/". $q_data["data"];

	$raster_paths = array();
	$raster_files = array();
	//go through all selected rasters
	for ($i=0; $i<count($q_data["raster"]); $i++) {

		//get raster file

		//scan raster upload folder for file that is not .json or .csv
		$rscan = scandir( dirname($COM_DIR) ."/DET/uploads/globals/processed/". $q_data["raster"][$i] );
		$scan = array_diff($rscan, array('.', '..'));
		for ($j=0+2;$j<count($scan)+2;$j++){
			if ( strpos($scan[$j],".csv") == false && strpos($scan[$j],".json") == false ){
					$file_raster = dirname($COM_DIR) ."/DET/uploads/globals/processed/". $q_data["raster"][$i] ."/". $scan[$j];	
					$raster_paths[] = $file_raster;
					$raster_files[] = $scan[$j]; //substr($file_raster, strpos($file_raster, $q_data["raster"][$i]) + strlen($q_data["raster"][$i]) + 1);

			}	
		}

		//individual output file
		$file_output = $COM_DIR ."/queue/available/". $q_data["request"] ."/". $q_data["request"] ."_". $q_data["raster"][$i] .".csv";

		//execute extract script
		// $py_Vars = $dataType ." ". $point_file ." ". $longitude ." ". $latitude ." ". $raster_file ." ". $output_file; 
		$py_vars = $q_data["dataType"] ." ". $file_points ." ". $q_data["id"] ." ". $q_data["longitude"] ." ". $q_data["latitude"] ." ". $file_raster ." ". $file_output ." ". $q_data["raster"][$i] ." ". $q_data["include"];
		// var_dump($py_vars);
		
		$start_time = time();

		exec("python ".$COM_DIR."/extract.py $py_vars"); 

		$end_time = time();
		$run_time = $end_time - $start_time;
		$timeHandle = fopen($COM_DIR ."/queue/run_times.csv", "a"); 
		// $timeData = array($file_cache, $run_time);
		$timeData = array($q_data["request"], $run_time);
		fputcsv($timeHandle, $timeData);
	}


	//--------------------------------------------------

	//combine individual output files into one file (individuals still included with results)
	
	if ( count($q_data["raster"]) > 1 ){
		//open files
		$outHandle = fopen($outAvailable, "w");

		$handles = array();
		for ($i=0; $i<count($q_data["raster"]); $i++) {		
			$handles[$i] = fopen( $COM_DIR ."/queue/available/". $q_data["request"] ."/". $q_data["request"] ."_". $q_data["raster"][$i] .".csv", "r" ); 
		}

		//join cache data and put in output file
		while ($fRow = fgetcsv($handles[0])){
			$outRow = $fRow;
			for ($i=1; $i<count($handles); $i++){
				$outRow[] = fgetcsv($handles[$i])[count($fRow)-1];
			}
			// fputcsv($outHandle, $outRow, ",", "");
			fwrite($outHandle, implode($outRow, ",")."\n");
		}

		//close files
		for ($i=0; $i<count($q_data["raster"]); $i++) {		
			fclose( $handles[$i] );
		}

		//close main file
		fclose($outHandle);
	} else {
		$singleFile = $COM_DIR ."/queue/available/". $q_data["request"] ."/". $q_data["request"] ."_". $q_data["raster"][0] .".csv";
		$resultFile = $COM_DIR ."/queue/available/". $q_data["request"] ."/". $q_data["request"] .".csv";
		copy($singleFile, $resultFile);
	}
	

	//--------------------------------------------------

	//add request to available list
	$avail_handle = fopen($COM_DIR ."/queue/available.csv", "a"); 
	$time = time();
	$duration = (60*60*24*3); // 3 days
	$avail_data = array($q_data["request"], $q_data["submission"], $time, $time+$duration, $q_data["email"]);
	fputcsv($avail_handle, $avail_data);
	fclose($avail_handle);

	//move pending request file to available folder
	$move_file = $COM_DIR ."/queue/pending/". $q_data["request"] . ".json"; 
	$move_data = file_get_contents($COM_DIR ."/queue/pending/". $q_data["request"] . ".json"); 
	file_put_contents($COM_DIR ."/queue/available/". $q_data["request"] ."/". $q_data["request"] . ".json", $move_data); 
	unlink($move_file);

	//remove request from pending list
	$pending_handle = fopen($COM_DIR . "/queue/pending.csv", "r"); 
	$temp_pending_handle = fopen($COM_DIR . "/queue/pending_temp.csv", "w"); 
	while ($pRow = fgetcsv($pending_handle)){
		if ($pRow[0] != $q_data["request"]){
			fputcsv($temp_pending_handle, $pRow);
		}	
	}
	fclose($pending_handle);
	fclose($temp_pending_handle);
	$temp_pending_contents = file_get_contents($COM_DIR . "/queue/pending_temp.csv"); 
	file_put_contents($COM_DIR . "/queue/pending.csv", $temp_pending_contents); 
	unlink($COM_DIR . "/queue/pending_temp.csv"); 

	//update request in log list with completion time
	$log_handle = fopen($COM_DIR . "/queue/log.csv", "r"); 
	$temp_log_handle = fopen($COM_DIR . "/queue/log_temp.csv", "w"); 
	while ($logRow = fgetcsv($log_handle)){
		if ($logRow[0] == $q_data["request"]){
			$logRow[3] = $time;
			$logRow[4] = $time+$duration;
			fputcsv($temp_log_handle, $logRow);
		} else {
			fputcsv($temp_log_handle, $logRow);
		}
	}
	fclose($log_handle);
	fclose($temp_log_handle);
	$temp_log_contents = file_get_contents($COM_DIR . "/queue/log_temp.csv"); 
	file_put_contents($COM_DIR . "/queue/log.csv", $temp_log_contents); 
	unlink($COM_DIR . "/queue/log_temp.csv"); 

	//--------------------------------------------------

	//create zip file for results

	$zipBase = $COM_DIR ."/queue/available/". $q_data["request"] ."/"; 
	
	$zipAll = new ZipArchive();
	$zipAll->open($zipBase. $q_data["request"] . ".zip", ZipArchive::CREATE);

	//result
	$zipAll->addFile($zipBase . $q_data["request"] . ".csv", $q_data["request"] . ".csv");



	//raw and individual results
	$rterms = array();
	foreach ($q_data["raster"] as $r => $raster){
		$rterms[$r] = "false";
		$rterms_meta = json_decode(file_get_contents( dirname($COM_DIR) ."/DET/uploads/globals/processed/". $raster ."/meta_info.json"), true);
		$rterms[$r] = $rterms_meta["meta_license_terms"];
		
		$zipAll->addFile( $zipBase . $q_data["request"] ."_". $q_data["raster"][$r] .".csv", $q_data["request"] ."_". $q_data["raster"][$r] .".csv" );
		
		if ($rterms[$r] == "true"){
			$zipAll->addFile( $raster_paths[$r], $raster_files[$r] ); 
		}
	}
	
		
	//request
	$zipAll->addFile($zipBase . $q_data["request"] . ".json", $q_data["request"] . ".json");

	//generate documentation
	include 'parse.php';
	//add documentation to zip
	$zipAll->addFile($zipBase . "documentation.pdf", "documentation.pdf");

	$zipAll->close();

	//--------------------------------------------------

	//create page for user access
	$result_page = '
	<!DOCTYPE html>
	<html>

	<head>
	    <meta charset="UTF-8">
	    <title>Data Results</title> 
	</head>

	<body>

		<h2>Request Results</h2><br>

		User: ' . $q_data["email"] .'<br>
		Request #: ' . $q_data["request"] .'<br><br>

		<a href="'. $q_data["request"].'.zip' .'"> All Data (.zip) </a><br><br><br>

		Contents - <br><br>

		<a href="documentation.pdf"> Documentation (.pdf)</a><br>
		<a href="'. $q_data["request"] . ".csv" .'"> Results (.csv)</a><br><br>';


	$result_page .= "Indivudual Results<br>";
	foreach ($q_data["raster"] as $r => $raster){
		$result_page .= '<a href="'. $q_data["request"] .'_'. $raster .'.csv"> '. $q_data["raster"][$r] .'</a><br>';
	}
	

	$result_page .= "<br>Rasters<br>";
	foreach ($q_data["raster"] as $r => $raster){
		if ($rterms[$r] == "true"){
			$result_page .= '<a href="' . "../../../../DET/uploads/globals/processed/". $raster .'/'. $raster_files[$r] .'">'. $q_data["raster"][$r] .'</a><br>';
		}
	}
	

	$result_page .= '
		<br><a href="'. $q_data["request"] . ".json"  .'"> Request #'.$q_data["request"].' Info (.json) </a><br><br><br>

		Request Submitted on: '. gmdate("M d Y H:i:s", $q_data["submission"]) .' GMT<br>
		Request Processed on: '. gmdate("M d Y H:i:s", $time) .' GMT<br>
		Request Expires on: '. gmdate("M d Y H:i:s", $time+$duration) .' GMT<br>

	</body>

	</html>
	';

	file_put_contents($COM_DIR ."/queue/available/". $q_data["request"] ."/". $q_data["request"] . ".html", $result_page); 

	//--------------------------------------------------

	//send email to user with results page
	$mail_to = $q_data["email"];
	$mail_subject = "AidData - Point Extraction Tool Data Request Results #".$q_data["request"];
	$mail_message = "Your data request has been processed and can be accessed using the link below. <br><br>";
	$mail_message .= "<a href='".$MAIL_DIR."/queue/available/".$q_data["request"]."/".$q_data["request"].".html'>Request #".$q_data["request"]."</a>";
	$mail_headers = 'MIME-Version: 1.0' . "\r\n";
	$mail_headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
	mail($mail_to, $mail_subject, $mail_message, $mail_headers);

}

?>