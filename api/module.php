<?php

namespace pineapple;

define('__INCLUDES__', "/pineapple/modules/PortalAuth/includes/");
define('__CONFIG__', __INCLUDES__ . "config");

// Main directory defines
define('__LOGS__', __INCLUDES__ . "logs/");
define('__HELPFILES__', __INCLUDES__ . "help/");
define('__CHANGELOGS__', __INCLUDES__ . "changelog/");
define('__SCRIPTS__', __INCLUDES__ . "scripts/");
    
// Injection set defines
define('__INJECTS__', __SCRIPTS__ . "injects/");
    
// NetClient defines
define('__DOWNLOAD__', "/www/download/");
define('__WINDL__', __DOWNLOAD__ . "windows/");
define('__OSXDL__', __DOWNLOAD__ . "osx/");
define('__ANDROIDDL__', __DOWNLOAD__ . "android/");
define('__IOSDL__', __DOWNLOAD__ . "ios/");

// PASS defines
define('__PASSDIR__', __INCLUDES__ . "pass/");
define('__PASSSRV__', __PASSDIR__ . "pass.py");
define('__PASSBAK__', __PASSDIR__ . "Backups/pass.py");
define('__NETCLIENTBAK__', __PASSDIR__ . "Backups/NetworkClient.py");
define('__PASSLOG__', __PASSDIR__ . "pass.log");
define('__TARGETLOG__', __PASSDIR__ . "targets.log");
define('__COMPILEWIN__', __PASSDIR__ . "NetCli_Win.zip");
define('__COMPILEOSX__', __PASSDIR__ . "NetCli_OSX.zip");

class PortalAuth extends Module
{
	public function route() {
		switch($this->request->action) {
			case 'getConfigs':
				$this->getConfigs();
				break;
			case 'checkConnection':
				$this->checkIsOnline();
				break;
			case 'updateConfigs':
				$this->saveConfigData($this->request->params);
				break;
			case 'checkTestServerConfig':
				$this->tserverConfigured();
				break;
			case 'readLog':
				$this->retrieveLog($this->request->file, $this->request->type);
				break;
			case 'deleteLog':
				$this->deleteLog($this->request->file);
				break;
			case 'checkCopyJQuery':
				$this->checkCopyJQuery();
				break;
			case 'isOnline':
				$this->checkIsOnline();
				break;
			case 'checkPortalExists':
				$this->portalExists();
				break;
			case 'getLogs':
				$this->getLogs($this->request->type);
				break;
			case 'getInjectionSets':
				$this->getInjectionSets();
				break;
			case 'clonedPortalExists':
				$this->clonedPortalExists($this->request->name);
				break;
			case 'clonePortal':
				$this->clonePortal($this->request->name, $this->request->options, $this->request->inject, $this->request->activate);
				break;
			case 'checkPASSRunning':
				$this->getPID();
				break;
			case 'startServer':
				$this->startServer();
				break;
			case 'stopServer':
				$this->stopServer();
				break;
			case 'getCode':
				$this->loadPASSCode();
				break;
			case 'restoreCode':
				switch($this->request->file) {
					case 'pass':
						$this->restoreFile(__PASSSRV__, __PASSBAK__);
						break;
					default:
						$base = __INJECTS__ . $this->request->set . "/";
						$ext = ($this->request->file == "auth") ? ".php" : ".txt";
						$path = $base . $this->request->file . $ext;
						$pathBak = $base . "backups/" . $this->request->file . $ext;
						$this->restoreFile($path, $pathBak);
						break;
				}
				break;
			case 'saveCode':
				switch($this->request->file) {
					case 'pass':
						$this->saveClonerFile(__PASSSRV__, $this->request->data);
						break;
					default:
						$base = __INJECTS__ . $this->request->set . "/";
						$ext = ($this->request->file == "auth") ? ".php" : ".txt";
						$path = $base . $this->request->file . $ext;
						$this->saveClonerFile($path, $this->request->data);
						break;
				}
				break;
			case 'backupCode':
				switch($this->request->file) {
					case 'pass':
						$this->saveClonerFile(__PASSSRV__, $this->request->data);
						$this->backupFile(__PASSSRV__, __PASSBAK__);
						break;
					default:
						$base = __INJECTS__ . $this->request->set . "/";
						$ext = ($this->request->file == "auth") ? ".php" : ".txt";
						$path = $base . $this->request->file . $ext;
						$pathBak = $base . "backups/" . $this->request->file . $ext;
						$this->saveClonerFile($path, $this->request->data);
						$this->backupFile($path, $pathBak);
						break;
				}
				break;
			case 'clearLog':
				$this->clearLog($this->request->file);
				break;
			case 'downloadLog':
				$this->downloadLog($this->request->file);
				break;
			case 'getInjectCode':
				$this->getInjectCode($this->request->injectSet);
				break;
			case 'downloadInjectSet':
				$this->exportInjectionSet($this->request->set);
				break;
			case 'deleteInjectSet':
				$this->deleteInjectionSet($this->request->set);
				break;
		}
	}
	
