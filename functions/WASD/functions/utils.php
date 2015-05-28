<?php

/**
 * WASD
 * Copyright (C) 2015 PEMapModder
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

namespace WASD\functions;
///////////
// paths //
///////////

define("WASD_RESOURCES_PATH", $_SERVER["DOCUMENT_ROOT"] . "/../WASD/");
define("WASD_FUNCTIONS_PATH", $_SERVER["DOCUMENT_ROOT"] . "/../");
define("WASD_PAGES_PATH", $_SERVER["DOCUMENT_ROOT"] . "/WASD/");
define("WASD_ERRORS_PATH", WASD_RESOURCES_PATH . "errors/");
foreach([\WASD_RESOURCES_PATH, \WASD_FUNCTIONS_PATH, \WASD_PAGES_PATH, \WASD_ERRORS_PATH] as $path){
	if(!is_dir($path)){
		mkdir($path, 0777, true);
	}
}
spl_autoload_register(function($class){
	$file = str_replace("\\", DIRECTORY_SEPARATOR, $class) . ".php";
	global $sourceRoots;
	foreach($sourceRoots as $root){
		$f = $root . $file;
		if(is_file($f)){
			require_once $f;
			return;
		}
	}
}, true, true);

////////////////////////////
// global vars and consts //
////////////////////////////
/** @noinspection PhpUnusedLocalVariableInspection */
$config = getConfig();
/** @var \mysqli|null $db */
$db = null;
/** @noinspection PhpUnusedLocalVariableInspection */
$sourceRoots = [];
/** @noinspection PhpUnusedLocalVariableInspection
 * @var Lang[] $langs */
$langs = [];
/** @noinspection PhpUnusedLocalVariableInspection
 * @var int $nextLangIndex */
