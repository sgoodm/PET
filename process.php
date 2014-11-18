<?php

// ++
// ++ handles ajax requests for PET
// ++

set_time_limit(0);

switch ($_POST['type']) {

	//returns directory contents
	case "scan":

		$dir = $_POST["dir"];
		$rscan = scandir($dir);
		$scan = array_diff($rscan, array('.', '..'));
		$out = json_encode($scan);
		echo $out;
		break;

	//returns queue contents of log
	case "read":
		if ($_POST['call'] == "request") {
			$file = "queue/log.csv";
		} else if ($_POST['call'] == "queue"){
			$file = "queue/pending.csv";
		}
		
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
		echo json_encode($r_queue);
	
		break;	

	//upload user files
	case "upload":

		foreach ($_FILES as $index => $file) {

			if ($file['error'] > 0) {
				// file_put_contents("xyz.txt", "bad1");
				var_dump("error - general");
			}

			if (empty($file['name'])) {
				// file_put_contents("xyz.txt", "bad2");
				var_dump("error - empty name");
			}

			$tmp = $file['tmp_name'];

			if (is_uploaded_file($tmp)){
				$old_mask = umask(0);
				mkdir("uploads/".$_POST["request"], 0775, true);
				if (!move_uploaded_file($tmp, "uploads/".$_POST["request"]."/".$file['name'])){
					echo 'error !';
					// file_put_contents("xyz.txt", "bad3");
					var_dump("error - cannot move uploaded file");
				}
				$name = substr($file['name'], 0, -4);
				// file_put_contents("xyz.txt", "good1");
				var_dump("success - good upload");
			} else {
				echo 'Upload failed !';
				// file_put_contents("xyz.txt", "bad4");
				var_dump("error - upload failed");
			}

		}
		break;

	//adds request to queue
	case "write":
		//load data
		$json = $_POST['request'];
		$contents = json_decode($json, true);
		//write data file
		file_put_contents("queue/pending/".$contents['request'].".json", $json);
		//write queue file
		$line = array($contents['request'], $contents['submission'], $contents['completion'], $contents['expiration'], $contents["email"]);
		$handle1 = fopen("queue/log.csv", "a");
		$handle2 = fopen("queue/pending.csv", "a");
		fputcsv($handle1, $line);
		fputcsv($handle2, $line);
		fclose($handle1);
		fclose($handle2);
		echo "write success";
		break;

	//send email to user with results page	
	case "email":
		$mail_to = $_POST["email"];
		$mail_subject = "AidData - Point Extraction Tool Request #".$_POST["request"]." Received";
		$mail_message = "Your data request has been received and will be processed soon (position ".$_POST['queue']." in queue).<br><br>";
		$mail_message .= $_POST["message"];
		$mail_headers = 'MIME-Version: 1.0' . "\r\n";
		$mail_headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
		mail($mail_to, $mail_subject, $mail_message, $mail_headers);
		echo "email sent";
		break;



}

?>