	//============================//
	//    DEPENDENCY FUNCTIONS    //
	//============================//
	
	private function tserverConfigured() {
		$configs = $this->loadConfigData();
		if (empty($configs['testSite']) || empty($configs['dataExpected'])) {
			$this->respond(false);
			return;
		}
		$this->respond(true);
	}
	
	private function checkCopyJQuery() {
		// Check if file exists already
		if (file_exists("/www/nodogsplash/jquery.min.js")) {
			$this->respond(true);
			return;
		}
		
		// Check if the /www/nodogsplash/ directory exists
		if (!is_dir("/www/nodogsplash")) {
			mkdir("/www/nodogsplash/", 0755, true);
		}
		if (copy(__SCRIPTS__ . "jquery.min.js", "/www/nodogsplash/jquery.min.js")) {
			$this->respond(true);
			return;
		}
		$this->logError("jQuery_copy_failed", "Attempted to create /www/nodogsplash/ and copy jquery.min.js to it but failed.");
		$this->respond(false);
	}

	private function getConfigs() {
                $configs = $this->loadConfigData();
                $this->respond(true, null, $configs);
        }
	
	//======================//
	//    MISC FUNCTIONS    //
	//======================//
	
	private function checkIsOnline() {
		$this->respond(checkdnsrr("wifipineapple.com", "A"));
	}
	
	private function activateAuthPHP($injectSet) {
		if (copy(__INJECTS__ . $injectSet . "/auth.php", "/www/nodogsplash/auth.php")) {
			$this->respond(true);
			return;
		}
		$this->respond(false);
	}
	
	private function refreshAuthLog() {
		$this->respond(true, null, file_get_contents('/www/nodogsplash/auth.log'));
	}

	private function respond($success, $msg = null, $data = null) {
		$this->response = array("success" => $success,"message" => $msg, "data" => $data);
	}
	
	//======================//
	//    MAC COLLECTION    //
	//======================//
	
	private function scanNetwork() {
		$data = array();
		$res = exec(__SCRIPTS__ . "scan.sh", $data);
		if ($res == "failed") {
			$this->logError("mac_scan_error", implode("\r\n",$data));
			$this->respond(false);
		} else {
			$this->response(true, null, implode(";",$data));
		}
	}
	
	private function allowsMACCollection() {
		$configs = $this->loadConfigData();
		if ($configs['mac_collection'] == "") {
			$this->respond(false);
			return;
		}
		$this->respond(true);
	}
	
	private function saveCapturedMACs($macs) {
		$fh = fopen(__INCLUDES__ . "macs.txt", "w+");
		fwrite($fh, $macs);
		fclose($fh);
	}
	
	private function getCapturedMACs() {
		$this->respond(true, null, file_get_contents(__INCLUDES__ . "macs.txt"));
	}
	
	//========================//
	//    PORTAL FUNCTIONS    //
	//========================//
	
	private function portalExists() {
		$configs = $this->loadConfigData();
		if (strcmp(file_get_contents($configs['testSite']), $configs['dataExpected']) == 0) {
			$this->respond(false);
		} else {
			$this->respond(true);
		}
	}
	
	function clonePortal($name, $opts, $injectionSet, $activate = false) {
		$configs = $this->loadConfigData();
		if ($this->clonedPortalExists($name)) {
			// Delete the current portal
			$this->rrmdir($configs['p_archive'] . $name);
		}
		$data = array();
		$res = exec("python " . __SCRIPTS__ . "portalclone.py " . $name . " " . $configs['p_archive'] . " '" . opts . "' " . $configs['testSite'] . " '" . $injectionSet . "' 2>&1", $data);
		if ($res == "Complete") {
			if ($activate) {
				if (!$this->activatePortal($name, $injectionSet)) {
					$this->respond(true, "Portal cloned successfully but failed to activate.");
					return;
				}
			}
			$this->respond(true);
			return;
		}
		$this->logError("clone_error", implode("\r\n",$data));
		$this->respond(false);
	}
	
