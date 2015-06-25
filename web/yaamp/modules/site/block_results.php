<?php

$id = getiparam('id');
if($id)
	$db_blocks = getdbolist('db_blocks', "coin_id=:id order by time desc limit 250", array(':id'=>$id));
else
	$db_blocks = getdbolist('db_blocks', "1 order by time desc limit 250");

echo "<table class='dataGrid'>";
//showTableSorter('maintable');
echo "<thead>";
echo "<tr>";
echo "<th width=20></th>";
echo "<th>Name</th>";
echo "<th>Time</th>";
echo "<th>Height</th>";
echo "<th>Amount</th>";
echo "<th>Status</th>";
echo "<th>Difficulty</th>";
echo "<th>Found Diff</th>";
echo "<th>Blockhash</th>";
echo "</tr>";
echo "</thead><tbody>";

foreach($db_blocks as $db_block)
{
	if(!$db_block->coin_id) continue;
	
	$coin = getdbo('db_coins', $db_block->coin_id);
	if(!$coin) continue;
	
//	$remote = new Bitcoin($coin->rpcuser, $coin->rpcpasswd, $coin->rpchost, $coin->rpcport);
	
// 	$blockext = $remote->getblock($db_block->blockhash);
// 	$tx = $remote->gettransaction($blockext['tx'][0]);
	
// 	$db_block->category = $tx['details'][0]['category'];
	
	if($db_block->category == 'immature')
		echo "<tr style='background-color: #e0d3e8;'>";
	else
		echo "<tr class='ssrow'>";
		
	echo "<td><img width=16 src='$coin->image'></td>";
	echo "<td><b>$coin->name ($coin->symbol)</b></td>";

//	$db_block->confirmations = $blockext['confirmations'];
//	$db_block->save();
	
	$d = datetoa2($db_block->time);
	echo "<td><b>$d ago</b></td>";
	
	echo "<td>$db_block->height</td>";
	echo "<td>$db_block->amount</td>";
	
	echo "<td>";
	
	if($db_block->category == 'orphan')
		echo "Orphan";
	
	else if($db_block->category == 'immature')
		echo "Immature ($db_block->confirmations)";
		
	else if($db_block->category == 'generate')
		echo 'Confirmed';
		
	echo "</td>";
	
	echo "<td>$db_block->difficulty</td>";
	echo "<td>$db_block->difficulty_user</td>";
	
	echo "<td style='font-size: .8em; font-family: monospace;'><a href='/explorer?id=$coin->id&hash=$db_block->blockhash'>$db_block->blockhash</a></td>";
	echo "</tr>";
}

echo "</tbody></table>";









