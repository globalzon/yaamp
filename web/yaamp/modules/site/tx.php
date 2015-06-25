<?php

require dirname(__FILE__).'/../../ui/lib/pageheader.php';

$user = getuserparam(getparam('address'));
if(!$user) return;

$this->pageTitle = "$user->username | yaamp.com";

$bitcoin = getdbosql('db_coins', "symbol='BTC'");

echo "<div class='main-left-box'>";
echo "<div class='main-left-title'>Transactions to $user->username</div>";
echo "<div class='main-left-inner'>";

$list = getdbolist('db_payouts', "account_id=$user->id order by time desc");

echo "<table  class='dataGrid2'>";

echo "<thead>";
echo "<tr>";
echo "<th></th>";
echo "<th>Time</th>";
echo "<th align=right>Amount</th>";
echo "<th>Tx</th>";
echo "</tr>";
echo "</thead>";

$total = 0;
foreach($list as $payout)
{
	$d = datetoa2($payout->time);
	$amount = bitcoinvaluetoa($payout->amount);
	
	echo "<tr class='ssrow'>";
	echo "<td width=18></td>";
	echo "<td><b>$d ago</b></td>";
	
	echo "<td align=right><b>$amount</b></td>";

	if($user->coinid == $bitcoin->id)
		echo "<td style='font-family: monospace;'><a href='https://blockchain.info/tx/$payout->tx' target=_blank>$payout->tx</a></td>";
	else
		echo "<td style='font-family: monospace;'><a href='/explorer?id=$user->coinid&txid=$payout->tx' target=_blank>$payout->tx</a></td>";
	
	echo "</tr>";
	$total += $payout->amount;
}

$total = bitcoinvaluetoa($total);

echo "<tr class='ssrow' style='border-top: 2px solid #eee;'>";
echo "<td width=18></td>";
echo "<td><b>Total</b></td>";

echo "<td align=right><b>$total</b></td>";
echo "<td></td>";

echo "</tr>";

echo "</table><br>";
echo "</div></div><br>";


