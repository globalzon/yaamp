<?php

function yaamp_get_algos()
{
	return array('sha256', 'scrypt', 'scryptn', 'neoscrypt', 'quark', 'lyra2', 'qubit', 'x11', 'x13', 'x15');
}

function yaamp_get_algo_norm($algo)
{
	$a = array(
//		'sha256'	=> 1,
		'scrypt'	=> 1,
		'scryptn'	=> 0.5,
		'x11'		=> 5.5,
		'x13'		=> 3.9,
		'x14'		=> 3.7,
		'x15'		=> 3.5,
		'zr5'		=> 3,
		'nist5'		=> 16,
		'neoscrypt'	=> 0.3,
		'lyra2'		=> 1.3,
		'quark'		=> 5,
		'fresh'		=> 5,
		'qubit'		=> 5,
		'skein'		=> 90,
		'groestl'	=> 5,
		'blake'		=> 300,
		'keccak'	=> 160,
	);

	if(!isset($a[$algo]))
		return 0;

	return $a[$algo];
}

function getAlgoColors($algo)
{
	$a = array(
		'sha256'	=> '#d0d0a0',
		'scrypt'	=> '#c0c0e0',
		'neoscrypt'	=> '#a0d0f0',
		'scryptn'	=> '#d0d0d0',
		'x11'		=> '#f0f0a0',
		'x13'		=> '#d0f0c0',
		'x14'		=> '#a0f0c0',
		'x15'		=> '#f0b0a0',
		'nist5'		=> '#f0d0f0',
		'quark'		=> '#c0c0c0',
		'qubit'		=> '#d0a0f0',
		'lyra2'		=> '#80a0f0',
	);

	if(!isset($a[$algo]))
		return '#ffffff';

	return $a[$algo];
}

function getAlgoPort($algo)
{
	$a = array(
		'sha256'	=> 3333,
		'scrypt'	=> 3433,
		'x11'		=> 3533,
		'x13'		=> 3633,
		'x15'		=> 3733,
		'nist5'		=> 3833,
		'x14'		=> 3933,
		'quark'		=> 4033,
		'fresh'		=> 4133,
		'neoscrypt'	=> 4233,
		'scryptn'	=> 4333,
		'lyra2'		=> 4433,
		'blake'		=> 4533,
		'jha'		=> 4633,
		'qubit'		=> 4733,
		'zr5'		=> 4833,
		'skein'		=> 4933,
		'groestl'	=> 5033,
		'keccak'	=> 5133,
	);

	if(!isset($a[$algo]))
		return 3033;

	return $a[$algo];
}

////////////////////////////////////////////////////////////////////////

function yaamp_fee($algo)
{
	$fee = controller()->memcache->get("yaamp_fee-$algo");
	if($fee) return $fee;
	
	$norm = yaamp_get_algo_norm($algo);
	if($norm == 0) $norm = 1;
	
	$hashrate = getdbosql('db_hashrate', "algo=:algo order by time desc", array(':algo'=>$algo));
	if(!$hashrate || !$hashrate->difficulty) return 1;

	$target = yaamp_hashrate_constant($algo);
	$interval = yaamp_hashrate_step();
	$delay = time()-$interval;
	
	$rate = controller()->memcache->get_database_scalar("yaamp_pool_rate_coinonly-$algo",
		"select sum(difficulty) * $target / $interval / 1000 from shares where valid and time>$delay and algo=:algo and jobid=0", array(':algo'=>$algo));
	
//	$fee = round(log($hashrate->hashrate * $norm / 1000000 / $hashrate->difficulty + 1), 1) + YAAMP_FEES_MINING;
	$fee = round(log($rate * $norm / 2000000 / $hashrate->difficulty + 1), 1) + YAAMP_FEES_MINING;
	
	controller()->memcache->set("yaamp_fee-$algo", $fee);
	return $fee;
}

function take_yaamp_fee($v, $algo)
{
	return $v - ($v * yaamp_fee($algo) / 100);
}

function yaamp_hashrate_constant($algo=null)
{
	return pow(2, 42);		// 0x400 00000000
}

function yaamp_hashrate_step()
{
	return 300;
}