$nextLangIndex = 0;
define("jquery", '<script src="//code.jquery.com/jquery-1.11.3.min.js"></script>', true);
define("jquery_ui", jquery . '<script src="//code.jquery.com/ui/1.11.4/jquery-ui.min.js"></script><link href="http://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.min.css" rel="stylesheet" />', true);
define("jsSHA", '<script src="https://raw.githubusercontent.com/Caligatio/jsSHA/v1.6.0/src/sha512.js"></script>', true);
define("jsSHAdev", '<script src="https://raw.githubusercontent.com/Caligatio/jsSHA/v1.6.0/src/sha_dev.js"></script>', true);
define("whirlpool", '<script src="https://raw.githubusercontent.com/jeffsteinport/Javascript-Whirlpool-hash/master/whirlpool.js"></script>', true);
define("cryptofoo", '<script src="https://raw.githubusercontent.com/SimonWaldherr/cryptofoo/master/cryptofoo.min.js"></script>', true);
///////////////
// functions //
///////////////
function getConfig($key = false){
	if($key !== false){
		global $config;
		if(isset($config[$key])){
			$value = $config[$key];
			return preg_replace_callback('#\$\{([a-z\.]+)\}#', function($match){
				return getConfig($match[1]);
			}, $value);
		}
		return null;
	}
	$path = $_SERVER["DOCUMENT_ROOT"] . "/../WASD/wasd.ini";
	if(!is_file($path)){
		file_put_contents($path, file_get_contents(\WASD_RESOURCES_PATH . "wasd.ini"));
	}
	$lines = array_filter(array_map("trim", explode("\n", file_get_contents($path))), function($line){
		return is_string($line) and strlen($line) > 0 and substr($line, 0, 1) !== "#";
	});
	$config = [];
	foreach($lines as $line){
		if(($pos = strpos($line, "=")) !== false){
			$config[rtrim(substr($line, 0, $pos))] = ltrim(substr($line, $pos + 1));
		}else{
			trigger_error("Delimiter \"=\" not found on line \"$line\" of $path", E_USER_WARNING);
		}
	}
	return $config;
}
function loadLang($name){
	global $langs;
	if(isset($langs[$name])){
		return $langs[$name];
	}
	return $langs[$name] = new Lang($name);
}
function t($key, $lang = false){
	global $nextLangIndex;
	if($lang === false){
		$lang = isset($_SESSION["wasd"]["langs"][$nextLangIndex++]) ? $_SESSION["wasd"]["langs"][$nextLangIndex] : "en";
	}
	$l = loadLang($lang);
	$v = $l->get($key);
	if($v === null){
		if($lang !== "en"){
			return t($key);
		}
		trigger_error("Undefined translation key: $key");
		$nextLangIndex = 0;
		return $key;
	}
	$nextLangIndex = 0;
	return $v;
}
function getResource($name){
	return is_file($name) ? file_get_contents(\WASD_RESOURCES_PATH . $name) : null;
}
function initDb(){
	global $config, $db;
	$db = new \mysqli($config["mysql.host"], $config["mysql.user"], $config["mysql.pass"], $config["mysql.schema"], $config["mysql.port"]);
	if($db->connect_error){
		suicide("MySQL connection error: $db->connect_error");
	}
	$db->query("CREATE TABLE IF NOT EXISTS wasd_brute_force (ip VARCHAR(68) PRIMARY KEY, lastattempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
}
function suicide($message){
	for($i = 1, $file = \WASD_ERRORS_PATH . "Error_" . date(DATE_ATOM) . ".$i.log"; is_file($file); $file = \WASD_ERRORS_PATH . "Error_" . date(DATE_ATOM) . "." . (++$i) . ".log");
	file_put_contents($file, date(DATE_ATOM) . PHP_EOL . "File: " . $_SERVER["SCRIPT_FILENAME"] . PHP_EOL . "Error message: " . $message . PHP_EOL . "Stack trace: " . (new \Exception)->getTraceAsString() . PHP_EOL . "\$_SERVER dump:" . PHP_EOL . var_export($_SERVER, true));
	?>
	<html>
	<head>
		<title>Error!</title>
	</head>
	<body>
	<h1>WASD crashed!</h1>
	<h3>If you are a user of this website:</h3>
	<p>Please inform the administrator of this website about this error. You may need to wait for a while before this gets fixed.</p>
	<h3>If you are the administrator of this website:</h3>
	<p>Please view the file <code><?= htmlspecialchars($file) ?></code> for error details.</p>
	</body>
	</html>
	<?php
	http_response_code(500);
	die;
}
function escape($string){
	$db = getDb();
	return is_string($string) ? "'{$db->escape_string($string)}'" : "$string";
}
/**
 * @param $query
 * @return string[]|null
 */
function fetchAssoc($query){
	$db = getDb();
	$r = $db->query($query);
	$row = $r->fetch_assoc();
	$r->close();
	return $row;
}
/**
 * @param $query
 * @return string[][]
 */
function fetchAll($query){
	$db = getDb();
	$r = $db->query($query);
	if($r === false){
		suicide("Error executing MySQL query: $query");
	}
	$out = [];
	while(is_array($row = $r->fetch_assoc())){
		$out[] = $row;
	}
	return $out;
}
function getDb(){
	global $db;
	if($db === null){
		initDb();
	}   return $db;
}
function registerSourceRoot($root){
	global $sourceRoots;
	if(!is_dir($root)){
		suicide("Root path passed must be a directory");
	}
	$sourceRoots[] = rtrim($root, "\\/") . DIRECTORY_SEPARATOR;
}
function redirect($new){
	header("Location: " . $new);
	die;
}

/////////////////
// init stuffs //
/////////////////
registerSourceRoot(\WASD_FUNCTIONS_PATH);
session_start();
$_SESSION["wasd"] = [
	"langs" => [], // English is always the automatic fallback, so don't add it here. REMEMBER TO AUTOMATICALLY SHOW IT TO CLIENTS.
	"account" => false,
];
