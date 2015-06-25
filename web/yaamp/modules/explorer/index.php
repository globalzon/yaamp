<?php

echo "<br>";
echo "<div class='main-left-box'>";
echo "<div class='main-left-title'>Block Explorer</div>";
echo "<div class='main-left-inner'>";

showTableSorter('maintable', '{headers: {0: {sorter: false}, 9: {sorter: false}}}');
echo "<thead>";

echo "<tr>";
echo "<th width=30></th>";
echo "<th>Name</th>";
echo "<th>Symbol</th>";
echo "<th>Algo</th>";
echo "<th>Version</th>";
echo "<th>Height</th>";
echo "<th>Difficulty</th>";
echo "<th>Connections</th>";
echo "<th>Network Hash</th>";
echo "<th></th>";
echo "</tr>";
echo "</thead><tbody>";

$list = getdbolist('db_coins', "enable and visible order by name");
foreach($list as $coin)
{
	if($coin->symbol == 'BTC') continue;
	if(!empty($coin->symbol2)) continue;
	
	$coin->version = substr($coin->version, 0, 20);
	$difficulty = Itoa2($coin->difficulty, 3);
	$nethash = $coin->network_hash? Itoa2($coin->network_hash).'h': '';
	
	echo "<tr class='ssrow'>";
	echo "<td><img src='$coin->image' width=18></td>";
	
	echo "<td><b><a href='/explorer?id=$coin->id'>$coin->name</a></b></td>";
	echo "<td><b>$coin->symbol</b></td>";
	
	echo "<td>$coin->algo</td>";
	echo "<td>$coin->version</td>";

	echo "<td>$coin->block_height</td>";
	echo "<td>$difficulty</td>";
	echo "<td>$coin->connections</td>";
	echo "<td>$nethash</td>";
	
	echo "<td>";

	if(!empty($coin->link_bitcointalk))
		echo "<a href='$coin->link_bitcointalk' target=_blank>forum</a> ";

	echo "</td>";
	echo "</tr>";
}

echo "</tbody>";
echo "</table>";

echo "<br></div></div>";

echo '<br><br><br><br><br><br><br><br><br><br>';
echo '<br><br><br><br><br><br><br><br><br><br>';
echo '<br><br><br><br><br><br><br><br><br><br>';


