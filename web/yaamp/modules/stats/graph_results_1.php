<?php

$algo = user()->getState('yaamp-algo');

$t = time() - 48*60*60;
$stats = getdbolist('db_hashstats', "time>$t and algo=:algo", array(':algo'=>$algo));

echo '[';

foreach($stats as $i=>$n)
{
	$m = round($n->hashrate/1000000, 3);
	
	if($i) echo ',';
	$d = date('Y-m-d H:i:s', $n->time);
	echo "[\"$d\",$m]";
}

echo ']';