	private function clonedPortalExists($name) {
		$configs = $this->loadConfigData();
		if (file_exists($configs['p_archive'] . $name)) {
			$this->respond(true);
			return true;
		}
		$this->respond(false);
		return false;
	}
	
	private function autoAuthenticate() {
		$configs = $this->loadConfigData();
		$args = implode(" ", explode(";", $configs['tags']));
		$data = array();
		$res = exec("python " . __SCRIPTS__ . "portalauth.py " . $args . " 2>&1", $data);
		if ($res != "Complete") {
			$this->logError("auto_auth_error", implode("\r\n",$data));
		}
		$this->respond($res);
		return $res;
	}
	
	private function activatePortal($name, $injectSet) {
		$configs = $this->loadConfigData();
		$data = array();
		$res = exec(__SCRIPTS__ . "activateportal.sh " . $configs['p_archive'] . " " . $name . " " . $injectSet, $data);
		if ($res == "") {
			return true;
		}
		$this->logError("Activate_Portal_Error", implode("\r\n",$data));
		return false;
	}
	
	//======================//
	//    PASS FUNCTIONS    //
	//======================//
	
	private function startServer() {
		$ret = exec("python " . __PASSSRV__ . " > /dev/null 2>&1 &");
		if ($this->getPID() != false) {
			$dt = array();
			exec("date +'%m/%d/%Y %T'", $dt);
			$fh = fopen(__PASSLOG__, "a");
			fwrite($fh, "[!] " . $dt[0] . " - Starting server...\r\n");
			fclose($fh);
			$this->respond(true);
			return true;
		}
		$this->logError("PASS_Server", "Failed to start server.");
		$this->respond(false);
		return false;
	}
	
	private function stopServer() {
		$pid = $this->getPID();
		if ($pid != false) {
			$ret = exec("kill " . $pid);
			if ($this->getPID() != false) {
				$this->logError("PASS_Server", "Failed to stop PASS server.  PID = " . $pid);
				$this->respond(false);
				return false;
			}
		}
		$dt = array();
		exec("date +'%m/%d/%Y %T'", $dt);
		$fh = fopen(__PASSLOG__, "a");
		fwrite($fh, "[!] " . $dt[0] . " - Server stopped\r\n");
		fclose($fh);
		$this->respond(true);
		return true;
	}
	
	private function getPID() {
		$data = array();
		$ret = exec("pgrep -lf pass.py", $data);
		$output = explode(" ", $data[0]);
		if ($output[1] == "python") {
			$this->respond(true, null, $output[0]);
			return $output[0];
		}
		$this->respond(false);
		return false;
	}
	
	private function loadPASSCode() {
		$data = file_get_contents(__PASSSRV__);
		if (!$data) {
			$this->respond(false);
			return false;
		}
		$this->respond(true, null, $data);
		return $data;
	}
	
	//===========================//
	//    FILE SAVE FUNCTIONS    //
	//===========================//
	
	private function saveClonerFile($filename, $data) {
		$fh = fopen($filename, "w+");
		if ($fh) {
			fwrite($fh, $data);
			fclose($fh);
			$this->respond(true);
			return true;
		}
		$this->respond(false);
		return false;
	}
	
	private function saveConfigData($data) {
		$fh = fopen(__CONFIG__, "w+");
		if ($fh) {
			foreach ($data as $key => $value) {
				fwrite($fh, $key . "=" . $value . "\n");
			}
			fclose($fh);
			$this->respond(true);
			return true;
		}
		$this->respond(false);
		return false;
	}
	
	private function loadConfigData() {
		$configs = array();
		$config_file = fopen(__CONFIG__, "r");
		if ($config_file) {
			while (($line = fgets($config_file)) !== false) {
				$item = explode("=", $line);
				$key = $item[0]; $val = trim($item[1]);
				$configs[$key] = $val;
			}
		}
		fclose($config_file);
		return $configs;
	}
	
	private function backupFile($fileName, $backupFile) {
		// Attempt to create a backups directory in case it doesn't exist
		mkdir(dirname($backupFile));
		if (copy($fileName, $backupFile)) {
			$this->respond(true);
			return true;
		}
		$this->respond(false);
		return false;
	}
	