function yaamp_profitability($coin)
{
	if(!$coin->difficulty) return 0;
	
	$btcmhd = 20116.56761169 / $coin->difficulty * $coin->reward * $coin->price;
	if(!$coin->auxpow && $coin->rpcencoding == 'POW')
	{
		$listaux = getdbolist('db_coins', "enable and visible and auto_ready and auxpow and algo='$coin->algo'");
		foreach($listaux as $aux)
		{
			if(!$aux->difficulty) continue;
			
			$btcmhdaux = 20116.56761169 / $aux->difficulty * $aux->reward * $aux->price;
			$btcmhd += $btcmhdaux;
		}
	}
	
	if($coin->algo == 'sha256') $btcmhd *= 1000;
	return $btcmhd;
}

function yaamp_convert_amount_user($coin, $amount, $user)
{
	$refcoin = getdbo('db_coins', $user->coinid);
	if(!$refcoin) $refcoin = getdbosql('db_coins', "symbol='BTC'");
	if(!$refcoin || $refcoin->price2<=0) return 0;
	
	$value = $amount * $coin->price2 / $refcoin->price2;
	return $value;
}

function yaamp_convert_earnings_user($user, $status)
{
	$refcoin = getdbo('db_coins', $user->coinid);
	if(!$refcoin) $refcoin = getdbosql('db_coins', "symbol='BTC'");
	if(!$refcoin || $refcoin->price2<=0) return 0;

	$value = dboscalar("select sum(amount*price) from earnings where $status and userid=$user->id");
 	$value = $value/$refcoin->price2;
	
	return $value;
}

////////////////////////////////////////////////////////////////////////////////////////////

function yaamp_pool_rate($algo=null)
{
	if(!$algo) $algo = user()->getState('yaamp-algo');

	$target = yaamp_hashrate_constant($algo);
	$interval = yaamp_hashrate_step();
	$delay = time()-$interval;
	
	$rate = controller()->memcache->get_database_scalar("yaamp_pool_rate-$algo",
		"select sum(difficulty) * $target / $interval / 1000 from shares where valid and time>$delay and algo=:algo", array(':algo'=>$algo));

	return $rate;
}

function yaamp_pool_rate_bad($algo=null)
{
	if(!$algo) $algo = user()->getState('yaamp-algo');
	
	$target = yaamp_hashrate_constant($algo);
	$interval = yaamp_hashrate_step();
	$delay = time()-$interval;
	
	$rate = controller()->memcache->get_database_scalar("yaamp_pool_rate_bad-$algo",
		"select sum(difficulty) * $target / $interval / 1000 from shares where not valid and time>$delay and algo=:algo", array(':algo'=>$algo));

	return $rate;
}

function yaamp_pool_rate_rentable($algo=null)
{
	if(!$algo) $algo = user()->getState('yaamp-algo');

	$target = yaamp_hashrate_constant($algo);
	$interval = yaamp_hashrate_step();
	$delay = time()-$interval;
	
	$rate = controller()->memcache->get_database_scalar("yaamp_pool_rate_rentable-$algo",
		"select sum(difficulty) * $target / $interval / 1000 from shares where valid and extranonce1 and time>$delay and algo=:algo", array(':algo'=>$algo));

	return $rate;
}

function yaamp_user_rate($userid, $algo=null)
{
	if(!$algo) $algo = user()->getState('yaamp-algo');
	
	$target = yaamp_hashrate_constant($algo);
	$interval = yaamp_hashrate_step();
	$delay = time()-$interval;
	
	$rate = controller()->memcache->get_database_scalar("yaamp_user_rate-$userid-$algo",
		"select sum(difficulty) * $target / $interval / 1000 from shares where valid and time>$delay and userid=$userid and algo=:algo", array(':algo'=>$algo));

	return $rate;
}

function yaamp_user_rate_bad($userid, $algo=null)
{
	if(!$algo) $algo = user()->getState('yaamp-algo');
	
	$target = yaamp_hashrate_constant($algo);
	$interval = yaamp_hashrate_step();
	$delay = time()-$interval;
	
	$rate = controller()->memcache->get_database_scalar("yaamp_user_rate_bad-$userid-$algo",
		"select sum(difficulty) * $target / $interval / 1000 from shares where not valid and time>$delay and userid=$userid and algo=:algo", array(':algo'=>$algo));

	return $rate;
}

