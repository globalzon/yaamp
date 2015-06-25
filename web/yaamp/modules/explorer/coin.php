<?php

echo "<br>";
echo "<div class='main-left-box'>";
echo "<div class='main-left-title'>$coin->name Explorer</div>";
echo "<div class='main-left-inner'>";

echo "<table  class='dataGrid2'>";

echo "<thead>";
echo "<tr>";
echo "<th>Time</th>";
echo "<th>Height</th>";
echo "<th>Diff</th>";
echo "<th>Transactions</th>";
echo "<th>Confirmations</th>";
echo "<th>Blockhash</th>";
echo "</tr>";
echo "</thead>";

$remote = new Bitcoin($coin->rpcuser, $coin->rpcpasswd, $coin->rpchost, $coin->rpcport);
for($i = $coin->block_height; $i > $coin->block_height-25; $i--)
{
	$hash = $remote->getblockhash($i);
	if(!$hash) continue;
	
	$block = $remote->getblock($hash);
	if(!$block) continue;
	
	$d = datetoa2($block['time']);
	$confirms = isset($block['confirmations'])? $block['confirmations']: '';
	$tx = count($block['tx']);
	$diff = $block['difficulty'];
	
//	debuglog($block);
	echo "<tr class='ssrow'>";
	echo "<td>$d</td>";
	echo "<td><a href='/explorer?id=$coin->id&height=$i'>$i</a></td>";
	echo "<td>$diff</td>";
	echo "<td>$tx</td>";
	echo "<td>$confirms</td>";
	echo "<td><span style='font-family: monospace;'><a href='/explorer?id=$coin->id&hash=$hash'>$hash</a></span></td>";
	
	echo "</tr>";
}

echo "</table>";

echo <<<end
<form action="/explorer" method="get" style="padding: 10px; width: 200px;">
<input type="hidden" name="id" value="$coin->id">
<input type="text" name="height" class="main-text-input" placeholder="block height">
<input type="submit" value="Submit" class="main-submit-button" >
</form>
end;

echo '<br><br><br><br><br><br><br><br><br><br>';
echo '<br><br><br><br><br><br><br><br><br><br>';
echo '<br><br><br><br><br><br><br><br><br><br>';




