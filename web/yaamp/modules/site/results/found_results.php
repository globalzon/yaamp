<?php

function WriteBoxHeader($title)
{
	echo "<div class='main-left-box'>";
	echo "<div class='main-left-title'>$title</div>";
	echo "<div class='main-left-inner'>";
}

$algo = user()->getState('yaamp-algo');

$count = getparam('count');
$count = $count? $count: 50;

WriteBoxHeader("Last $count Blocks ($algo)");

if($algo == 'all')
	$db_blocks = getdbolist('db_blocks', "1 order by time desc limit :count", array(':count'=>$count));
else
	$db_blocks = getdbolist('db_blocks', "algo=:algo order by time desc limit :count", array(':algo'=>$algo, ':count'=>$count));

echo "<table class='dataGrid2'>";
echo "<thead>";
echo "<tr>";
echo "<td></td>";
echo "<th>Name</th>";
echo "<th align=right>Amount</th>";
echo "<th align=right>Difficulty</th>";
echo "<th align=right>Block</th>";
echo "<th align=right>Time</th>";
echo "<th align=right>Status</th>";
echo "</tr>";
echo "</thead>";

foreach($db_blocks as $db_block)
{
	$d = datetoa2($db_block->time);
	if(!$db_block->coin_id)
	{
		$reward = bitcoinvaluetoa($db_block->amount);
		
		echo "<tr class='ssrow'>";
		echo "<td width=18><img width=16 src='/images/btc.png'></td>";
		echo "<td><b>Rental</b><span style='font-size: .8em'> ($db_block->algo)</span></td>";
		echo "<td align=right style='font-size: .8em'><b>$reward BTC</b></td>";
		echo "<td align=right style='font-size: .8em'></td>";
		echo "<td align=right style='font-size: .8em'></td>";
		echo "<td align=right style='font-size: .8em'>$d ago</td>";
		echo "<td align=right style='font-size: .8em'>";
		echo "<span style='padding: 2px; color: white; background-color: #5cb85c'>Confirmed</span>";
		echo "</td>";
		echo "</tr>";
	
		continue;
	}
	
	$reward = round($db_block->amount, 3);
	$coin = getdbo('db_coins', $db_block->coin_id);
	$difficulty = Itoa2($db_block->difficulty, 3);
	$height = number_format($db_block->height, 0, '.', ' ');
	$url = "/explorer?id=$coin->id&hash=$db_block->blockhash";
	
	echo "<tr class='ssrow'>";
	echo "<td width=18><img width=16 src='$coin->image'></td>";
	echo "<td><b><a href='$url'>$coin->name</a></b><span style='font-size: .8em'> ($coin->algo)</span></td>";
	echo "<td align=right style='font-size: .8em'><b>$reward $coin->symbol_show</b></td>";
	echo "<td align=right style='font-size: .8em' title='found $db_block->difficulty_user'>$difficulty</td>";
	echo "<td align=right style='font-size: .8em'>$height</td>";
	echo "<td align=right style='font-size: .8em'>$d ago</td>";
	echo "<td align=right style='font-size: .8em'>";

	if($db_block->category == 'orphan')
		echo "<span style='padding: 2px; color: white; background-color: #d9534f'>Orphan</span>";

	else if($db_block->category == 'immature')
		echo "<span style='padding: 2px; color: white; background-color: #f0ad4e'>Immature ($db_block->confirmations)</span>";

	else if($db_block->category == 'generate')
		echo "<span style='padding: 2px; color: white; background-color: #5cb85c'>Confirmed</span>";

	else if($db_block->category == 'new')
		echo "<span style='padding: 2px; color: white; background-color: #ad4ef0'>New</span>";
	
	echo "</td>";
	echo "</tr>";
}

echo "</table>";

echo "<br></div></div><br>";




