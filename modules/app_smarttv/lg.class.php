<?php
/**
 * LG Smart TV Control class
 *
 * @author Fedorov Ivan <4fedorov@gmail.com>
 * @copyright Fedorov I.A.
 *
 */
 
class lg_tv{

	function mSearch($timeout = 2)
	{
/*	$msg  = 'M-SEARCH * HTTP/1.1'."\r\n";
		$msg .= 'HOST: 239.255.255.250:1900'."\r\n";
		$msg .= 'MAN: "ssdp:discover"'."\r\n";
		$msg .= 'MX: 3'."\r\n";
		$msg .= 'ST: urn:schemas-udap:service:netrcu:1'."\r\n"; 
		$msg .= 'USER-AGENT: UDAP/2.0'."\r\n";
		$msg .= "\r\n";
*/	
		$msg  = 'B-SEARCH * HTTP/1.1'."\r\n";
		$msg .= 'HOST: 255.255.255.255:1990'."\r\n";
		$msg .= 'MAN: "ssdp:discover"'."\r\n";
		$msg .= 'MX: 2'."\r\n";
		$msg .= 'ST: urn:schemas-udap:service:netrcu:1'."\r\n"; 
		$msg .= 'USER-AGENT: UDAP/2.0'."\r\n";
		$msg .= "\r\n";

		$sock = socket_create(AF_INET, SOCK_DGRAM, 0);
		//if (!$sock)	die('Unable to create AF_INET socket');
		socket_set_option($sock, SOL_SOCKET, SO_BROADCAST, 1);
		socket_sendto( $sock, $msg, strlen($msg), 0, '255.255.255.255', 1990);//<-- B-SEARCH
		
		socket_set_option($sock, SOL_SOCKET, SO_RCVTIMEO, array('sec'=>$timeout, 'usec'=>'0'));
		
		$response = array();
		do {
			$buf = null;
			@socket_recvfrom($sock, $buf, 1024, MSG_WAITALL, $from, $port); 
			if(!is_null($buf)) $response[] = $this->parseMSearchResponse($buf, $from);
		} while(!is_null($buf));
		
		// CLOSE SOCKET
		socket_close($sock);
		return $response;
		
		
	}
	
	function parseMSearchResponse($res, $ip)
	{
		$result = array();
		$lines = explode("\r\n", trim($res));
		if(trim($lines[0]) == 'HTTP/1.1 200 OK') {
			array_shift($lines);
			foreach($lines as $row) {
				if( stripos($row, 'loca') === 0 ){
					$result["location"] = str_ireplace('location: ', '', $row);
					$result["ip"] = $ip;
				}
			}	
		}
		return $result;
	}

	function requestPairing($ip, $port = 8080){
		$data = '<?xml version="1.0" encoding="utf-8"?><envelope><api type="pairing"><name>showKey</name></api></envelope>';
		$path = '/udap/api/pairing';
		return $this->call($ip, $port, $path, $data);	
	}
	
	function confirmPairing($ip, $key, $port = 8080){
		$data = '<?xml version="1.0" encoding="utf-8"?><envelope><api type="pairing"><name>hello</name><value>'.$key.'</value><port>'.$port.'</port></api></envelope>';
		$path = '/udap/api/pairing';
		return $this->call($ip, $port, $path, $data);	
	}

	function endPairing($ip, $port = 8080){
		$data = '<?xml version="1.0" encoding="utf-8"?><envelope><api type="pairing"><name>byebye</name><port>'.$port.'</port></api></envelope>';
		$path = '/udap/api/pairing';
		return $this->call($ip, $port, $path, $data);	
	}
	
	function sendCommand($ip, $key, $cmd, $name = 'HandleKeyInput', $port = 8080){
		$data = '<?xml version="1.0" encoding="utf-8"?><envelope><api type="command"><name>'.$name.'</name><value>'.$cmd.'</value></api></envelope>';
		$path = '/udap/api/command';
		$res = $this->call($ip, $port, $path, $data);
		return $res;
	}
	
	function sendQuery ($ip, $key, $query, $img = 0, $port = 8080){
		$path = '/udap/api/data?target='.$query;
		$res = $this->confirmPairing($ip, $key, $port);
		if($res["http_code"] == 200){
			$res = $this->call2($ip, $port, $path, $img);
			$this->endPairing($ip, $port);
		}
		return $res;
	}

	function call($ip, $port, $path, $data){
		$res = array();
		$http = curl_init();
		curl_setopt_array($http, 
			array(
				CURLOPT_URL => 'http://'.$ip.':'.$port.$path,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => $data,
				CURLOPT_HTTPHEADER => array(
					'Content-Type: text/xml; charset=utf-8',
					'Cache-Control: no-cache',
					'Connection: Keep-Alive'
				),
				CURLOPT_USERAGENT => 'User-Agent: Linux/2.6.18 UDAP/2.0 CentOS/5.8',
				CURLOPT_CONNECTTIMEOUT => 2
			)
		);

		$res["http_data"] = curl_exec($http);
		$res["http_code"] = curl_getinfo($http, CURLINFO_HTTP_CODE);
		curl_close($http);
		return $res;
	}
	
	function call2($ip, $port, $path, $img){
		$res = array();
		$http = curl_init();
		curl_setopt_array($http, 
			array(
				CURLOPT_URL => 'http://'.$ip.':'.$port.$path,
				CURLOPT_RETURNTRANSFER => true
			)
		);
		
		if($img == 1){
			$res["http_data"] = curl_exec($http);
		} else {
			$res["http_data"] = simplexml_load_string(curl_exec($http));
		}
		$res["http_code"] = curl_getinfo($http, CURLINFO_HTTP_CODE);
		curl_close($http);
		return $res;
	}
}
?>
