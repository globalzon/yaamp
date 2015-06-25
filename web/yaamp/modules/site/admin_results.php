<?php

/////////////////////////////////////////////////////////////////////////////////////

echo "<br><table class='dataGrid'>";
//showTableSorter('maintable');
echo "<thead class=''>";

echo "<tr>";
echo "<th width=30></th>";
echo "<th></th>";
echo "<th>Name</th>";

echo "<th>Server</th>";

echo "<th align=right>Diff/Height</th>";
echo "<th align=right>Profit</th>";
echo "<th align=right>Owed/BTC</th>";
echo "<th align=right>Balance/BTC</th>";
echo "<th align=right>Mint/BTC</th>";
echo "<th align=right>Price</th>";
echo "<th align=right>Win/Market</th>";

echo "</tr>";
echo "</thead><tbody>";

$current_algo = '';

$server = getparam('server');
if(!empty($server))
{
	$coins = getdbolist('db_coins', "(installed or enable) and rpchost=:server order by algo, index_avg desc", 
		array(':server'=>$server));
}
else
	$coins = getdbolist('db_coins', "installed or enable order by algo, index_avg desc");

$total = count($coins);
echo "<tr>";
echo "<td colspan=2></td>";
echo "<td colspan=9>$total Coins</td>";
echo "</tr>";

foreach($coins as $coin)
{
	if($coin->algo != $current_algo)
	{
		$current_algo = $coin->algo;
		echo "<tr class='ssrow' id='$current_algo'>";
	}
	else
		echo "<tr class='ssrow'>";

	$lowsymbol = strtolower($coin->symbol);
	echo "<td><img src='$coin->image' width=24></td>";

	$algo_color = getAlgoColors($coin->algo);
	echo "<td style='background-color:$algo_color;'><b>";
		
	if($coin->enable)
	{
		echo "u";
		if($coin->auto_ready) echo "<span style='color: green;'> a</span>";
		else echo "<span style='color: red;'> d</span>";

		echo '<br>';
		
		if($coin->visible) echo "v";
		else echo '&nbsp;';
		
		if($coin->auxpow) echo " x";
		
		if($coin->block_height < $coin->target_height)
		{
			$percent = round($coin->block_height*100/$coin->target_height, 2);
			echo "<br><span style='font-size: .8em'>$percent%</span>";
		}
	}
		
	echo "</b></td>";
	$version = substr($coin->version, 0, 25);
	
	echo "<td><b><a href='/site/coin?id=$coin->id'>$coin->name ($coin->symbol)</a></b>
		<br><span style='font-size: .8em'>$version ($coin->symbol2)</span></td>";

	echo "<td>$coin->rpchost:$coin->rpcport";
	if($coin->connections) echo " ($coin->connections)";
	echo "<br><span style='font-size: .8em'>$coin->rpcencoding <span style='background-color:$algo_color;'>&nbsp; ($coin->algo) &nbsp;</span></span></td>";
	
	$difficulty = Itoa2($coin->difficulty, 3);
	
	if(!empty($coin->errors))
		echo "<td align=right style='color: red; font-size: .9em;' title='$coin->errors'><b>$difficulty</b><br>$coin->block_height</td>";
	else
		echo "<td align=right style='font-size: .9em'><b>$difficulty</b><br>$coin->block_height</td>";

// 	$network_ttf = $coin->network_ttf? sectoa($coin->network_ttf): '';
// 	$actual_ttf = $coin->actual_ttf? sectoa($coin->actual_ttf): '';
// 	$pool_ttf = $coin->pool_ttf? sectoa($coin->pool_ttf): '';
	$btcmhd = yaamp_profitability($coin);
	$btcmhd = mbitcoinvaluetoa($btcmhd);
	
	$h = $coin->block_height-100;
	$ss1 = dboscalar("select count(*) from blocks where coin_id=$coin->id and height>=$h and category!='orphan'");
	$ss2 = dboscalar("select count(*) from blocks where coin_id=$coin->id and height>=$h and category='orphan'");
	
	$percent_pool1 = $ss1? $ss1.'%': '';
	$percent_pool2 = $ss2? $ss2.'%': '';
	
// 	echo "<td align=right style='font-size: .9em'>$network_ttf<br>$actual_ttf</td>";
// 	echo "<td align=right style='font-size: .9em'>$pool_ttf<br></td>";
	
	if($ss1 > 50)
		echo "<td align=right style='font-size: .9em'><b>$btcmhd</b><br><span style='color: blue;'>$percent_pool1</span>";
	else
		echo "<td align=right style='font-size: .9em'><b>$btcmhd</b><br>$percent_pool1";

	echo "<span style='color: red;'> $percent_pool2</span></td>";

	$owed = dboscalar("select sum(balance) from accounts where coinid=$coin->id");
	$owed_btc = bitcoinvaluetoa($owed*$coin->price);
	$owed = bitcoinvaluetoa($owed);
	
	if($coin->balance+$coin->mint < $owed)
		echo "<td align=right style='font-size: .9em'><span style='color: red;'>$owed<br>$owed_btc</span></td>";
	else
		echo "<td align=right style='font-size: .9em'>$owed<br>$owed_btc</td>";
	
	$btc = bitcoinvaluetoa($coin->balance*$coin->price);
	echo "<td align=right style='font-size: .9em'>$coin->balance<br>$btc</td>";
	
	$btc = bitcoinvaluetoa($coin->mint*$coin->price);
	echo "<td align=right style='font-size: .9em'>$coin->mint<br>$btc</td>";
	
	$price = bitcoinvaluetoa($coin->price);
	$price2 = bitcoinvaluetoa($coin->price2);
//	$marketcount = getdbocount('db_markets', "coinid=$coin->id");
	
	$marketname = '';
	$bestmarket = getBestMarket($coin);
	if($bestmarket)	$marketname = $bestmarket->name;
	
	if($coin->dontsell)
		echo "<td align=right style='font-size: .9em; background-color: #ffaaaa'>$price<br>$price2</td>";
	else
		echo "<td align=right style='font-size: .9em'>$price<br>$price2</td>";
	
	echo "<td align=right style='font-size: .9em'>$coin->reward<br>$marketname</td>";
	
	echo "</tr>";
}

echo "</tbody>";
echo "</table>";

//////////////////////////////////////////

echo "<br>";













