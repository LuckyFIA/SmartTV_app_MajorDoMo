<?php

/**
 * Smart TV Control module
 *
 * module for MajorDoMo project
 * @author Fedorov Ivan <4fedorov@gmail.com>
 * @copyright Fedorov I.A.
 * @version 0.1 November 2014
 */
class app_smarttv extends module
{

    function app_smarttv()
    {
        $this->name = "app_smarttv";
        $this->title = "SmartTV";
        $this->module_category = "<#LANG_SECTION_DEVICES#>";
        $this->checkInstalled();
    }

    function saveParams()
    {
        $p = array();
        if (IsSet($this->id)) {
            $p["id"] = $this->id;
        }
        if (IsSet($this->view_mode)) {
            $p["view_mode"] = $this->view_mode;
        }
        if (IsSet($this->edit_mode)) {
            $p["edit_mode"] = $this->edit_mode;
        }
        if (IsSet($this->tab)) {
            $p["tab"] = $this->tab;
        }
        return parent::saveParams($p);
    }

    function getParams()
    {
        global $id;
        global $mode;
        global $view_mode;
        global $alias;
		// global $tab;

        if (isset($id)) {
            $this->id = $id;
        }
        if (isset($mode)) {
            $this->mode = $mode;
        }
        if (isset($alias)) {
            $this->alias = $alias;
        }
        /* if (isset($view_mode)) {
            $this->view_mode = $view_mode;
        }
        if (isset($tab)) {
            $this->tab = $tab;
        } */
    }

    function run()
    {
        global $session;
        $out = array();
        $this->className = 'SmartTV';
        if ($this->action == 'admin') {
            $this->admin($out);
        } else {
            $this->usual($out);
        }
        if (IsSet($this->owner->action)) {
            $out['PARENT_ACTION'] = $this->owner->action;
        }
        if (IsSet($this->owner->name)) {
            $out['PARENT_NAME'] = $this->owner->name;
        }
        $out['VIEW_MODE'] = $this->view_mode;
        //$out['EDIT_MODE'] = $this->edit_mode;
        $out['MODE'] = $this->mode;
        $out['ACTION'] = $this->action;
        if ($this->single_rec) {
            $out['SINGLE_REC'] = 1;
        }
        $this->data = $out;
        $p = new parser(DIR_TEMPLATES . $this->name . "/" . $this->name . ".html", $this->data, $this);
        $this->result = $p->result;
    }

    /**
     * BackEnd
     *
     * Module backend
     *
     * @access public
     */
    function admin(&$out)
    {
        include_once("lg.class.php");
        global $subm;

		switch ($subm){
			case 'search':
                $this->search($out);
				break;
			case 'asearch':
                $this->findDevices($out);
				break;
			case 'msearch':
                $this->msearch($out);
				break;
            case 'help':
                $this->view_mode = "help";
                break;
			case 'pairing':
				$this->requestPairing($out);
				break;
			case 'pairingKey':
				$this->confirmPairing($out);
				break;
			case 'pairingDelete':
				$this->deletePairing($out);
				break;
			case 'sendCmd':
				global $cmd;
                global $alias;
				$lgTV = new lg_tv();
				$device = $this->device($alias);
				$this->sendCmd($lgTV, $device, $cmd);
			case '':
				$this->viewPairing($out);
				break;
        }
    }

    /**
     * FrontEnd
     *
     * Module frontend
     *
     * @access public
     */
    function usual(&$out)
    {
        global $alias;
		if(!isset($alias)) $alias = $this->alias;
		if ($alias != '') {
            include_once("lg.class.php");
            global $cmd;
			$out["alias"] = $alias;
            $code = array('btn_volUP' => '24', 'btn_volDOWN' => '25', 'btn_chUP' => '27', 'btn_chDOWN' => '28', 'btn_OK' => '20', 'btn_UP' => '12', 'btn_DOWN' => '13', 'btn_LEFT' => '14', 'btn_RIGHT' => '15', 'btn_0' => '2', 'btn_1' => '3', 'btn_2' => '4', 'btn_3' => '5', 'btn_4' => '6', 'btn_5' => '7', 'btn_6' => '8', 'btn_7' => '9', 'btn_8' => '10', 'btn_9' => '11', 'btn_LIST' => '50', 'btn_BACK' => '403', 'btn_OFF' => '1', 'btn_MUTE' => '26', 'btn_INPUT' => '47', 'btn_MENU' => '21');
            $lgTV = new lg_tv();
            $device = $this->device($alias);
            $this->sendCmd($lgTV, $device, $code[$cmd]);
		}
	}

    function search(&$out)
    {
        $this->view_mode = "search";
		//$out["SEARCH"] = 'Поиск устройств в сети';
    }
	
