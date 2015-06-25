<?php

function BackendPayments()
{
	$list = getdbolist('db_coins', "enable and id in (select distinct coinid from accounts)");
	foreach($list as $coin)
		BackendCoinPayments($coin);
	
	dborun("update accounts set balance=0 where coinid=0");
}

function BackendCoinPayments($coin)
{
//	debuglog("BackendCoinPayments $coin->symbol");
	$remote = new Bitcoin($coin->rpcuser, $coin->rpcpasswd, $coin->rpchost, $coin->rpcport);

	$info = $remote->getinfo();
	if(!$info)
	{
		debuglog("$coin->symbol cant connect to coin");
		return;
	}

	$min = 0.001;
	
// 	if(date("w", time()) == 0 && date("H", time()) > 12)		// sunday afternoon
// 		$min = 0.0001;
	
	$users = getdbolist('db_accounts', "balance>$min and coinid=$coin->id");
	
	if($coin->symbol == 'MUE' || $coin->symbol == 'DIME')
	{
		foreach($users as $user)
		{
			$user = getdbo('db_accounts', $user->id);
			if(!$user) continue;
			
			$amount = $user->balance;
			while($user->balance > $min && $amount > $min)
			{
				debuglog("$coin->symbol sendtoaddress $user->username $amount");
				$tx = $remote->sendtoaddress($user->username, round($amount, 8));
				if(!$tx)
				{
					debuglog("error $remote->error, $user->username, $amount");
					if($remote->error == 'transaction too large' || $remote->error == 'invalid amount')
					{
						$amount /= 2;
						continue;
					}
					
					break;
				}
	
				$payout = new db_payouts;
				$payout->account_id = $user->id;
				$payout->time = time();
				$payout->amount = bitcoinvaluetoa($amount);
				$payout->fee = 0;
				$payout->tx = $tx;
				$payout->save();
	
				$user->balance -= $amount;
				$user->save();
			}
		}
		
		debuglog("payment done");
		return;
	}
	
	$total_to_pay = 0;
	$addresses = array();

	foreach($users as $user)
	{
		$total_to_pay += round($user->balance, 8);
		$addresses[$user->username] = round($user->balance, 8);
	}

	if(!$total_to_pay)
	{
	//	debuglog("nothing to pay");
		return;
	}
	
	if($info['balance']-0.001 < $total_to_pay)
	{
		debuglog("$coin->symbol wallet insufficient funds for payment {$info['balance']} < $total_to_pay");
		return;
	}

	if($coin->symbol=='BTC')
	{
		global $cold_wallet_table;
		
		$balance = $info['balance'];
		$stats = getdbosql('db_stats', "1 order by time desc");
		
		$renter = dboscalar("select sum(balance) from renters");
		$pie = $balance - $total_to_pay - $renter - 1;
		
		debuglog("pie to split is $pie");
		if($pie>0)
		{
			foreach($cold_wallet_table as $coldwallet=>$percent)
			{
				$coldamount = round($pie * $percent, 8);
				if($coldamount < $min) break;

				debuglog("paying cold wallet $coldwallet $coldamount");
				
				$addresses[$coldwallet] = $coldamount;
				$total_to_pay += $coldamount;
			}
		}
	}
	
	debuglog("paying $total_to_pay $coin->symbol min is $min");
	
	$tx = $remote->sendmany('', $addresses, 1, '');
	if(!$tx)
	{
		debuglog($remote->error);
		return;
	}

	foreach($users as $user)
	{
		$user = getdbo('db_accounts', $user->id);
		if(!$user) continue;
		
		$payout = new db_payouts;
		$payout->account_id = $user->id;
		$payout->time = time();
		$payout->amount = bitcoinvaluetoa($user->balance);
		$payout->fee = 0;
		$payout->tx = $tx;
		$payout->save();
			
		$user->balance = 0;
		$user->save();
	}
	
	debuglog("payment done");
	sleep(5);
}






