<?php

function BackendBlockNew($coin, $db_block)
{
//	debuglog("NEW BLOCK $coin->name $db_block->height");
	$reward = $db_block->amount;
	
	$total_hash_power = dboscalar("select sum(difficulty) from shares where valid and algo='$coin->algo'");
	if(!$total_hash_power) return;
	
	$list = dbolist("SELECT userid, sum(difficulty) as total from shares where valid and algo='$coin->algo' group by userid");
	foreach($list as $item)
	{
		$hash_power = $item['total'];
		if(!$hash_power) continue;
		
		$user = getdbo('db_accounts', $item['userid']);
		if(!$user) continue;

		$amount = $reward * $hash_power / $total_hash_power;
		if(!$user->no_fees) $amount = take_yaamp_fee($amount, $coin->algo);

		$earning = new db_earnings;
		$earning->userid = $user->id;
		$earning->coinid = $coin->id;
		$earning->blockid = $db_block->id;
		$earning->create_time = $db_block->time;
		$earning->amount = $amount;
		$earning->price = $coin->price;
		
		if($db_block->category == 'generate')
		{
			$earning->mature_time = time();
			$earning->status = 1;
		}
		else	// immature
			$earning->status = 0;
		
		$earning->save();

		$user->last_login = time();
		$user->save();
	}

	$delay = time() - 5*60;
	dborun("delete from shares where algo='$coin->algo' and time<$delay");
}

/////////////////////////////////////////////////////////////////////////////////////////////////

function BackendBlockFind1()
{
//	debuglog(__METHOD__);
	$list = getdbolist('db_blocks', "category='new' order by time");
	foreach($list as $db_block)
	{
		$coin = getdbo('db_coins', $db_block->coin_id);
		if(!$coin->enable) continue;

		$db_block->category = 'orphan';
		$remote = new Bitcoin($coin->rpcuser, $coin->rpcpasswd, $coin->rpchost, $coin->rpcport);
			
		$block = $remote->getblock($db_block->blockhash);
		if(!$block || !isset($block['tx']) || !isset($block['tx'][0]))
		{
			$db_block->save();
			continue;
		}
			
		$tx = $remote->gettransaction($block['tx'][0]);
		if(!$tx || !isset($tx['details']) || !isset($tx['details'][0]))
		{
			$db_block->save();
			continue;
		}
			
		$db_block->txhash = $block['tx'][0];
		$db_block->category = 'immature';						//$tx['details'][0]['category'];
		$db_block->amount = $tx['details'][0]['amount'];
		$db_block->confirmations = $tx['confirmations'];
		$db_block->price = $coin->price;
		$db_block->save();

		if($db_block->category != 'orphan')
			BackendBlockNew($coin, $db_block);
	}
}

/////////////////////////////////////////////////////////////////////////////////

function BackendBlocksUpdate()
{
//	debuglog(__METHOD__);
	$t1 = microtime(true);
	
	$list = getdbolist('db_blocks', "category='immature' order by time");
	foreach($list as $block)
	{
		$coin = getdbo('db_coins', $block->coin_id);
		if(!$coin || !$coin->enable)
		{
			$block->delete();
			continue;
		}

		$remote = new Bitcoin($coin->rpcuser, $coin->rpcpasswd, $coin->rpchost, $coin->rpcport);
		if(empty($block->txhash))
		{
			$blockext = $remote->getblock($block->blockhash);
			if(!$blockext || !isset($blockext['tx'][0])) continue;
			
			$block->txhash = $blockext['tx'][0];
		}
		
		$tx = $remote->gettransaction($block->txhash);
		if(!$tx) continue;
		
		$block->confirmations = $tx['confirmations'];
			
		if($block->confirmations == -1)
			$block->category = 'orphan';
		
		else if(isset($tx['details']) && isset($tx['details'][0]))
			$block->category = $tx['details'][0]['category'];

		else if(isset($tx['category']))
			$block->category = $tx['category'];

		$block->save();
			
		if($block->category == 'generate')
			dborun("update earnings set status=1, mature_time=UNIX_TIMESTAMP() where blockid=$block->id");
		
		else if($block->category != 'immature')
			dborun("delete from earnings where blockid=$block->id");
	}

	$d1 = microtime(true) - $t1;
	controller()->memcache->add_monitoring_function(__METHOD__, $d1);
}

////////////////////////////////////////////////////////////////////////////////////////////

function BackendBlockFind2()
{
	$coins = getdbolist('db_coins', "enable");
	foreach($coins as $coin)
	{
		if($coin->symbol == 'BTC') continue;
		$remote = new Bitcoin($coin->rpcuser, $coin->rpcpasswd, $coin->rpchost, $coin->rpcport);
			
		$mostrecent = 0;
		if($coin->lastblock == null) $coin->lastblock = '';
		$list = $remote->listsinceblock($coin->lastblock);
		if(!$list) continue;

//		debuglog("find2 $coin->symbol");
		foreach($list['transactions'] as $transaction)
		{
			if($transaction['time'] > time() - 5*60) continue;
			if(!isset($transaction['blockhash'])) continue;

			if($transaction['time'] > $mostrecent)
			{
				$coin->lastblock = $transaction['blockhash'];
				$mostrecent = $transaction['time'];
			}

			if($transaction['time'] < time() - 60*60) continue;
			if($transaction['category'] != 'generate' && $transaction['category'] != 'immature') continue;

			$blockext = $remote->getblock($transaction['blockhash']);
			
			$db_block = getdbosql('db_blocks', "blockhash='{$transaction['blockhash']}' or height={$blockext['height']}");
			if($db_block) continue;

//			debuglog("adding lost block $coin->name {$blockext['height']}");

			$db_block = new db_blocks;
			$db_block->blockhash = $transaction['blockhash'];
			$db_block->coin_id = $coin->id;
			$db_block->category = 'immature';			//$transaction['category'];
			$db_block->time = $transaction['time'];
			$db_block->amount = $transaction['amount'];
			$db_block->confirmations = $transaction['confirmations'];
			$db_block->height = $blockext['height'];
			$db_block->difficulty = $blockext['difficulty'];
			$db_block->price = $coin->price;
			$db_block->algo = $coin->algo;
			$db_block->save();

			BackendBlockNew($coin, $db_block);
		}
			
		$coin->save();
	}
}

function MonitorBTC()
{
//	debuglog(__FUNCTION__);
	
	$coin = getdbosql('db_coins', "symbol='BTC'");
	if(!$coin) return;
	
	$remote = new Bitcoin($coin->rpcuser, $coin->rpcpasswd, $coin->rpchost, $coin->rpcport);
	if(!$remote) return;
	
	$mostrecent = 0;
	if($coin->lastblock == null) $coin->lastblock = '';
	$list = $remote->listsinceblock($coin->lastblock);
	if(!$list) return;

	$coin->lastblock = $list['lastblock'];
	$coin->save();
	
	foreach($list['transactions'] as $transaction)
	{
		if(!isset($transaction['blockhash'])) continue;
		if($transaction['confirmations'] == 0) continue;
		if($transaction['category'] != 'send') continue;
		if($transaction['fee'] != -0.0001) continue;
		
		debuglog(__FUNCTION__);
		debuglog($transaction);
		
		$txurl = "https://blockchain.info/tx/{$transaction['txid']}";
		
		$b = mail('yaamp201@gmail.com', "withdraw {$transaction['amount']}", 
			"<a href='$txurl'>{$transaction['address']}</a>");
		
		if(!$b) debuglog('error sending email');
		
	}
		
	
}






