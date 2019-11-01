<style>
  body {
  font-family: monaco, monospace;
  font-size: 0.7em;
  // font-family: arial,sans-serif;
  // font-size: small;
  text-align: left;
}
h1 {
  font-size: 20px
}
h2 {
  font-size: 14px;
}
h3 {
  font-size: 10px;
}
</style>

<?php
// Uncomment to report Errors for Debug purposes
// error_reporting(E_ALL);
require('remeha_functions.php');

// remeha.ini file Variables
//
$ini_array = parse_ini_file("remeha.ini");
$ESPIPAddress = $ini_array['ESPIPAddress'];
$ESPPort = $ini_array['ESPPort'];
$retries = $ini_array['retries'];
$nanosleeptime =  $ini_array['nanosleeptime'];
$echo_flag = "1";
$newline = $ini_array['newline'];
	if ($newline == "terminal"){$newline = "\n";}
	elseif ($newline == "windows"){$newline = "\r\n";} 
	else {$newline = "<br />";}
$phpver = phpversion();

$remeha_counter1 = hex2bin($ini_array['remeha_counter1']);
$remeha_counter2 = hex2bin($ini_array['remeha_counter2']);
$remeha_counter3 = hex2bin($ini_array['remeha_counter3']);
$remeha_counter4 = hex2bin($ini_array['remeha_counter4']);
$remeha_sample_req_length = $ini_array['remeha_sample_req_length'];
$remeha_counter_length = $ini_array['remeha_counter_length'];

$fp = connect_to_esp($ESPIPAddress, $ESPPort, $retries, $newline);
if (!$fp) 
	{
	exit("Unable to establish connection to $ESPIPAddress:$ESPPort$newline");
	} 
else
	{
	stream_set_timeout($fp, 5);
	// Collect Counter Info
	conditional_echo(str_repeat("=", 80) . "$newline", $echo_flag);
	conditional_echo("PHP version: $phpver$newline", $echo_flag);
	conditional_echo("Connected to $ESPIPAddress:$ESPPort$newline", $echo_flag);
	conditional_echo("Sending request...$newline", $echo_flag);
	fwrite($fp,$remeha_counter1, $remeha_sample_req_length);
	$data_counter1 = "";
	$data_counter1 = bin2hex(fread($fp, $remeha_counter_length));
	$data_counter1U = strtoupper($data_counter1);
	conditional_echo("Counter Data-1 read: $data_counter1U$newline", $echo_flag);
	usleep($nanosleeptime);
	
	if (!($remeha_counter2 == "")) 
		{
		fwrite($fp,$remeha_counter2, $remeha_sample_req_length);
		$data_counter2 = "";
		$data_counter2 = bin2hex(fread($fp, $remeha_counter_length));
		$data_counter2U = strtoupper($data_counter2);
		conditional_echo("Counter Data-2 read: $data_counter2U$newline", $echo_flag);
		usleep($nanosleeptime);
		}
	if (!($remeha_counter3 == "")) 
		{
		fwrite($fp,$remeha_counter3, $remeha_sample_req_length);
		$data_counter3="";
		$data_counter3=bin2hex(fread($fp, $remeha_counter_length));
		$data_counter3U = strtoupper($data_counter3);
		conditional_echo("Counter Data-3 read: $data_counter3U$newline", $echo_flag);
		usleep($nanosleeptime);
		}
	if (!($remeha_counter4 == "")) 
		{
		fwrite($fp,$remeha_counter4, $remeha_sample_req_length);
		$data_counter4="";
		$data_counter4=bin2hex(fread($fp, $remeha_counter_length));
		$data_counter4U = strtoupper($data_counter4);
		conditional_echo("Counter Data-4 read: $data_counter4U$newline", $echo_flag);
		}	
	$output = counter_data_dump($data_counter1, $data_counter2, $data_counter3, $data_counter4, $echo_flag, $newline, $ini_array);
	fclose($fp);

	}

