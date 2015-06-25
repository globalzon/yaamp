<?php

function simplifyscript($script)
{
	$script = preg_replace("/[0-9a-f]+ OP_DROP ?/","", $script);
	$script = preg_replace("/OP_NOP ?/","", $script);
	return trim($script);
}

///////////////////////////////////////////////////////////////////////////////////////////////

$remote = new Bitcoin($coin->rpcuser, $coin->rpcpasswd, $coin->rpchost, $coin->rpcport);

$block = $remote->getblock($hash);
if(!$block) return;
//debuglog($block);

$d = date('Y-m-d H:i:s', $block['time']);
$confirms = isset($block['confirmations'])? $block['confirmations']: '';
$txcount = count($block['tx']);

$version = dechex($block['version']);
$nonce = dechex($block['nonce']);

echo "<table class='dataGrid1'>";
echo "<tr><td width=100></td><td></td></tr>";

echo "<tr><td>Coin:</td><td><b><a href='/explorer?id=$coin->id'>$coin->name</a></b></td></tr>";
echo "<tr><td>Blockhash:</td><td><span style='font-family: monospace;'>$hash</span></td></tr>";

echo "<tr><td>Confirmations:</td><td>$confirms</td></tr>";
echo "<tr><td>Size:</td><td>{$block['size']} bytes</td></tr>";
echo "<tr><td>Height:</td><td>{$block['height']}</td></tr>";
echo "<tr><td>Time:</td><td>$d</td></tr>";
echo "<tr><td>Difficulty:</td><td>{$block['difficulty']}</td></tr>";

echo "<tr><td>Version:</td><td><span style='font-family: monospace;'>$version</span></td></tr>";
echo "<tr><td>Merkle Root:</td><td><span style='font-family: monospace;'>{$block['merkleroot']}</span></td></tr>";

echo "<tr><td>Nonce:</td><td><span style='font-family: monospace;'>$nonce</span></td></tr>";
echo "<tr><td>Bits:</td><td><span style='font-family: monospace;'>{$block['bits']}</span></td></tr>";

if(isset($block['previousblockhash']))
	echo "<tr><td>Previous Hash:</td><td><span style='font-family: monospace;'>
		<a href='/explorer?id=$coin->id&hash={$block['previousblockhash']}'>{$block['previousblockhash']}</a></span></td></tr>";

if(isset($block['nextblockhash']))
	echo "<tr><td>Next Hash:</td><td><span style='font-family: monospace;'>
		<a href='/explorer?id=$coin->id&hash={$block['nextblockhash']}'>{$block['nextblockhash']}</a></span></td></tr>";

echo "<tr><td>Transactions:</td><td>$txcount</td></tr>";

echo "</table><br>";

////////////////////////////////////////////////////////////////////////////////

echo "<table class='dataGrid2'>";

echo "<thead>";
echo "<tr>";
echo "<th>Transaction Hash</th>";
echo "<th>Value</th>";
echo "<th>From (amount)</th>";
echo "<th>To (amount)</th>";
echo "</tr>";
echo "</thead>";

foreach($block['tx'] as $txhash)
{
	$tx = $remote->getrawtransaction($txhash, 1);
	if(!$tx) continue;

	$valuetx = 0;
	foreach($tx['vout'] as $vout)
		$valuetx += $vout['value'];

	echo "<tr class='ssrow'>";
	
	echo "<td><span style='font-family: monospace;'><a href='/explorer?id=$coin->id&txid={$tx['txid']}'>{$tx['txid']}</a></span></td>";
	echo "<td>$valuetx</td>";
	
	echo "<td>";
	foreach($tx['vin'] as $vin)
	{
		if(isset($vin['coinbase']))
			echo "Generation";
		
	}
	echo "</td>";
	
	echo "<td>";
	foreach($tx['vout'] as $vout)
	{
		$value = $vout['value'];
		
		if(isset($vout['scriptPubKey']['addresses'][0]))
			echo "<span style='font-family: monospace;'>{$vout['scriptPubKey']['addresses'][0]}</span> ($value)";
		else
			echo "($value)";
		
		echo '<br>';
	}
	echo "</td>";
		
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


