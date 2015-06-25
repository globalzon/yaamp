<?php

echo "<br><table class='dataGrid'>";
echo "<thead>";
echo "<tr>";
echo "<th>Coin</th>";
echo "<th>Market</th>";
echo "<th>Price</th>";
echo "<th>Message</th>";
echo "<th>Deposit</th>";
echo "</tr>";
echo "</thead><tbody>";

$list = dbolist("SELECT coins.id as coinid, markets.id as marketid FROM coins, markets WHERE coins.installed AND 
	coins.id=markets.coinid AND (markets.deposit_address IS NULL or (message is not null and message!='')) order by markets.id desc");
foreach($list as $item)
{
	$coin = getdbo('db_coins', $item['coinid']);
	$market = getdbo('db_markets', $item['marketid']);
	
	echo "<tr class='ssrow'>";
	echo "<td><a href='/site/coin?id=$coin->id'>$coin->name</a></td>";
	echo "<td>$market->name</td>";
	echo "<td>$market->price</td>";
	echo "<td>$market->message</td>";
	echo "<td>$market->deposit_address</td>";
	echo "</tr>";
}

echo "</tbody></table>";

echo '<br><br><br><br><br><br><br><br><br><br>';
echo '<br><br><br><br><br><br><br><br><br><br>';

