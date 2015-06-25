<?php

$algo = user()->getState('yaamp-algo');

echo "<br><table class='dataGrid'>";
echo "<thead>";
echo "<tr>";
echo "<th>Wallet</th>";
echo "<th>Pass</th>";
echo "<th>Client</th>";
echo "<th>Version</th>";
echo "<th>Diff</th>";
echo "<th>Hashrate</th>";
echo "<th>Bad</th>";
echo "<th></th>";
//echo "<th>Nonce1</th>";
echo "</tr>";
echo "</thead><tbody>";

$workers = getdbolist('db_workers', "algo=:algo order by name", array(':algo'=>$algo));
foreach($workers as $worker)
{
	$user_rate = yaamp_worker_rate($worker->id);
	$user_bad = yaamp_worker_rate_bad($worker->id);
	$percent = ($user_rate+$user_bad)? round($user_bad*100/($user_rate+$user_bad), 3): 0;
	$user_rate = Itoa2($user_rate).'h/s';
	$user_bad = Itoa2($user_bad).'h/s';
	
	$dns = !empty($worker->dns)? $worker->dns: $worker->ip;
	if(strlen($worker->dns) > 40)
		$dns = '...'.substr($worker->dns, strlen($worker->dns) - 40);
	
	echo "<tr class='ssrow'>";
	echo "<td><a href='/?address=$worker->name'><b>$worker->name</b></a></td>";
	echo "<td>$worker->password</td>";
	echo "<td title='$worker->ip'>$dns</td>";
	echo "<td>$worker->version</td>";
	echo "<td>$worker->difficulty</td>";
	echo "<td>$user_rate</td>";
	echo "<td>$user_bad</td>";
	
	if($percent > 50)
		echo "<td align=right><b>{$percent}%</b></td>";
	else
		echo "<td align=right>{$percent}%</td>";
	
//	echo "<td>$worker->nonce1</td>";
	echo "</tr>";
}

echo "</tbody></table>";




