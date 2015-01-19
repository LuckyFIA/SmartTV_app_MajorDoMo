<?php
//phpinfo();
include_once("lg.class.php");

$lgTV = new lg_tv();
/*
// Поиск 
$response = $lgTV->mSearch();
foreach($response as $res){
	$xml = simplexml_load_file($res["location"]);
	$device = $xml->device;
	$name = $device->friendlyName;
	$model = $device->modelName;
	echo "<br>$model $name ". $res["ip"];
}


// End


// Pairing
echo"<br>";
$res11 = array();
$res11 = $lgTV->RequestPairingKey($response[0]["ip"]);
print_r($res11);

// End Pairing

if($response["loc"] != ''){
	
}
*/

$ip = "192.168.10.189";
$port = 8080;
//$path = '/udap/api/data?target=volume_info';
$path = '/udap/api/data?target=volume_info';

$key = 982887;
	$res = array();
	$url =  'http://'.$ip.':'.$port.$path;
$res = $lgTV->confirmPairing($ip, $key, $port);



	$http = curl_init();
		curl_setopt_array($http, 
			array(
				CURLOPT_URL => 'http://'.$ip.':'.$port.$path,
				CURLOPT_RETURNTRANSFER => true,
			)
		);
	$xml = simplexml_load_string(curl_exec($http));
	$res["http_data"] = $xml;
	$res["http_code"] = curl_getinfo($http, CURLINFO_HTTP_CODE);
	curl_close($http);	





	print_r($res);
	$mute = $res["http_data"]->{'data'}->mute;
	$level = $res["http_data"]->{'data'}->level;
	echo"<br>Mute: $mute<br>Level: $level";
/*	
	$http = curl_init();
		curl_setopt_array($http, 
			array(
				CURLOPT_URL => $url,
				CURLOPT_RETURNTRANSFER => true,
			)
		);
		$res = curl_exec($http);
		
		curl_close($http);
		$res2 = simplexml_load_string($res);
		print_r($res2);
	*/	
//if($res["http_code"] == 200){

	//$data_file = 'http://'.$ip.':'.$port.'/udap/api/data?target=volume_info';
	//$xml = simplexml_load_file($data_file);
	
	$data = '';
	$path = '/udap/api/data?target=volume_info';

	//$res = $lgTV->call($ip, $port, $path, $data);
/*	
		
		
echo"111";
		$res1 = curl_exec($http);
		
		curl_close($http);
*/	
	//print_r($res);	


	
	//print_r($lgTV->endPairing($ip, $port));
	
	
//}



include_once(DIR_MODULES.'app_smarttv/app_smarttv.class.php');
$smartTv=new app_smarttv();

if(is_array($params))
{
	if(isset($params["alias"])){
		if(isset($params['vol'])) $smartTv->set_volume($params['vol'],$app_radio);
	}
	
}
else
{
	if($params=='play' || $params=='stop')  $app_radio->control($params);
	else if(strpos($params, "vol")===0) $app_radio->set_volume((int)substr($params,3),$app_radio);
	else if(strpos($params, "sta:")===0) $app_radio->change_station(substr($params,4),$app_radio);
}



?>