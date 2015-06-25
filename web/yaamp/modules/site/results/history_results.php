<?php

$mining = getdbosql('db_mining');
$algo = user()->getState('yaamp-algo');
if($algo == 'all') return;

echo "<div class='main-left-box'>";
echo "<div class='main-left-title'>Pool Stats ($algo)</div>";
echo "<div class='main-left-inner'>";

echo "<table class='dataGrid2'>";
echo "<thead>";
echo "<tr>";
echo "<th></th>";
echo "<th>Name</th>";
echo "<th align=right>Last Hour</th>";
echo "<th align=right>Last 24 Hours</th>";
echo "<th align=right>Last 7 Days</th>";
echo "<th align=right>Last 30 Days</th>";
echo "</tr>";
echo "</thead>";

$t1 = time() - 60*60;
$t2 = time() - 24*60*60;
$t3 = time() - 7*24*60*60;
$t4 = time() - 30*24*60*60;

$total1 = 0;
$total2 = 0;
$total3 = 0;
$total4 = 0;

$algo = user()->getState('yaamp-algo');
$list = dbolist("SELECT coin_id FROM blocks where category!='orphan' and time>$t4 and coin_id in (select id from coins where algo=:algo) group by coin_id order by id desc", array(':algo'=>$algo));

foreach($list as $item)
{
	$coin = getdbo('db_coins', $item['coin_id']);
	if($coin->symbol == 'BTC') continue;
	
	$res1 = controller()->memcache->get_database_row("history_item1-$coin->id-$algo",
		"select count(*) as a, sum(amount*price) as b from blocks where category!='orphan' and time>$t1 and coin_id=$coin->id and algo=:algo", array(':algo'=>$algo));
	
	$res2 = controller()->memcache->get_database_row("history_item2-$coin->id-$algo",
		"select count(*) as a, sum(amount*price) as b from blocks where category!='orphan' and time>$t2 and coin_id=$coin->id and algo=:algo", array(':algo'=>$algo));
	
	$res3 = controller()->memcache->get_database_row("history_item3-$coin->id-$algo",
		"select count(*) as a, sum(amount*price) as b from blocks where category!='orphan' and time>$t3 and coin_id=$coin->id and algo=:algo", array(':algo'=>$algo));
		
	$res4 = controller()->memcache->get_database_row("history_item4-$coin->id-$algo",
		"select count(*) as a, sum(amount*price) as b from blocks where category!='orphan' and time>$t4 and coin_id=$coin->id and algo=:algo", array(':algo'=>$algo));
	
	$total1 += $res1['b'];
	$total2 += $res2['b'];
	$total3 += $res3['b'];
	$total4 += $res4['b'];
	
	$name = substr($coin->name, 0, 12);
	
	echo "<tr class='ssrow'>";

	echo "<td width=18><img width=16 src='$coin->image'></td>";
	echo "<td><b><a href='/site/block?id=$coin->id'>$name</a></b></td>";
	
	echo "<td align=right style='font-size: .9em;'>{$res1['a']}</td>";
	echo "<td align=right style='font-size: .9em;'>{$res2['a']}</td>";
	echo "<td align=right style='font-size: .9em;'>{$res3['a']}</td>";
	echo "<td align=right style='font-size: .9em;'>{$res4['a']}</td>";
	
	echo "</tr>";
}

///////////////////////////////////////////////////////////////////////

$hashrate1 = controller()->memcache->get_database_scalar("history_hashrate1-$algo",
	"select avg(hashrate) from hashrate where time>$t1 and algo=:algo", array(':algo'=>$algo));

$hashrate2 = controller()->memcache->get_database_scalar("history_hashrate2-$algo",
	"select avg(hashrate) from hashrate where time>$t2 and algo=:algo", array(':algo'=>$algo));

$hashrate3 = controller()->memcache->get_database_scalar("history_hashrate3-$algo",
	"select avg(hashrate) from hashrate where time>$t3 and algo=:algo", array(':algo'=>$algo));

$hashrate4 = controller()->memcache->get_database_scalar("history_hashrate4-$algo",
	"select avg(hashrate) from hashstats where time>$t4 and algo=:algo", array(':algo'=>$algo));

$hashrate1 = max($hashrate1 , 1);
$hashrate2 = max($hashrate2 , 1);
$hashrate3 = max($hashrate3 , 1);
$hashrate4 = max($hashrate4 , 1);

$btcmhday1 = mbitcoinvaluetoa($total1 / $hashrate1 * 1000000 * 24 * 1000);
$btcmhday2 = mbitcoinvaluetoa($total2 / $hashrate2 * 1000000 * 1 * 1000);
$btcmhday3 = mbitcoinvaluetoa($total3 / $hashrate3 * 1000000 / 7 * 1000);
$btcmhday4 = mbitcoinvaluetoa($total4 / $hashrate4 * 1000000 / 30 * 1000);

$hashrate1 = Itoa2($hashrate1);
$hashrate2 = Itoa2($hashrate2);
$hashrate3 = Itoa2($hashrate3);
$hashrate4 = Itoa2($hashrate4);

$total1 = bitcoinvaluetoa($total1);
$total2 = bitcoinvaluetoa($total2);
$total3 = bitcoinvaluetoa($total3);
$total4 = bitcoinvaluetoa($total4);

echo "<tr class='ssrow' style='border-top: 2px solid #eee;'>";
echo "<td width=18><img width=16 src='/images/btc.png'></td>";
echo "<td><b>BTC Value</b></td>";

echo "<td align=right style='font-size: .9em;'>$total1</td>";
echo "<td align=right style='font-size: .9em;'>$total2</td>";
echo "<td align=right style='font-size: .9em;'>$total3</td>";
echo "<td align=right style='font-size: .9em;'>$total4</td>";

echo "</tr>";

///////////////////////////////////////////////////////////////////////

echo "<tr class='ssrow' style='border-top: 2px solid #eee;'>";
echo "<td width=18></td>";
echo "<td><b>Avg Hashrate</b></td>";

echo "<td align=right style='font-size: .9em;'>{$hashrate1}h/s</td>";
echo "<td align=right style='font-size: .9em;'>{$hashrate2}h/s</td>";
echo "<td align=right style='font-size: .9em;'>{$hashrate3}h/s</td>";
echo "<td align=right style='font-size: .9em;'>{$hashrate4}h/s</td>";

echo "</tr>";

///////////////////////////////////////////////////////////////////////

echo "<tr class='ssrow' style='border-top: 2px solid #eee;'>";
echo "<td width=18></td>";
echo "<td><b>mBTC/Mh/d</b></td>";

echo "<td align=right style='font-size: .9em;'>$btcmhday1</td>";
echo "<td align=right style='font-size: .9em;'>$btcmhday2</td>";
echo "<td align=right style='font-size: .9em;'>$btcmhday3</td>";
echo "<td align=right style='font-size: .9em;'>$btcmhday4</td>";

echo "</tr>";

echo "</table>";


echo "</div>";

echo "<br>";
echo "</div></div><br>";
	





