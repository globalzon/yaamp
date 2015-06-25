<?php 

function percent_feedback($v, $n, $p)
{
	return ($v*(100-$p) + $n*$p) / 100;
}

function string_to_hashrate($s)
{
	$value = floatval(trim(preg_replace('/,/', '', $s)));

	if(stripos($s, 'kh/s')) $value *= 1000;
	if(stripos($s, 'mh/s')) $value *= 1000000;
	if(stripos($s, 'gh/s')) $value *= 1000000000;

	return $value;
}

/////////////////////////////////////////////////////////////////////////////////////////////

function BackendCoinsUpdate()
{
//	debuglog(__FUNCTION__);
	$t1 = microtime(true);
	
	$pool_rate = array();
	foreach(yaamp_get_algos() as $algo)
		$pool_rate[$algo] = yaamp_pool_rate($algo);
	
	$coins = getdbolist('db_coins', "installed");
	foreach($coins as $coin)
	{
//		debuglog("doing $coin->name");

		$coin = getdbo('db_coins', $coin->id);
		if(!$coin) continue;
		
		$remote = new Bitcoin($coin->rpcuser, $coin->rpcpasswd, $coin->rpchost, $coin->rpcport);

		$info = $remote->getinfo();
		if(!$info)
		{
			$coin->enable = false;
		//	$coin->auto_ready = false;
			$coin->connections = 0;
				
			$coin->save();
			continue;
		}
		
//		debuglog($info);
		$coin->enable = true;
		
		if(isset($info['difficulty']))
			$difficulty = $info['difficulty'];
		else
			$difficulty = $remote->getdifficulty();
		
		if(is_array($difficulty))
		{
			$coin->difficulty = $difficulty['proof-of-work'];
			if(isset($difficulty['proof-of-stake']))
				 $coin->difficulty_pos = $difficulty['proof-of-stake'];
		}
		else
			$coin->difficulty = $difficulty;

		if($coin->algo == 'quark')
			$coin->difficulty /= 0x100;
		
		if($coin->difficulty == 0)
			$coin->difficulty = 1;
			
		$coin->errors = isset($info['errors'])? $info['errors']: '';
		$coin->txfee = isset($info['paytxfee'])? $info['paytxfee']: '';
		$coin->connections = isset($info['connections'])? $info['connections']: '';
		$coin->balance = isset($info['balance'])? $info['balance']: 0;
		$coin->mint = dboscalar("select sum(amount) from blocks where coin_id=$coin->id and category='immature'");

		if(empty($coin->master_wallet))
		{
			$coin->master_wallet = $remote->getaccountaddress('');
		//	debuglog($coin->master_wallet);
		}
		
		if(empty($coin->rpcencoding))
		{
			$difficulty = $remote->getdifficulty();
			if(is_array($difficulty))
				$coin->rpcencoding = 'POS';
			else
				$coin->rpcencoding = 'POW';
		}

 		if($coin->hassubmitblock == NULL)
 		{
			$remote->submitblock('');
			if(strcasecmp($remote->error, 'method not found') == 0)
				$coin->hassubmitblock = false;
			else
				$coin->hassubmitblock = true;
		}

 		if($coin->auxpow == NULL)
 		{
			$ret = $remote->getauxblock();
			
			if(strcasecmp($remote->error, 'method not found') == 0)
				$coin->auxpow = false;
			else
				$coin->auxpow = true;
		}

//		if($coin->symbol != 'BTC')
//		{
//			if($coin->symbol == 'PPC')
//				$template = $remote->getblocktemplate('');
//			else
			$template = $remote->getblocktemplate('{}');

			if($template && isset($template['coinbasevalue']))
			{
				$coin->reward = $template['coinbasevalue']/100000000*$coin->reward_mul;

				if($coin->symbol == 'TAC' && isset($template['_V2']))
					$coin->charity_amount = $template['_V2']/100000000;
				
				if(isset($template['payee_amount']) && $coin->symbol != 'LIMX')
				{
					$coin->charity_amount = $template['payee_amount']/100000000;
					$coin->reward -= $coin->charity_amount;
					
				//	debuglog("$coin->symbol $coin->charity_amount $coin->reward");
				}
				
				else if(!empty($coin->charity_address))
				{
					if($coin->charity_amount)
						;	//$coin->reward -= $coin->charity_amount;
					else
						$coin->reward -= $coin->reward * $coin->charity_percent / 100;
				}

				if(isset($template['bits']))
				{
					$target = decode_compact($template['bits']);
					$coin->difficulty = target_to_diff($target);
				}
			}
			
			else if(strcasecmp($remote->error, 'method not found') == 0)
			{
				$template = $remote->getmemorypool();
				if($template && isset($template['coinbasevalue']))
				{
					$coin->usememorypool = true;
					$coin->reward = $template['coinbasevalue']/100000000*$coin->reward_mul;

					if(isset($template['bits']))
					{
						$target = decode_compact($template['bits']);
						$coin->difficulty = target_to_diff($target);
					}
				}
				
				else
				{
					$coin->auto_ready = false;
					$coin->errors = $remote->error;
				}
			}
			
			else
			{
				$coin->auto_ready = false;
				$coin->errors = $remote->error;
			}
				
			if(strcasecmp($coin->errors, 'No more PoW blocks') == 0)
			{
				$coin->dontsell = true;
				$coin->auto_ready = false;
			}
//		}
			
		if($coin->block_height != $info['blocks'])
		{
			$count = $info['blocks'] - $coin->block_height;
			$ttf = (time() - $coin->last_network_found) / $count;

			if(empty($coin->actual_ttf)) $coin->actual_ttf = $ttf;

			$coin->actual_ttf = percent_feedback($coin->actual_ttf, $ttf, 5);
			$coin->last_network_found = time();
		}

		$coin->version = $info['version'];
		$coin->block_height = $info['blocks'];
		
		$coin->save();
	//	debuglog(" end $coin->name");

	}

	$coins = getdbolist('db_coins', "enable order by auxpow desc");
	foreach($coins as $coin)
	{
		$coin = getdbo('db_coins', $coin->id);
		if(!$coin) continue;
		
		if($coin->difficulty)
		{
			$coin->index_avg = $coin->reward * $coin->price * 10000 / $coin->difficulty;
			if(!$coin->auxpow && $coin->rpcencoding == 'POW')
			{
	 			$indexaux = dboscalar("select sum(index_avg) from coins where enable and visible and auto_ready and auxpow and algo='$coin->algo'");
				$coin->index_avg += $indexaux;
			}
		}
		
		if($coin->network_hash)
			$coin->network_ttf = $coin->difficulty * 0x100000000 / $coin->network_hash;
			
		if(isset($pool_rate[$coin->algo]))
			$coin->pool_ttf = $coin->difficulty * 0x100000000 / $pool_rate[$coin->algo];
		
		if(strstr($coin->image, 'http'))
		{
			$data = file_get_contents($coin->image);
			$coin->image = "/images/coin-$coin->id.png";
			
			@unlink(YAAMP_HTDOCS.$coin->image);
			file_put_contents(YAAMP_HTDOCS.$coin->image, $data);
		}
		
		$coin->save();
	}
	
	$d1 = microtime(true) - $t1;
	controller()->memcache->add_monitoring_function(__METHOD__, $d1);
}




