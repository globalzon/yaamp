<?php

function WriteBoxHeader($title)
{
	echo "<div class='main-left-box'>";
	echo "<div class='main-left-title'>$title</div>";
	echo "<div class='main-left-inner'>";
}

$algo = user()->getState('yaamp-algo');

$user = getuserparam(getparam('address'));
if(!$user || $user->is_locked) return;

$count = getparam('count');
$count = $count? $count: 50;

WriteBoxHeader("Last $count Earnings: $user->username");
$earnings = getdbolist('db_earnings', "userid=$user->id order by create_time desc limit :count", array(':count'=>$count));

echo "<table class='dataGrid2'>";
echo "<thead>";
echo "<tr>";
echo "<td></td>";
echo "<th>Name</th>";
echo "<th align=right>Amount</th>";
echo "<th align=right>Percent</th>";
echo "<th align=right>mBTC</th>";
echo "<th align=right>Time</th>";
echo "<th align=right>Status</th>";
echo "</tr>";
echo "</thead>";

foreach($earnings as $earning)
{
	$coin = getdbo('db_coins', $earning->coinid);
	$block = getdbo('db_blocks', $earning->blockid);
	
	$d = datetoa2($earning->create_time);
	if(!$coin)
	{
		$reward = bitcoinvaluetoa($earning->amount);
		$value = altcoinvaluetoa($earning->amount*1000);
		$percent = $block? mbitcoinvaluetoa($earning->amount*100/$block->amount): '';
		$algo = $block? $block->algo: '';
		
		echo "<tr class='ssrow'>";
		echo "<td width=18><img width=16 src='/images/btc.png'></td>";
		echo "<td><b>Rental</b><span style='font-size: .8em'> ($algo)</span></td>";
		echo "<td align=right style='font-size: .8em'><b>$reward BTC</b></td>";
		echo "<td align=right style='font-size: .8em'>{$percent}%</td>";
		echo "<td align=right style='font-size: .8em'>$value</td>";
		echo "<td align=right style='font-size: .8em'>$d ago</td>";
		echo "<td align=right style='font-size: .8em'>Cleared</td>";
		echo "</tr>";

		continue;
	}
	
	$reward = altcoinvaluetoa($earning->amount);
	$percent = mbitcoinvaluetoa($earning->amount*100/$block->amount);
	$value = altcoinvaluetoa($earning->amount*$earning->price*1000);
	
	echo "<tr class='ssrow'>";
	echo "<td width=18><img width=16 src='$coin->image'></td>";
	echo "<td><b>$coin->name</b><span style='font-size: .8em'> ($coin->algo)</span></td>";
	echo "<td align=right style='font-size: .8em'><b>$reward $coin->symbol_show</b></td>";
	echo "<td align=right style='font-size: .8em'>{$percent}%</td>";
	echo "<td align=right style='font-size: .8em'>$value</td>";
	echo "<td align=right style='font-size: .8em'>$d ago</td>";
	echo "<td align=right style='font-size: .8em'>";

	if($earning->status == 0)
		echo "Immature ($block->confirmations)";

	else if($earning->status == 1)
		echo 'Exchange';
	
	else if($earning->status == 2)
		echo 'Cleared';

	echo "</td>";
	echo "</tr>";
}

echo "</table>";

echo "<br></div></div><br>";




