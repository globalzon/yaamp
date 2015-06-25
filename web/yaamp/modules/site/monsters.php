
<a href='/site/common'>Summary</a>&nbsp;
<a href='/site/admin'>Coins</a>&nbsp;
<a href='/site/exchange'>Exchange</a>&nbsp;
<a href='/site/user?symbol=BTC'>Users</a>&nbsp;
<a href='/site/worker'>Workers</a>&nbsp;
<a href='/site/version'>Version</a>&nbsp;
<a href='/site/earning'>Earnings</a>&nbsp;
<a href='/site/payments'>Payments</a>&nbsp;
<a href='/site/monsters'>Big Miners</a>&nbsp;
<a href='/site/emptymarkets'>EmptyMarket</a>&nbsp;

<?php

echo "<a href='/site/monsters'>refresh</a><br>";

echo "<br><table class='dataGrid'>";
echo "<thead>";
echo "<tr>";
echo "<th></th>";
echo "<th></th>";
echo "<th>Wallet</th>";
echo "<th></th>";
echo "<th>Last</th>";
echo "<th>Blocks</th>";
echo "<th>Balance</th>";
echo "<th>Total Paid</th>";
echo "<th>Miners</th>";
echo "<th>Shares</th>";
echo "<th></th>";
echo "<th></th>";
echo "</tr>";
echo "</thead><tbody>";

function showUser($userid, $what)
{
	$user = getdbo('db_accounts', $userid);
	if(!$user) return;

	$d = datetoa2($user->last_login);
	$balance = bitcoinvaluetoa($user->balance);
	$paid = dboscalar("select sum(amount) from payouts where account_id=$user->id");
	$paid = bitcoinvaluetoa($paid);
	
	$t = time()-24*60*60;

	$miner_count = getdbocount('db_workers', "userid=$user->id");
	$share_count = getdbocount('db_shares', "userid=$user->id");
	$block_count = getdbocount('db_blocks', "userid=$user->id and time>$t");
	
	$coin = getdbo('db_coins', $user->coinid);

	echo "<tr class='ssrow'>";
	
	if($coin)
		echo "<td><img src='$coin->image' width=16> $coin->symbol</td>";
	else
		echo "<td></td>";
	
	echo "<td>$user->id</td>";
	echo "<td><a href='/site?address=$user->username'>$user->username</a></td>";
	echo "<td>$what</td>";
	echo "<td>$d</td>";
	
	echo "<td>$block_count</td>";
	echo "<td>$balance</td>";
	
	if(intval($paid) > 0.01)
		echo "<td><b>$paid</b></td>";
	else
		echo "<td>$paid</td>";
	
	echo "<td>$miner_count</td>";
	echo "<td>$share_count</td>";

	if($user->is_locked)
	{
		echo "<td>locked</td>";
		echo "<td><a href='/site/unblockuser?wallet=$user->username'>unblock</a></td>";
	}
	
	else
	{
		echo "<td></td>";
		echo "<td><a href='/site/blockuser?wallet=$user->username'>block</a></td>";
	}
	
	echo "</tr>";
}

$t = time()-24*60*60;

$list = dbolist("select userid from shares where pid is null or pid not in (select pid from stratums) group by userid");
foreach($list as $item)
	showUser($item['userid'], 'pid');

$list = dbolist("select id from accounts where balance>0.001 and id not in (select distinct userid from blocks where userid is not null and time>$t)");
foreach($list as $item)
	showUser($item['id'], 'blocks');

$monsters = dbolist("SELECT COUNT(*) AS total, userid FROM workers GROUP BY userid ORDER BY total DESC LIMIT 5");
foreach($monsters as $item)
	showUser($item['userid'], 'miners');

$monsters = dbolist("SELECT COUNT(*) AS total, workerid FROM shares GROUP BY workerid ORDER BY total DESC LIMIT 5");
foreach($monsters as $item)
{
	$worker = getdbo('db_workers', $item['workerid']);
	if(!$worker) continue;

	showUser($worker->userid, 'shares');
}

$list = getdbolist('db_accounts', "is_locked");
foreach($list as $user)
	showUser($user->id, 'locked');

echo "</tbody></table>";