	//==================================//
	//    FILE RESTORATION FUNCTIONS    //
	//==================================//
	
	private function restoreFile($oldFile, $newFile) {
		$fileData = file_get_contents($newFile);
		if ($fileData) {
			$this->saveClonerFile($oldFile, $fileData);
			$this->respond(true, null, $fileData);
			return $fileData;
		}
		$this->respond(false);
		return false;
	}
	
	//===============================//
	//    INJECTION SET FUNCTIONS    //
	//===============================//
	
	private function createInjectionSet($setName) {
		// Check if the directory exists
		if (file_exists(__INJECTS__ . $setName)) {
			$this->respond(false, "Injection set already exists");
			return "Injection set already exists";
		}
	
		// Create a directory for the set
		if (!mkdir(__INJECTS__ . $setName)) {
			$this->logError("createInjectionSet", "Failed to create directory structure");
			$this->respond(false);
			return false;
		}
		// Create each of the Inject files
		$files = ["/injectJS.txt","/injectCSS.txt","/injectHTML.txt","/auth.php"];
		foreach ($files as $file) {
			$fh = fopen(__INJECTS__ . $setName . $file, "w+");
			if (!$fh) {
				$this->logError("createInjectionSetFiles", "Failed to create " . $setName . $file);
				fclose($fh);
				$this->respond(false);
				return false;
			}
			fwrite($fh, "// Replace me with your code //");
			fclose($fh);
		}
		$this->respond(true);
		return true;
	}
	
	private function exportInjectionSet($setName) {
		$data = array();
		$res = exec(__SCRIPTS__ . "packInjectionSet.sh " . $setName, $data);
		if ($res != "Complete") {
			$this->logError("Injection_Set_Export", $data);
			$this->respond(false);
			return false;
		}
		$file = __INCLUDES__ . "downloads/" . $setName . ".tar.gz";
		$this->respond(true, null, $this->downloadFile($file));
		return true;
	}
	
	private function fetchAvailableInjectionSets() {
		$data = array();
		$res = exec("python " . __SCRIPTS__ . "retrieveInjectionSetLinks.py", $data);
		$html = "<table class='pa_config_table'>";
		foreach ($data as $row) {
			$cells = explode(";", $row);
			$html .= "<tr><td><a href='#' class='pa_externalInjectionSet' url='" . $cells[1] . "'>Download</a></td><td><h2 class='pa_h2' style='display:inline'>" . $cells[0] . "</h2></td></tr><tr><td colspan='2'><hr /></td></tr>";
		}
		$html .= "</table>";
		$this->respond(true, null, $html);
		return $html;
	}
	
	private function downloadExternalInjectionSet($url) {
		$data = array();
		$res = exec(__SCRIPTS__ . "downloadExternalInjectionSet.sh " . $url, $data);
		if ($res != "Complete") {
			$this->respond(false);
			return false;
		}
		$this->respond(true);
		return true;
	}
	
	private function importInjectionSet($file) {
		$data = array();
		if ($this->importPayload($file, __INJECTS__)) {
			exec(__SCRIPTS__ . "unpackInjectionSet.sh " . $file['name'], $data);
			$this->respond(true);
			return;
		}
		$this->respond(false);
	}
	
	private function getInjectionSets() {
		$dirs = scandir(__INJECTS__);
		array_shift($dirs); array_shift($dirs);
		array_unshift($dirs, "Select...");
		$this->respond(true, null, $dirs);
	}
	
	private function getInjectCode($set) {
		$failed = false;
		$injectFiles = array();
		if (!$injectFiles['injectjs'] = $this->getInjectionFile("injectJS.txt", $set)){
			$failed = true;
		}
		if (!$injectFiles['injectcss'] = $this->getInjectionFile("injectCSS.txt", $set)) {
			$failed = true;
		}
		if (!$injectFiles['injecthtml'] = $this->getInjectionFile("injectHTML.txt", $set)) {
			$failed = true;
		}
		if (!$injectFiles['authphp'] = $this->getInjectionFile("auth.php", $set)) {
			$failed = true;
		}
		if ($failed) {
			$this->respond(false, null, $injectFiles);
		}
		$this->respond(true, null, $injectFiles);
	}
	
