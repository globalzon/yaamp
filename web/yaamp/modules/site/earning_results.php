<?php

$earnings = getdbolist('db_earnings', "status!=2 order by create_time limit 500");

echo "<br><table class='dataGrid'>";
//showTableSorter('maintable');
echo "<thead>";
echo "<tr>";
echo "<th width=20></th>";
echo "<th>Name</th>";
echo "<th>Wallet</th>";
//echo "<th>Status</th>";
//echo "<th>Amount</th>";
echo "<th>Quantity</th>";
echo "<th>Block</th>";
echo "<th>Status</th>";
echo "<th>Sent</th>";
echo "<th></th>";
echo "</tr>";
echo "</thead><tbody>";

foreach($earnings as $earning)
{
//	if(!$earning) debuglog($earning);
	$coin = getdbo('db_coins', $earning->coinid);
	if(!$coin) continue;
	
	$user = getdbo('db_accounts', $earning->userid);
	if(!$user) continue;
	
	$block = getdbo('db_blocks', $earning->blockid);
	if(!$block) continue;
	
	$t1 = datetoa2($earning->create_time). ' ago';
	$t2 = datetoa2($earning->mature_time). ' ago';
	
	echo "<tr class='ssrow'>";
	echo "<td><img width=16 src='$coin->image'></td>";
	echo "<td><b>$coin->name ($coin->symbol_show)</b></td>";
	echo "<td><b>$user->username</b></td>";
	echo "<td>$earning->amount</td>";
	echo "<td>$block->height</td>";
	echo "<td>$block->category ($block->confirmations) $earning->status</td>";
	echo "<td>$t1 $t2</td>";

	echo "<td>
		<a href='/site/clearearning?id=$earning->id'>[clear]</a>
		<a href='/site/deleteearning?id=$earning->id'>[delete]</a>
		</td>";
	
//	echo "<td style='font-size: .7em'>$earning->tx</td>";
	echo "</tr>";
	
// 	if($block->category == 'generate' && $earning->status == 0)
// 	{
// 		$earning->status = 1;
// 		$earning->mature_time = time()-100*60;
// 		$earning->save();
// 	}
}

echo "</tbody></table>";