function yaamp_worker_rate($workerid, $algo=null)
{
	if(!$algo) $algo = user()->getState('yaamp-algo');
	
	$target = yaamp_hashrate_constant($algo);
	$interval = yaamp_hashrate_step();
	$delay = time()-$interval;

	$rate = controller()->memcache->get_database_scalar("yaamp_worker_rate-$workerid-$algo",
		"select sum(difficulty) * $target / $interval / 1000 from shares where valid and time>$delay and workerid=$workerid");

	return $rate;
}

function yaamp_worker_rate_bad($workerid, $algo=null)
{
	if(!$algo) $algo = user()->getState('yaamp-algo');
	
	$target = yaamp_hashrate_constant($algo);
	$interval = yaamp_hashrate_step();
	$delay = time()-$interval;

	$rate = controller()->memcache->get_database_scalar("yaamp_worker_rate_bad-$workerid-$algo",
		"select sum(difficulty) * $target / $interval / 1000 from shares where not valid and time>$delay and workerid=$workerid");

	return empty($rate)? 0: $rate;
}

function yaamp_coin_rate($coinid)
{
	$coin = getdbo('db_coins', $coinid);
	if(!$coin || !$coin->enable) return 0;
		
	$target = yaamp_hashrate_constant($coin->algo);
	$interval = yaamp_hashrate_step();
	$delay = time()-$interval;

	$rate = controller()->memcache->get_database_scalar("yaamp_coin_rate-$coinid",
		"select sum(difficulty) * $target / $interval / 1000 from shares where valid and time>$delay and coinid=$coinid");

	return $rate;
}

function yaamp_rented_rate($algo=null)
{
	if(!$algo) $algo = user()->getState('yaamp-algo');

	$target = yaamp_hashrate_constant($algo);
	$interval = yaamp_hashrate_step();
	$delay = time()-$interval;

	$rate = controller()->memcache->get_database_scalar("yaamp_rented_rate-$algo",
		"select sum(difficulty) * $target / $interval / 1000 from shares where time>$delay and algo=:algo and jobid!=0 and valid", array(':algo'=>$algo));

	return $rate;
}

function yaamp_job_rate($jobid)
{
	$job = getdbo('db_jobs', $jobid);
	if(!$job) return 0;
		
	$target = yaamp_hashrate_constant($job->algo);
	$interval = yaamp_hashrate_step();
	$delay = time()-$interval;

	$rate = controller()->memcache->get_database_scalar("yaamp_job_rate-$jobid",
		"select sum(difficulty) * $target / $interval / 1000 from jobsubmits where valid and time>$delay and jobid=$jobid");
	return $rate;
}

function yaamp_job_rate_bad($jobid)
{
	$job = getdbo('db_jobs', $jobid);
	if(!$job) return 0;
		
	$target = yaamp_hashrate_constant($job->algo);
	$interval = yaamp_hashrate_step();
	$delay = time()-$interval;

	$rate = controller()->memcache->get_database_scalar("yaamp_job_rate_bad-$jobid",
		"select sum(difficulty) * $target / $interval / 1000 from jobsubmits where not valid and time>$delay and jobid=$jobid");

	return $rate;
}

//////////////////////////////////////////////////////////////////////////////////////////////////////

function yaamp_pool_rate_pow($algo=null)
{
	if(!$algo) $algo = user()->getState('yaamp-algo');

	$target = yaamp_hashrate_constant($algo);
	$interval = yaamp_hashrate_step();
	$delay = time()-$interval;
	
	$rate = controller()->memcache->get_database_scalar("yaamp_pool_rate_pow-$algo",
		"select sum(shares.difficulty) * $target / $interval / 1000 from shares, coins 
			where shares.valid and shares.time>$delay and shares.algo=:algo and 
			shares.coinid=coins.id and coins.rpcencoding='POW'", array(':algo'=>$algo));

	return $rate;
}

/////////////////////////////////////////////////////////////////////////////////////////////

function yaamp_renter_account($renter)
{
	if(YAAMP_PRODUCTION)
		return "renter-prod-$renter->id";
	else
		return "renter-dev-$renter->id";
}