	private function getInjectionFile($fileName, $setName) {
		if (file_exists(__INJECTS__ . $setName . "/" . $fileName)) {
			return file_get_contents(__INJECTS__ . $setName . "/" . $fileName);
		}
		return false;
	}
	
	private function deleteInjectionSet($setName) {
		$this->rrmdir(__INJECTS__ . $setName);
		if (is_dir(__INJECTS__ . $setName)) {
			$this->respond(false);
			return false;
		}
		$this->respond(true);
		return true;
	}
	
	//================================//
	//    PAYLOAD UPLOAD FUNCTIONS    //
	//================================//

	private function importPayload($file, $dir) {	
		// Check if the directory exists, if not then create it
		if (!file_exists($dir)) {
			if (!mkdir($dir, 0755, true)) {
				$this->logError("payload_upload_error", "The following directory does not exist and could not be created\n" . $dir);
				$this->respond(false);
				return false;
			}
		}
		$file['name'] = str_replace(array( '(', ')' ), '', $file['name']);
		$uploadfile = $dir . basename($file['name']);
		if (move_uploaded_file($file['tmp_name'], $uploadfile)) {
			$this->respond(true);
			return true;
		}
		$this->logError("payload_upload_error", "The destination directory exists but for an unknown reason the file failed to upload.  This is most likely because you are still using the default upload limit in nginx.conf and php.ini.  Go to the Payload tab -> NetClient tab and click on the link 'Configure Upload Limit'.");
		$this->respond(false);
		return false;
	}
	
	private function cfgUploadLimit() {
		$data = array();
		$res = exec("python " . __SCRIPTS__ . "cfgUploadLimit.py > /dev/null 2>&1 &", $data);
		if ($res != "") {
			$this->logError("cfg_upload_limit_error", $data);
			$this->respond(false);
			return false;
		}
		$this->respond(true);
		return true;
	}
	
	//=========================================//
	//    ACTIVITY AND TARGET LOG FUNCTIONS    //
	//=========================================//
	
	private function clearLog($log) {
		if ($log == "activity") {
			$fh = fopen(__PASSLOG__, "w+");
			fclose($fh);
			$this->respond(true, null, file_get_contents(__PASSLOG__));
			return file_get_contents(__PASSLOG__);
		} else if ($log == "targets") {
			$fh = fopen(__TARGETLOG__, "w+");
			fclose($fh);
			$this->respond(true, null, file_get_contents(__TARGETLOG__));
			return file_get_contents(__TARGETLOG__);
		}
	}
	
	private function downloadLog($log) {
		if ($log == "activity") {
			$this->respond(true, null, $this->downloadFile(__PASSLOG__));
		} else if ($log == "targets") {
			$this->respond(true, null, $this->downloadFile(__TARGETLOG__));
		}
	}
	
	//===========================//
	//    ERROR LOG FUNCTIONS    //
	//===========================//

	private function logError($filename, $data) {
		$time = exec("date +'%H_%M_%S'");
		$fh = fopen(__LOGS__ . $filename . "_" . $time . ".txt", "w+");
		fwrite($fh, $data);
		fclose($fh);
	}

	private function getLogs($type) {
		$dir = ($type == "error") ? __LOGS__ : __CHANGELOGS__;
		$contents = array();
		foreach (scandir($dir) as $log) {
			if ($log == "." || $log == "..") {continue;}
			array_push($contents, $log);
		}
		$this->respond(true, null, $contents);
	}

	private function retrieveLog($logname, $type) {
        switch($type) {
                case "error":
                    $dir = __LOGS__;
                    break;
                case "help":
                    $dir = __HELPFILES__;
                    break;
                case "change":
                    $dir = __CHANGELOGS__;
                    break;
                case "pass":
                    $dir = __PASSDIR__;
                    break;
        }
		$data = file_get_contents($dir . $logname);
		if (!$data) {
			$this->respond(false);
			return;
		}
		$this->respond(true, null, $data);
	}

	private function deleteLog($logname) {
		$res = unlink(__LOGS__ . $logname);
		$this->respond($res);
		return $res;
	}

	private function rrmdir($dir) {
		if (is_dir($dir)) {
			$objects = scandir($dir);
			foreach ($objects as $object) {
				if ($object != "." && $object != "..") {
					if (filetype($dir."/".$object) == "dir") {
						$this->rrmdir($dir."/".$object);
					} else {
						unlink($dir."/".$object);
					}
				}
			}
			reset($objects);
			rmdir($dir);
		}
	}
}
