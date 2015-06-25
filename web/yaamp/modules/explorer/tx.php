<?php

$remote = new Bitcoin($coin->rpcuser, $coin->rpcpasswd, $coin->rpchost, $coin->rpcport);

echo "<table class='dataGrid2'>";

echo "<thead>";
echo "<tr>";
echo "<th>Transaction Hash</th>";
echo "<th>Value</th>";
echo "<th>From (amount)</th>";
echo "<th>To (amount)</th>";
echo "</tr>";
echo "</thead>";

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








