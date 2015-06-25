<?php

$defaultalgo = user()->getState('yaamp-algo');

echo "<div class='main-left-box'>";
echo "<div class='main-left-title'>Pool Status</div>";
echo "<div class='main-left-inner'>";

//echo "<table class='dataGrid2'>";
showTableSorter('maintable1');
echo "<thead>";
echo "<tr>";
echo "<th>Algo</th>";
echo "<th align=right>Port</th>";
echo "<th align=right>Coins</th>";
echo "<th align=right>Miners</th>";
echo "<th align=right>Hashrate</th>";
echo "<th align=right>Fees**</th>";
echo "<th align=right>Current<br>Estimate</th>";
//echo "<th>Norm</th>";
echo "<th align=right>24 Hours<br>Estimated</th>";
echo "<th align=right>24 Hours<br>Actual</th>";
echo "</tr>";
echo "</thead>";

$best_algo = '';
$best_norm = 0;

$algos = array();
foreach(yaamp_get_algos() as $algo)
{
	$algo_norm = yaamp_get_algo_norm($algo);

	$price = controller()->memcache->get_database_scalar("current_price-$algo",
		"select price from hashrate where algo=:algo order by time desc limit 1", array(':algo'=>$algo));

	$norm = $price*$algo_norm;
	$norm = take_yaamp_fee($norm, $algo);
	
	$algos[] = array($norm, $algo);

	if($norm > $best_norm)
	{
		$best_norm = $norm;
		$best_algo = $algo;
	}
}

function cmp($a, $b)
{
	return $a[0] < $b[0];
}

usort($algos, 'cmp');

$total_coins = 0;
$total_miners = 0;

echo "<tbody>";
foreach($algos as $item)
{
	$norm = $item[0];
	$algo = $item[1];
	
	$coins = getdbocount('db_coins', "enable and visible and auto_ready and algo=:algo", array(':algo'=>$algo));
	$count = getdbocount('db_workers', "algo=:algo", array(':algo'=>$algo));
	
	$hashrate = controller()->memcache->get_database_scalar("current_hashrate-$algo",
		"select hashrate from hashrate where algo=:algo order by time desc limit 1", array(':algo'=>$algo));
	$hashrate = $hashrate? Itoa2($hashrate).'h/s': '-';
	
	$price = controller()->memcache->get_database_scalar("current_price-$algo",
		"select price from hashrate where algo=:algo order by time desc limit 1", array(':algo'=>$algo));
	
	$price = $price? mbitcoinvaluetoa(take_yaamp_fee($price, $algo)): '-';
	$norm = mbitcoinvaluetoa($norm);
	
	$t = time() - 24*60*60;

	$avgprice = controller()->memcache->get_database_scalar("current_avgprice-$algo",
		"select avg(price) from hashrate where algo=:algo and time>$t", array(':algo'=>$algo));
	$avgprice = $avgprice? mbitcoinvaluetoa(take_yaamp_fee($avgprice, $algo)): '-';
	
	$total1 = controller()->memcache->get_database_scalar("current_total-$algo",
		"select sum(amount*price) from blocks where category!='orphan' and time>$t and algo=:algo", array(':algo'=>$algo));
	
	$hashrate1 = controller()->memcache->get_database_scalar("current_hashrate1-$algo",
		"select avg(hashrate) from hashrate where time>$t and algo=:algo", array(':algo'=>$algo));
	
//	$btcmhday1 = $hashrate1 != 0? mbitcoinvaluetoa($total1 / $hashrate1 * 1000000 * 1000): '-';
	if($algo == 'sha256')
		$btcmhday1 = $hashrate1 != 0? mbitcoinvaluetoa($total1 / $hashrate1 * 1000000 * 1000000): '';
	else
		$btcmhday1 = $hashrate1 != 0? mbitcoinvaluetoa($total1 / $hashrate1 * 1000000 * 1000): '';
	
	$fees = yaamp_fee($algo);
	$port = getAlgoPort($algo);
	
	if($defaultalgo == $algo)
 		echo "<tr style='cursor: pointer; background-color: #e0d3e8;' onclick='javascript:select_algo(\"$algo\")'>";
	else
		echo "<tr style='cursor: pointer' class='ssrow' onclick='javascript:select_algo(\"$algo\")'>";
	
	echo "<td><b>$algo</b></td>";
	echo "<td align=right style='font-size: .8em;'>$port</td>";
	echo "<td align=right style='font-size: .8em;'>$coins</td>";
	echo "<td align=right style='font-size: .8em;'>$count</td>";
	echo "<td align=right style='font-size: .8em;'>$hashrate</td>";
	echo "<td align=right style='font-size: .8em;'>{$fees}%</td>";

	if($algo == $best_algo)
		echo "<td align=right style='font-size: .8em;' title='normalized $norm'><b>$price*</b></td>";
	else if($norm>0)
		echo "<td align=right style='font-size: .8em;' title='normalized $norm'><b>$price</b></td>";
	else
		echo "<td align=right style='font-size: .8em;'><b>$price</b></td>";
	
	echo "<td align=right style='font-size: .8em;'>$avgprice</td>";
	echo "<td align=right style='font-size: .8em;'>$btcmhday1</td>";
	echo "</tr>";
	
	$total_coins += $coins;
	$total_miners += $count;
}

echo "</tbody>";

if($defaultalgo == 'all')
	echo "<tr style='cursor: pointer; background-color: #e0d3e8;' onclick='javascript:select_algo(\"all\")'>";
else
	echo "<tr style='cursor: pointer' class='ssrow' onclick='javascript:select_algo(\"all\")'>";

echo "<td><b>all</b></td>";
echo "<td></td>";
echo "<td align=right style='font-size: .8em;'>$total_coins</td>";
echo "<td align=right style='font-size: .8em;'>$total_miners</td>";
echo "<td></td>";
echo "<td></td>";
echo "<td></td>";
echo "<td></td>";
echo "<td></td>";
echo "</tr>";

echo "</table>";

echo "<p style='font-size: .8em'>
		&nbsp;* best normalized multi algo<br>
		&nbsp;** additional 2% for BTC payouts<br>
		&nbsp;*** values in mBTC/Mh/day (mBTC/Gh/day for sha256)<br>
		</p>";

echo "</div></div><br>";
	





