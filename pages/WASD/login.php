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

namespace WASD;
use WASD\functions\Account;
use function WASD\functions\escape;
use function WASD\functions\fetchAssoc;
use function WASD\functions\t;
require_once $_SERVER["DOCUMENT_ROOT"] . "/../WASD/functions/utils.php";
if($_SESSION["wasd"]["account"] !== false){
	functions\redirect("./");
}
if(isset($_POST["uname"], $_POST["hash"])){
	$uname = escape($_POST["uname"]);
	$hash = escape($_POST["hash"]);
	$row = fetchAssoc("SELECT registerdate,logindate,lastip FROM simpleauth_players WHERE name=$uname AND hash=$hash");
	if(is_array($row)){
		$_SESSION["wasd"]["account"] = $acc = new Account;
		$acc->name = $uname;
		$acc->lastIp = $row["lastip"];
		$acc->registerDate = $row["registerdate"];
		$acc->loginDate = $row["logindate"];
		$acc->webLogin = microtime(true);
		functions\redirect("./");
	}
	$ip = escape($_SERVER["REMOTE_ADDR"]);
	$now = time();
	functions\getDb()->query("INSERT INTO wasd_brute_force(ip,lastattempt)VALUES($ip,$now)ON DUPLICATE KEY UPDATE lastattempt=$now");
	$row = fetchAssoc("SELECT unix_timestamp()-unix_timestamp(lastattempt)AS diff FROM wasd_brute_force WHERE ip=$ip");
	if(is_array($row) and $row["diff"] < 5){
		printf(t("login.brute-force-msg"), 5 - $row["diff"]);
		header("Content-Type: text/plain");
		die;
	}
	$wrongLogin = true;
}
?>
<html class="login">
<head>
	<title><?= functions\getConfig("website.name") ?></title>
	<style rel="stylesheet" type="text/css"><?= functions\getResource("style.css"); ?></style>
	<?= jquery ?>
	<?= jsSHA ?>
	<?= cryptofoo ?>
	<!--suppress JSPotentiallyInvalidConstructorUsage -->
	<script>
		function onFormSubmit(){
			var pass = $("#input_pass");
			var user = $("#imput_uname");
			var shaObj = new jsSHA(pass.val() + user.val(), "TEXT");
			var sha512 = shaObj.getHash("SHA-512", "BYTES");
//			var whirlpool = Whirlpool(pass.val());
			var whirlpool = hex2binb(cryptofoo.hash("whirlpool", user.val() + pass.val())).value;
			pass.val(binb2hex(sha512 ^ whirlpool));
		}
		/** copied fom jsSHA */
		function hex2binb(str){
			var bin = [], length = str.length, i, num, offset;
			if(0 !== (length % 2)){
				throw "String of HEX type must be in byte increments";
			}
			for(i = 0; i < length; i += 2){
				num = parseInt(str.substr(i, 2), 16);
				if(!isNaN(num)){
					offset = i >>> 3;
					while(bin.length <= offset){
						bin.push(0);
					}
					bin[i >>> 3] |= num << (24 - (4 * (i % 8)));
				}else{
					throw "String of HEX type contains invalid characters";
				}
			}
			return {"value" : bin, "binLen" : length * 4};
		}
		function binb2hex(binarray){
			var hex_tab = "0123456789abcdef", str = "", length = binarray.length * 4, i, srcByte;
			for(i = 0; i < length; i += 1){
				/* The below is more than a byte but it gets taken care of later */
				srcByte = binarray[i >>> 2] >>> ((3 - (i % 4)) * 8);
				str += hex_tab.charAt((srcByte >>> 4) & 0xF) +
				hex_tab.charAt(srcByte & 0xF);
			}
			return str.toUpperCase();
		}
	</script>
</head>
<body class="login">
<h1 class="title"><?= t("login.headline") ?></h1>
<?= isset($wrongLogin) ? "<p></p>":"" ?>
<form method="post">
	<label for="input_uname"><?= t("login.username") ?></label><input type="text" name="uname" id="input_uname" class="input"><br>
	<label for="input_pass"><?= t("login.password") ?></label><input type="password" name="hash" id="input_pass" class="input password"><br>
	<input type="submit" value="<?= t("login.submit") ?>" class="button submit" onclick="onFormSubmit();">
</form>
</body>
</html>