	function msearch(&$out)
    {
        $this->view_mode = "msearch";
		global $ip;
		if(isset($ip)){	
			$out["IP"] = $ip;
			$lgTV = new lg_tv();
			$res = $lgTV->requestPairing($ip);
			if($res["http_code"] == 200){
				$out["PAIRING"] = 'Ok';
			} else {
				$out["PAIRING"] = 'Ошибка!';
			}
		}
		//$out["SEARCH"] = 'Поиск устройств в сети';
    }
	
	function findDevices(&$out)
    {
        $lgTV = new lg_tv();
        $response = $lgTV->mSearch();
        $this->view_mode = "search";
        foreach ($response as $res) {
            $xml = simplexml_load_file($res["location"]);
            $device = $xml->device;
            $found["name"] = $device->friendlyName;
            $found["model"] = $device->modelName;
            $found["ip"] = $res["ip"];
            $out["FOUND_DEVICE"][] = $found;
        }
		if (!$response) $out["SEARCH"] = 'Устройств не найдено. Повторите поиск или добавьте вручную';
    }

    function requestPairing(&$out)
    {
        global $name;
        global $ip;

        $lgTV = new lg_tv();
        $res = $lgTV->requestPairing($ip);
        $this->view_mode = "pairing";
        $out["name"] = $name;
        $out["ip"] = $ip;

        if ($res["http_code"] > 200) {
            $out["ERROR"] = 'Ошибка! Код ' . $res["http_code"];
            $this->findDevices($out);
        }
    }

    function confirmPairing(&$out)
    {
        global $alias;
        global $name;
        global $key;
        global $ip;

        $lgTV = new lg_tv();
        $res = $lgTV->confirmPairing($ip, $key);
        if ($res["http_code"] > 200) {
            $out["ERROR"] = 'Ошибка! Код ' . $res["http_code"];
            $out["alias"] = $alias;
            $out["name"] = $name;
            $out["ip"] = $ip;
            $this->view_mode = "pairing";
        } else {
            $res = $lgTV->endPairing($ip);
            if ($res["http_code"] == 200) {
                $this->savePairing($out, $alias, $name, $key, $ip);
            } else {
                $out["ERROR"] = 'Ошибка! Код ' . $res["http_code"];
                //$this->findDevices($out);
            }
		}
    }

    function savePairing(&$out, $alias, $name, $key, $ip)
    {
        $className = $this->className;

        $rec = SQLSelectOne("SELECT ID FROM classes WHERE TITLE LIKE '" . DBSafe($className) . "'");
        if (!$rec['ID']) {
            $rec = array();
            $rec['TITLE'] = $className;
            $rec['DESCRIPTION'] = 'SmartTV';
            $rec['ID'] = SQLInsert('classes', $rec);
        }

        $obj_rec = SQLSelectOne("SELECT ID FROM objects WHERE CLASS_ID='" . $rec['ID'] . "' AND TITLE LIKE '" . DBSafe($objectName[$i]) . "'");
        if (!$obj_rec['ID']) {
            $obj_rec = array();
            $obj_rec['CLASS_ID'] = $rec['ID'];
            $obj_rec['TITLE'] = $alias;
            $obj_rec['DESCRIPTION'] = $name;
            $obj_rec['ID'] = SQLInsert('objects', $obj_rec);
        }
        sg($className . '.' . $alias . '.brand', "LG");
		sg($className . '.' . $alias . '.name', $name);
        sg($className . '.' . $alias . '.key', $key);
        sg($className . '.' . $alias . '.ip', $ip);
        $out["SUCCESS"] = $name . ' (' . $ip . ') успешно добавлен!';
        $this->viewPairing($out);
    }

    function viewPairing(&$out)
    {
        $className = $this->className;
        $this->view_mode = "";
        $pairing = getObjectsByClass($className);
		if(!empty( $pairing)){
			foreach ($pairing as $obj) {
				$pairingDevice["alias"] = $obj["TITLE"];
				$pairingDevice["name"] = gg($className . '.' . $obj["TITLE"] . '.name');
				$pairingDevice["ip"] = gg($className . '.' . $obj["TITLE"] . '.ip');
				$out["PAIRING_DEVICE"][] = $pairingDevice;
			}
		}
        global $cmd;
        if (isset($cmd)) {
            $lgTV = new lg_tv();
            $className = $this->className;
            $ip = gg($className . '.tv.ip');
            $key = gg($className . '.tv.key');
            $res = $lgTV->sendCommand($ip, $key, $cmd);
        }
	}

