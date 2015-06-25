<?php

require_once('serverconfig.php');
require_once('yaamp/ui/app.php');

if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
	$_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_X_FORWARDED_FOR'];

if(0)
{
	debuglog("{$_SERVER['REMOTE_ADDR']}, {$_SERVER['REQUEST_URI']}");
}

try
{
	$app->run();
}

catch(CException $e)
{
//	Javascript("window.history.go(-1)");
//	mydump($e, 3);

	debuglog("front end error ".$_SERVER['REMOTE_ADDR']);
	debuglog($e->getMessage());
	
//	send_email_alert('frontend', "frontend error", "a frontend error occured");
}