// Time to 'Work the COUNTER Data'
//
function counter_data_dump($data_counter1, $data_counter2, $data_counter3, $data_counter4, $echo_flag, $newline, $ini_array)
{
	$remeha_counter_length = $ini_array['remeha_counter_length'];
	$remeha_crc  = $ini_array['remeha_crc'];
	$remeha_header = $ini_array['remeha_header'];
	$counter_offset = $ini_array['counter_offset'];
	
	// Manipulate data & Do a CRC Check	
	$decode_cnt1 = str_split($data_counter1, 2);
	$hexstr_cnt1 = str_split($data_counter1, $remeha_counter_length);  #length is 52 for calenta and 48 for avanta
	$hexstrPayload_cnt1 = substr($data_counter1, 2, $remeha_counter_length - $remeha_crc - 4);
	$hexstrCRC_cnt1 = substr($data_counter1, $remeha_counter_length - $remeha_crc - 2, $remeha_crc);
	
	if ($remeha_crc == 2) {
		$crcCalc_cnt1 = checksum8xor($hexstrPayload_cnt1);	
	}
	else {
		$crcCalc_cnt1 = crc16_modbus($hexstrPayload_cnt1);
	}
	
	$decode_cnt2 = str_split($data_counter2, 2);
	$hexstr_cnt2 = str_split($data_counter2, $remeha_counter_length);
	$hexstrPayload_cnt2 = substr($data_counter2, 2, $remeha_counter_length - $remeha_crc - 4);
	$hexstrCRC_cnt2 = substr($data_counter2, $remeha_counter_length - $remeha_crc - 2, $remeha_crc);
	
	if ($remeha_crc == 2) {
		$crcCalc_cnt2 = checksum8xor($hexstrPayload_cnt2);	
	}
	else {
		$crcCalc_cnt2 = crc16_modbus($hexstrPayload_cnt2);
	}
	if ($hexstrCRC_cnt2 == "") {$hexstrCRC_cnt2 = "0";}
	
	$decode_cnt3 = str_split($data_counter3, 2);
	$hexstr_cnt3 = str_split($data_counter3, $remeha_counter_length);
	$hexstrPayload_cnt3 = substr($data_counter3, 2, $remeha_counter_length - $remeha_crc - 4);
	$hexstrCRC_cnt3 = substr($data_counter3, $remeha_counter_length - $remeha_crc - 2, $remeha_crc);
	
	if ($remeha_crc == 2) {
		$crcCalc_cnt3 = checksum8xor($hexstrPayload_cnt3);	
	}
	else {
		$crcCalc_cnt3 = crc16_modbus($hexstrPayload_cnt3);
	}
	if ($hexstrCRC_cnt3 == "") {$hexstrCRC_cnt3 = "0";}
	
	$decode_cnt4 = str_split($data_counter4, 2);
	$hexstr_cnt4 = str_split($data_counter4, $remeha_counter_length);
	$hexstrPayload_cnt4 = substr($data_counter4, 2, $remeha_counter_length - $remeha_crc - 4);
	$hexstrCRC_cnt4 = substr($data_counter4, $remeha_counter_length - $remeha_crc - 2, $remeha_crc);
	
	if ($remeha_crc == 2) {
		$crcCalc_cnt4 = checksum8xor($hexstrPayload_cnt4);	
	}
	else {
		$crcCalc_cnt4 = crc16_modbus($hexstrPayload_cnt4);
	}
	
	if ($hexstrCRC_cnt4 == "") {$hexstrCRC_cnt4 = "0";}
	
	// Concatenate Counter data to work with
	$concat_counter = substr($hexstrPayload_cnt1, 2*($remeha_header + $counter_offset), 32-$counter_offset).substr($hexstrPayload_cnt2, 2*$remeha_header, 32).substr($hexstrPayload_cnt3, 2*$remeha_header, 32).substr($hexstrPayload_cnt4, 2*$remeha_header, 32);
	conditional_echo("Counter Data total read: $concat_counter$newline", $echo_flag);
	
	
	$decode_counter = str_split($concat_counter, 2);		

	// Write the contents to the file
	$ini_array = parse_ini_file("remeha.ini");
	$log_data = $ini_array['log_data'];
	$path = $ini_array['path_to_logs'];
	$filename = $ini_array['counter_data_log'];
	$file = "$path$filename";
	date_default_timezone_set('Europe/Amsterdam');
	$date = date_create();

	if (($hexstrCRC_cnt1 == $crcCalc_cnt1) && ($hexstrCRC_cnt2 == $crcCalc_cnt2) && ($hexstrCRC_cnt3 == $crcCalc_cnt3) && ($hexstrCRC_cnt4 == $crcCalc_cnt4))
		{
		conditional_echo("Data Integrity Good - CRCs Compute OK$newline", $echo_flag);
		if ($log_data == 2)
			{
			$datatowrite = date_format($date, 'Y-m-d H:i:s') . ' | 02 ' . $hexstrPayload_cnt1 . ' ' . $hexstrCRC_cnt1 . ' ' .'03 | ' . '02 ' . $hexstrPayload_cnt2 . ' ' . $hexstrCRC_cnt2 . ' ' .'03 | ' . '02 ' . $hexstrPayload_cnt3 . ' ' . $hexstrCRC_cnt3 . ' ' .'03 | ' . '02 ' . $hexstrPayload_cnt4 . ' ' . $hexstrCRC_cnt4 . ' ' .'03 |' . "\n";
			file_put_contents($file, $datatowrite, FILE_APPEND);
			conditional_echo("Data written to log: $file$newline", $echo_flag);
			}
		conditional_echo(str_repeat("=", 80) . "$newline", $echo_flag);
		}
	else
		{
		if (($log_data == 1) || ($log_data == 2))
			{
			$datatowrite = '**** CRC Error **** | ' . date_format($date, 'Y-m-d H:i:s') . ' | 02 ' . $hexstrPayload_cnt1 . ' ' . $hexstrCRC_cnt1 . ' ' .'03 | ' . '02 ' . $hexstrPayload_cnt2 . ' ' . $hexstrCRC_cnt2 . ' ' .'03 | ' . '02 ' . $hexstrPayload_cnt3 . ' ' . $hexstrCRC_cnt3 . ' ' .'03 | ' . '02 ' . $hexstrPayload_cnt4 . ' ' . $hexstrCRC_cnt4 . ' ' .'03 |' . "\n";
			file_put_contents($file, $datatowrite, FILE_APPEND);
			conditional_echo("Data written to log: $file$newline", $echo_flag);
			}
		conditional_echo("$newline", $echo_flag);
		conditional_echo("************** CRC ERROR!!!! ***********$newline", $echo_flag);
		return;		# Don't continue with updating Counter data
		}

	// Counter Info
	$pumphours_ch_dhw = $decode_counter["0"];
	$pumphours_ch_dhw .= $decode_counter["1"];
	$threewayvalvehours = $decode_counter["2"];
	$threewayvalvehours .= $decode_counter["3"];
	$hours_ch_dhw = $decode_counter["4"];
	$hours_ch_dhw .= $decode_counter["5"];
	$hours_dhw = $decode_counter["6"];
	$hours_dhw .= $decode_counter["7"];
	$powerhours_ch_dhw = $decode_counter["8"];
	$powerhours_ch_dhw .= $decode_counter["9"];
	$pumpstarts_ch_dhw = $decode_counter["10"];
	$pumpstarts_ch_dhw .= $decode_counter["11"];
	$nr_threewayvalvecycles = $decode_counter["12"];
	$nr_threewayvalvecycles .= $decode_counter["13"];
	$burnerstarts_dhw = $decode_counter["14"];
	$burnerstarts_dhw .= $decode_counter["15"];
	$tot_burnerstarts_ch_dhw = $decode_counter["16"];
	$tot_burnerstarts_ch_dhw .= $decode_counter["17"];
	$failed_burnerstarts = $decode_counter["18"];
	$failed_burnerstarts .= $decode_counter["19"];
	$nr_flame_loss = $decode_counter["20"];
	$nr_flame_loss .= $decode_counter["21"];
	// END Counter Info

	//Convert Hex2Dec
	$pumphours_ch_dhw = hexdec($pumphours_ch_dhw)*2;	
	$threewayvalvehours = hexdec($threewayvalvehours)*2;
	$hours_ch_dhw = hexdec($hours_ch_dhw)*2;
	$hours_dhw = hexdec($hours_dhw)*1;
	$powerhours_ch_dhw = hexdec($powerhours_ch_dhw)*2;
	$pumpstarts_ch_dhw = hexdec($pumpstarts_ch_dhw)*8;
	$nr_threewayvalvecycles = hexdec($nr_threewayvalvecycles)*8;
	$burnerstarts_dhw = hexdec($burnerstarts_dhw)*8;
	$tot_burnerstarts_ch_dhw = hexdec($tot_burnerstarts_ch_dhw)*8;
	$failed_burnerstarts = hexdec($failed_burnerstarts)*1;
	$nr_flame_loss = hexdec($nr_flame_loss)*1;
	// END Convert Hex2Dec

	// START Display Counters
	echo "Counters Received: " . date_format($date, 'Y-m-d H:i:s') . "$newline";
	echo str_repeat("=", 80) . "$newline";
	echo "Hours run pump CH+DHW: $pumphours_ch_dhw hours$newline";
	echo "Hours run 3-way valve DHW: $threewayvalvehours hours$newline";
	echo "Hours run CH+DHW: $hours_ch_dhw hours$newline";
	echo "Hours run DHW: $hours_dhw hours$newline";
	echo "Power Supply available hours: $powerhours_ch_dhw hours$newline";
	echo "Pump starts CH+DHW: $pumpstarts_ch_dhw starts$newline";
	echo "Number of 3-way valve cycles: $nr_threewayvalvecycles cycles$newline";
	echo "Burner Starts DHW: $burnerstarts_dhw starts$newline";
	echo "Total Burner Starts CH+DHW: $tot_burnerstarts_ch_dhw starts$newline";
	echo "Failed burner starts: $failed_burnerstarts starts$newline";
	echo "Number of flame loss: $nr_flame_loss times$newline";
	echo str_repeat("=", 80) . "$newline";
	// END Display Counters

	// Update Domoticz Devices with collected values
	// DomoticZ Device ID's
	$pumphours_ch_dhwIDX = $ini_array['pumphours_ch_dhwIDX'];
	$threewayvalvehoursIDX = $ini_array['threewayvalvehoursIDX'];
	$hours_ch_dhwIDX = $ini_array['hours_ch_dhwIDX'];
	$hours_dhwIDX = $ini_array['hours_dhwIDX'];
	$powerhours_ch_dhwIDX = $ini_array['powerhours_ch_dhwIDX'];
	$pumpstarts_ch_dhwIDX = $ini_array['pumpstarts_ch_dhwIDX'];
	$nr_threewayvalvecyclesIDX = $ini_array['nr_threewayvalvecyclesIDX'];
	$burnerstarts_dhwIDX = $ini_array['burnerstarts_dhwIDX'];
	$tot_burnerstarts_ch_dhwIDX = $ini_array['tot_burnerstarts_ch_dhwIDX'];
	$failed_burnerstartsIDX = $ini_array['failed_burnerstartsIDX'];
	$nr_flame_lossIDX = $ini_array['nr_flame_lossIDX'];
	// END Device ID's

	// Set variables for cURL updates & call udevice function to update
	$DOMOIPAddress = $ini_array['DOMOIPAddress'];
	$DOMOPort = $ini_array['DOMOPort'];
	$Username = $ini_array['Username'];
	$Password = $ini_array['Password'];
	$DOMOUpdate = $ini_array['DOMOUpdate'];
	$DOMOUpdateInterval = $ini_array['DOMOUpdateInterval'];

	$url = "http://$Username:$Password@$DOMOIPAddress:$DOMOPort/json.htm?type=devices&filter=all&order=ID";
	$json_string = file_get_contents($url);
	$parsed_json = json_decode($json_string, true);	
	$DOMOdevices_lastupdate = array_lookup($parsed_json, $pumphours_ch_dhwIDX, "LastUpdate");
	$now = date('Y-m-d H:i:s');
	$time_diff_mins = number_format((strtotime($now) - strtotime($DOMOdevices_lastupdate))/60, 2);
	echo "Last Update:$DOMOdevices_lastupdate Time Now:$now Elapsed:$time_diff_mins<br />";
	if ($time_diff_mins > $DOMOUpdateInterval) {$DOMOUpdateAll = 1;}
	else {$DOMOUpdateAll = $ini_array['DOMOUpdateAll'];}

	// Set variables for cURL updates & call udevice function to update
	If ($DOMOUpdateAll == 1)
		{
		$DOMOpumphours_ch_dhw = udevice($pumphours_ch_dhwIDX, 0, $pumphours_ch_dhw, $DOMOIPAddress, $DOMOPort, $Username, $Password, $DOMOUpdate);
		$DOMOthreewayvalvehours = udevice($threewayvalvehoursIDX, 0, $threewayvalvehours, $DOMOIPAddress, $DOMOPort, $Username, $Password, $DOMOUpdate);
		$DOMOhours_ch_dhw = udevice($hours_ch_dhwIDX, 0, $hours_ch_dhw, $DOMOIPAddress, $DOMOPort, $Username, $Password, $DOMOUpdate);
		$DOMOhours_dhw = udevice($hours_dhwIDX, 0, $hours_dhw, $DOMOIPAddress, $DOMOPort, $Username, $Password, $DOMOUpdate);
		$DOMOpowerhours_ch_dhw = udevice($powerhours_ch_dhwIDX, 0, $powerhours_ch_dhw, $DOMOIPAddress, $DOMOPort, $Username, $Password, $DOMOUpdate);
		$DOMOpumpstarts_ch_dhw = udevice($pumpstarts_ch_dhwIDX, 0, $pumpstarts_ch_dhw, $DOMOIPAddress, $DOMOPort, $Username, $Password, $DOMOUpdate);
		$DOMOnr_threewayvalvecycles = udevice($nr_threewayvalvecyclesIDX, 0, $nr_threewayvalvecycles, $DOMOIPAddress, $DOMOPort, $Username, $Password, $DOMOUpdate);
		$DOMOburnerstarts_dhw = udevice($burnerstarts_dhwIDX, 0, $burnerstarts_dhw, $DOMOIPAddress, $DOMOPort, $Username, $Password, $DOMOUpdate);
		$DOMOtot_burnerstarts_ch_dhw = udevice($tot_burnerstarts_ch_dhwIDX, 0, $tot_burnerstarts_ch_dhw, $DOMOIPAddress, $DOMOPort, $Username, $Password, $DOMOUpdate);
		$DOMOfailed_burnerstarts = udevice($failed_burnerstartsIDX, 0, $failed_burnerstarts, $DOMOIPAddress, $DOMOPort, $Username, $Password, $DOMOUpdate);
		$DOMOnr_flame_loss = udevice($nr_flame_lossIDX, 0, $nr_flame_loss, $DOMOIPAddress, $DOMOPort, $Username, $Password, $DOMOUpdate);
		echo "Update All: Domoticz Update:$DOMOUpdate Update All:$DOMOUpdateAll$newline";
		echo str_repeat("=", 80) . "$newline";
		}
	else
		{
		$DOMOType = "Data";		// Lookup the 'Data' devices
		$DOMOpumphours_ch_dhw_array = preg_replace('/[^0-9.]+/', '', array_lookup($parsed_json, $pumphours_ch_dhwIDX, $DOMOType));
		If ($DOMOpumphours_ch_dhw_array != $pumphours_ch_dhw) {$DOMOpumphours_ch_dhw = udevice($pumphours_ch_dhwIDX, 0, $pumphours_ch_dhw, $DOMOIPAddress, $DOMOPort, $Username, $Password, $DOMOUpdate);}

		$DOMOthreewayvalvehours_array = preg_replace('/[^0-9.]+/', '', array_lookup($parsed_json, $threewayvalvehoursIDX, $DOMOType));
		If ($DOMOthreewayvalvehours_array != $threewayvalvehours) {$DOMOthreewayvalvehours = udevice($threewayvalvehoursIDX, 0, $threewayvalvehours, $DOMOIPAddress, $DOMOPort, $Username, $Password, $DOMOUpdate);}

		$DOMOhours_ch_dhw_array = preg_replace('/[^0-9.]+/', '', array_lookup($parsed_json, $hours_ch_dhwIDX, $DOMOType));
		If ($DOMOhours_ch_dhw_array != $hours_ch_dhw) {$DOMOhours_ch_dhw = udevice($hours_ch_dhwIDX, 0, $hours_ch_dhw, $DOMOIPAddress, $DOMOPort, $Username, $Password, $DOMOUpdate);}

		$DOMOhours_dhw_array = preg_replace('/[^0-9.]+/', '', array_lookup($parsed_json, $hours_dhwIDX, $DOMOType));
		If ($DOMOhours_dhw_array != $hours_dhw) {$DOMOhours_dhw = udevice($hours_dhwIDX, 0, $hours_dhw, $DOMOIPAddress, $DOMOPort, $Username, $Password, $DOMOUpdate);}
		
		$DOMOpowerhours_ch_dhw_array = preg_replace('/[^0-9.]+/', '', array_lookup($parsed_json, $powerhours_ch_dhwIDX, $DOMOType));
		If ($DOMOpowerhours_ch_dhw_array != $powerhours_ch_dhw) {$DOMOpowerhours_ch_dhw = udevice($powerhours_ch_dhwIDX, 0, $powerhours_ch_dhw, $DOMOIPAddress, $DOMOPort, $Username, $Password, $DOMOUpdate);}

		$DOMOpumpstarts_ch_dhw_array = preg_replace('/[^0-9.]+/', '', array_lookup($parsed_json, $pumpstarts_ch_dhwIDX, $DOMOType));
		If ($DOMOpumpstarts_ch_dhw_array != $pumpstarts_ch_dhw) {$DOMOpumpstarts_ch_dhw = udevice($pumpstarts_ch_dhwIDX, 0, $pumpstarts_ch_dhw, $DOMOIPAddress, $DOMOPort, $Username, $Password, $DOMOUpdate);}

		$DOMOnr_threewayvalvecycles_array = preg_replace('/[^0-9.]+/', '', array_lookup($parsed_json, $nr_threewayvalvecyclesIDX, $DOMOType));
		If ($DOMOnr_threewayvalvecycles_array != $nr_threewayvalvecycles) {$DOMOnr_threewayvalvecycles = udevice($nr_threewayvalvecyclesIDX, 0, $nr_threewayvalvecycles, $DOMOIPAddress, $DOMOPort, $Username, $Password, $DOMOUpdate);}

		$DOMOburnerstarts_dhw_array = preg_replace('/[^0-9.]+/', '', array_lookup($parsed_json, $burnerstarts_dhwIDX, $DOMOType));
		If ($DOMOburnerstarts_dhw_array != $burnerstarts_dhw) {$DOMOburnerstarts_dhw = udevice($burnerstarts_dhwIDX, 0, $burnerstarts_dhw, $DOMOIPAddress, $DOMOPort, $Username, $Password, $DOMOUpdate);}

		$DOMOtot_burnerstarts_ch_dhw_array = preg_replace('/[^0-9.]+/', '', array_lookup($parsed_json, $tot_burnerstarts_ch_dhwIDX, $DOMOType));
		If ($DOMOtot_burnerstarts_ch_dhw_array != $tot_burnerstarts_ch_dhw) {$DOMOtot_burnerstarts_ch_dhw = udevice($tot_burnerstarts_ch_dhwIDX, 0, $tot_burnerstarts_ch_dhw, $DOMOIPAddress, $DOMOPort, $Username, $Password, $DOMOUpdate);}

		$DOMOfailed_burnerstarts_array = preg_replace('/[^0-9.]+/', '', array_lookup($parsed_json, $failed_burnerstartsIDX, $DOMOType));
		If ($DOMOfailed_burnerstarts_array != $failed_burnerstarts) {$DOMOfailed_burnerstarts = udevice($failed_burnerstartsIDX, 0, $failed_burnerstarts, $DOMOIPAddress, $DOMOPort, $Username, $Password, $DOMOUpdate);}

		$DOMOnr_flame_loss_array = preg_replace('/[^0-9.]+/', '', array_lookup($parsed_json, $nr_flame_lossIDX, $DOMOType));
		If ($DOMOnr_flame_loss_array != $nr_flame_loss) {$DOMOnr_flame_loss = udevice($nr_flame_lossIDX, 0, $nr_flame_loss, $DOMOIPAddress, $DOMOPort, $Username, $Password, $DOMOUpdate);}
		
		echo "Update Changes Only: Domoticz Update:$DOMOUpdate Update All:$DOMOUpdateAll$newline";
		echo str_repeat("=", 80) . "$newline";
		}
		// END set variables for cURL updates
}
?>
