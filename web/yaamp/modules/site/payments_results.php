<?php

$list = getdbolist('db_accounts', "coinid!=6 and (balance or last_login>UNIX_TIMESTAMP()-60*60) order by last_login desc limit 50");

echo "<br><table class='dataGrid'>";
echo "<thead>";
echo "<tr>";
echo "<th>Wallet</th>";
echo "<th>Last</th>";
echo "<th width=20></th>";
echo "<th>Coin</th>";
echo "<th align=right>Pool</th>";
echo "<th align=right>User</th>";
echo "</tr>";
echo "</thead><tbody>";

foreach($list as $user)
{
	$coin = getdbo('db_coins', $user->coinid);
	$balance = bitcoinvaluetoa($user->balance);
	$d = datetoa2($user->last_login);
	
	echo "<tr class='ssrow'>";
	echo "<td><a href='/?address=$user->username'><b>$user->username</b></a></td>";
	echo "<td>$d</td>";
	
	if($coin)
	{
		$coinbalance = bitcoinvaluetoa($coin->balance);
		echo "<td><img width=16 src='$coin->image'></td>";
		echo "<td><b><a href='/site/coin?id=$coin->id'>$coin->name</a></b></td>";
		echo "<td align=right>$coinbalance</td>";
	}
	else
	{
		echo "<td></td>";
		echo "<td></td>";
		echo "<td align=right></td>";
	}
	
	echo "<td align=right>$balance</td>";
	echo "</tr>";
}

echo "</tbody></table>";