    function deletePairing(&$out)
    {
        global $alias;
        $rec = SQLSelectOne("SELECT ID FROM classes WHERE TITLE LIKE '" . DBSafe($this->className) . "'");
        $obj = SQLSelectOne("SELECT * FROM objects WHERE CLASS_ID='" . $rec['ID'] . "' AND TITLE LIKE '" . DBSafe($alias) . "'");
        // some action 
        SQLExec("DELETE FROM history WHERE OBJECT_ID='" . $obj['ID'] . "'");
        SQLExec("DELETE FROM methods WHERE OBJECT_ID='" . $obj['ID'] . "'");
        SQLExec("DELETE FROM pvalues WHERE OBJECT_ID='" . $obj['ID'] . "'");
        SQLExec("DELETE FROM properties WHERE OBJECT_ID='" . $obj['ID'] . "'");
        SQLExec("DELETE FROM objects WHERE ID='" . $obj['ID'] . "'");
		$this->viewPairing($out);
    }

    function control($alias, $cmd, $val=0)
	{
		include_once("lg.class.php");
        $lgTV = new lg_tv();
        $device = $this->device($alias);
		
		
		switch ($cmd){
			case 'setVol':
				$this -> setVolume($lgTV, $device, $val);
				break;
			case 'getVol':
				$currLevel = $this -> getVolume($lgTV, $device);
				return $currLevel;
			case 'sendCode':
				$res = $this -> sendCmd($lgTV, $device, $val);
				return $res;
			case 'curChan':
				$res = $this -> getChannel($lgTV, $device, "cur_channel");
				return $res;
			case 'listChan':
				$res = $this -> getChannel($lgTV, $device, "channel_list");
				return $res;
			case 'setChan':
				$res = $this -> setChannel($lgTV, $device, $val);
				break;
			case 'getImg':
				$res = $this -> getImage($lgTV, $device);
				return $res;
		}
	}
		
	
	function getImage (&$lgTV, &$device)
    {
		$query = $lgTV->sendQuery($device["ip"], $device["key"], "screen_image", 1);
		$res = $query["http_data"];
		return $res;
    }
	
	function getChannel(&$lgTV, &$device, $val)
    {
		$res = array();
		$query = $lgTV->sendQuery($device["ip"], $device["key"], $val);
		$res = json_decode(json_encode($query["http_data"]), TRUE);
		return $res;
    }
	
	function setChannel(&$lgTV, &$device, $cmd)
	{
		$num = preg_split('//', $cmd, -1, PREG_SPLIT_NO_EMPTY);
		
		$res = $lgTV->confirmPairing($device["ip"], $device["key"]);
		if($res["http_code"] == 200){
			foreach($num as $val){
				$res = $lgTV->sendCommand($device["ip"], $device["key"], $val+2);
				usleep(45000);
			}
			$res = $lgTV->sendCommand($device["ip"], $device["key"], 20); //Send Ok
			$lgTV->endPairing($device["ip"]);
		}
	}
	
	function sendCmd(&$lgTV, &$device, $cmd)
	{
		$res = $lgTV->confirmPairing($device["ip"], $device["key"]);
		if($res["http_code"] == 200){
			$res = $lgTV->sendCommand($device["ip"], $device["key"], $cmd);
			$lgTV->endPairing($device["ip"]);
			return $res;
		}
	}
	
	function setVolume(&$lgTV, &$device, $newLevel)
    {
        $currLevel = $this -> getVolume($lgTV, $device);
       
		if($newLevel > $currLevel) $cmd = 24; // Volume Up
		if($newLevel < $currLevel) $cmd = 25; // Volume Down
		$level = abs($newLevel - $currLevel);
		if($level > 11) $level = ($level - 11)/2 + 11;

		if(isset($cmd)){
			$res = $lgTV->confirmPairing($device["ip"], $device["key"]);
			if($res["http_code"] == 200){
				for ($i = 0; $i < $level; $i++) {
					$res = $lgTV->sendCommand($device["ip"], $device["key"], $cmd);
					usleep(45000);
				}
				$lgTV->endPairing($device["ip"]);	
			}
		}	   
    }
	
	function getVolume(&$lgTV, &$device)
    {
		$query = $lgTV->sendQuery($device["ip"], $device["key"], "volume_info");
		$currLevel = $query["http_data"]->{'data'}->level;
        return $currLevel;
    }

    function device($alias)
    {
        $device = array();
        $className = 'SmartTV';
        $device["ip"] = gg($className . '.' . $alias . '.ip');
        $device["key"] = gg($className . '.' . $alias . '.key');
        return $device;
    }

    /**
     * Install
     *
     * Module installation routine
     *
     * @access private
     */
    function install($parent_name = "")
    {

        parent::install($parent_name);
    }

    function uninstall()
    {

    }


// --------------------------------------------------------------------
}

?>