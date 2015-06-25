<?php

$percent = 16;
$algo = user()->getState('yaamp-algo');

$step = 15*60;
$t = time() - 24*60*60;

$stats = getdbolist('db_hashrate', "time>$t and algo=:algo order by time", array(':algo'=>$algo));
$averages = array();

echo '[[';

for($i = 0; $i < 95-count($stats); $i++)
{
	$d = date('Y-m-d H:i:s', $t);
	echo "[\"$d\",0],";
	
	$t += $step;
	$averages[] = array($d, 0);
}

foreach($stats as $i=>$n)
{
	$m = $n->price;
	if($i) echo ',';
	
	$d = date('Y-m-d H:i:s', $n->time);
	echo "[\"$d\",$m]";

	$averages[] = array($d, $m);
}

echo '],[';

$average = $averages[0][1];
foreach($averages as $i=>$n)
{
	if($i) echo ',';
	
	$average = ($average*(100-$percent) + $n[1]*$percent) / 100;
	$m = round($average, 5);
	
	echo "[\"{$n[0]}\",$m]";
}

// $a = 10;
// foreach($averages as $i=>$n)
// {
// 	if($i < $a) continue;
// 	if($i > $a) echo ',';

// 	$average = 0;
// 	for($j = $i-$a+1; $j<=$i; $j++)
// 		$average += $averages[$j][1]/$a;

// 	$m = round($average, 3);

// 	echo "[\"{$n[0]}\",$m]";
// }

echo ']]';






