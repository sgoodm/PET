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
		if ($_POST['call'] == "queue") {
			$file = "queue/log.csv";
		} else if ($_POST['call'] == "priority"){
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
		$r_priority = array();
		foreach ($rows as $row) {
			$r_queue[] = $row[0];
			$r_priority[] = $row[1];

		}
		if ($_POST['call'] == "queue") {
			echo json_encode($r_queue);
		} else if ($_POST['call'] == "priority"){
			echo json_encode($r_priority);
		}
		break;	

	//adds request to queue
	case "write":
		//load data
		$json = $_POST['action'];
		$contents = json_decode($json, true);
		//write data file
		file_put_contents("queue/pending/".$contents['queue'].".json", $json);
		//write queue file
		$line = array($contents['queue'], $contents['priority'], $contents['request'], $contents['completion'], $contents['expiration'], $contents["email"]);
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
		$mail_subject = "AidData - Data Request #".$_POST["queue"]." Received";
		$mail_message = "Your data request has been received and will be processed soon. <br><br>";
		$mail_message .= $_POST["message"];
		$mail_headers = 'MIME-Version: 1.0' . "\r\n";
		$mail_headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
		mail($mail_to, $mail_subject, $mail_message, $mail_headers);
		echo "email sent";
		break;


}

?>