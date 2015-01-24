<?php

namespace Kitsune\ClubPenguin;

date_default_timezone_set("America/Los_Angeles");

error_reporting(E_ALL ^ E_STRICT);

spl_autoload_register(function ($path) {
	$realPath = str_replace("\\", "/", $path) . ".php";
	$includeSuccess = include_once $realPath;
	
	if(!$includeSuccess) {
		echo "Unable to load $realPath\n";
	}
});

$cp = new Login();

// A simple example of binding to multiple ports and/or addresses
// $cp->listen(["127.0.0.1", "192.168.1.159"], [6112, 7432])
// $cp->listen(["127.0.0.1"], [6112, 7432])

$cp->listen(0, 6112);

while(true) {
	$cp->acceptClients();
}

?>