<?php

function BackendStatsUpdate()
{
//	debuglog(__FUNCTION__);
//	$t1xx = microtime(true);
	
 	$t = time()-2*60;
 	$errors = '';

 	$list = getdbolist('db_stratums', "time<$t");
 	foreach($list as $stratum)
 	{
 		debuglog("stratum $stratum->algo terminated");
 		$errors .= "$stratum->algo, ";
 	}
 	
 	if(!empty($errors))
		send_email_alert('stratums', "stratums restarted $errors", "stratums were restarted: $errors");
 	
 	dborun("delete from stratums where time<$t");
	dborun("delete from workers where pid not in (select pid from stratums)");
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////
	// long term stats
	
	$t = floor(time()/60/60)*60*60;
	foreach(yaamp_get_algos() as $algo)
	{
		$pool_rate = yaamp_pool_rate($algo);

		$stats = getdbosql('db_hashstats', "time=$t and algo=:algo", array(':algo'=>$algo));
		if(!$stats)
		{
			$stats = new db_hashstats;
			$stats->time = $t;
			$stats->hashrate = $pool_rate;
			$stats->algo = $algo;
		}
		else
		{
			$percent = 1;
			$stats->hashrate = round(($stats->hashrate*(100-$percent) + $pool_rate*$percent) / 100);
		}

		$stats->earnings = dboscalar("select sum(amount*price) from blocks where time>$t and category!='orphan' and algo=:algo", array(':algo'=>$algo));
		$stats->save();
	}
	
	////////////////////////////////////////////////////////////////////////////////////////////////////
	// short term stats
	
	$step = 15;
	$t = floor(time()/$step/60)*$step*60;
	
	foreach(yaamp_get_algos() as $algo)
	{
		$stats = getdbosql('db_hashrate', "time=$t and algo=:algo", array(':algo'=>$algo));
		if(!$stats)
		{
			$stats = new db_hashrate;
			$stats->time = $t;
			$stats->hashrate = dboscalar("select hashrate from hashrate where algo=:algo order by time desc limit 1", array(':algo'=>$algo));
			$stats->hashrate_bad = 0;	//dboscalar("select hashrate_bad from hashrate where algo=:algo order by time desc limit 1", array(':algo'=>$algo));
			$stats->price = dboscalar("select price from hashrate where algo=:algo order by time desc limit 1", array(':algo'=>$algo));
			$stats->rent = dboscalar("select rent from hashrate where algo=:algo order by time desc limit 1", array(':algo'=>$algo));
			$stats->algo = $algo;
		}
		
		$pool_rate = yaamp_pool_rate($algo);
		$stats->hashrate = $pool_rate;	//round(($stats->hashrate*(100-$percent) + $pool_rate*$percent) / 100);
		
		$pool_rate_bad = yaamp_pool_rate_bad($algo);
		$stats->hashrate_bad = $pool_rate_bad;	//round(($stats->hashrate_bad*(100-$percent) + $pool_rate_bad*$percent) / 100);
		
		if($stats->hashrate < 1000) $stats->hashrate = 0;

		$t1 = time() - 5*60;
		$total_rentable = dboscalar("select sum(difficulty) from shares where valid and extranonce1 and algo=:algo and time>$t1", array(':algo'=>$algo));
		$total_diff = dboscalar("select sum(difficulty) from shares where valid and algo=:algo and time>$t1", array(':algo'=>$algo));
		$total_rented = 0;
		
		if(!$total_diff)
		{
			$t1 = time() - 15*60;
			$total_diff = dboscalar("select sum(difficulty) from shares where valid and algo=:algo and time>$t1", array(':algo'=>$algo));
		}
			
		if($total_diff>0)
		{
			$price = 0;
			$rent = 0;
				
			$list = dbolist("select coinid, sum(difficulty) as d from shares where valid and algo=:algo and time>$t1 group by coinid", array(':algo'=>$algo));
			foreach($list as $item)
			{
				if($item['coinid'] == 0)
				{
					if(!$total_rentable) continue;
					$total_rented = $item['d'];
						
					$price += $stats->rent * $item['d'] / $total_diff;
					$rent += $stats->rent * $item['d'] / $total_rentable;
				}
				else
				{
					$coin = getdbo('db_coins', $item['coinid']);
					if(!$coin) continue;
	
					$btcghd = yaamp_profitability($coin);
					
					$price += $btcghd * $item['d'] / $total_diff;
					$rent += $btcghd * $item['d'] / $total_diff;
				}
			}
			
			$percent = 33;
			$rent = max($price, ($stats->rent*(100-$percent) + $rent*$percent) / 100);
				
			$target = yaamp_hashrate_constant($algo);
			$interval = yaamp_hashrate_step();

			$aa = $total_rentable * $target / $interval / 1000;
			$bb = dboscalar("select sum(speed) from jobs where active and ready and price>$rent and algo=:algo", array(':algo'=>$algo));
			
			if($total_rented*1.3 < $total_rentable || $bb > $aa)
				$rent += $price*YAAMP_FEES_RENTING/100;
			
			else
				$rent -= $price*YAAMP_FEES_RENTING/100;
				
			$stats->price = $price;
			$stats->rent = $rent;
		}
		
		else
		{
			$coin = getdbosql('db_coins', "enable and auto_ready and algo=:algo order by index_avg desc", array(':algo'=>$algo));
			if($coin)
			{
				$btcghd = yaamp_profitability($coin);
				$stats->price = $btcghd;
				$stats->rent = $stats->price + $stats->price * YAAMP_FEES_RENTING / 100;
			}
		}
		
		if(YAAMP_LIMIT_ESTIMATE)
		{
			$t1 = time() - 24*60*60;
			$avg = dboscalar("select avg(price) from hashrate where time>$t1 and algo=:algo", array(':algo'=>$algo));
			if($avg) $stats->price = min($stats->price, $avg*1.5);
		}

		$stats->difficulty = dboscalar("select sum(difficulty) from coins where enable and auto_ready and algo=:algo", array(':algo'=>$algo));
		$stats->save();
	}
	
	//////////////////////////////////////////////////////////////
	
	$step = 15;
	$t = floor(time()/$step/60)*$step*60;
		
	$btc = getdbosql('db_coins', "symbol='BTC'");
	$topay = dboscalar("select sum(balance) from accounts where coinid=$btc->id");	//here: take other currencies too
	$margin = $btc->balance - $topay;
	
	$balances = dboscalar("select sum(balance) from balances");
	$onsell = dboscalar("select sum(amount*bid) from orders");
	
	$immature = dboscalar("select sum(amount*price) from earnings where status=0");
	$confirmed = dboscalar("select sum(amount*price) from earnings where status=1");
	
	$wallets = dboscalar("select sum(balance*price) from coins where enable and symbol!='BTC'");
	$renters = dboscalar("select sum(balance) from renters");

	$mints = dboscalar("select sum(mint*price) from coins where enable");
	$off = $mints-$immature;
	
//	debuglog("mint $mints $immature $off");
	
	$total_profit = $btc->balance + $balances + $onsell + $wallets - $topay - $renters;
	
	$stats = getdbosql('db_stats', "time=$t");
	if(!$stats)
	{
		$stats = new db_stats;
		$stats->time = $t;
	}
	
	$stats->profit = $total_profit;
	$stats->wallet = $btc->balance;
	$stats->wallets = $wallets;
	
	$stats->margin = $margin;
	$stats->balances = $balances;
	$stats->onsell = $onsell;
	
	$stats->immature = $immature;
	$stats->waiting = $confirmed;
	$stats->renters = $renters;
	
	$stats->save();
	
	/////////////////////////////////////////////////////////////////////////////
	
	foreach(yaamp_get_algos() as $algo)
	{
		$factor = yaamp_get_algo_norm($algo);
	
		$dbalgo = getdbosql('db_algos', "name='$algo'");
		if(!$dbalgo)
		{
			$dbalgo = new db_algos;
			$dbalgo->name = $algo;
		}
		
		$dbalgo->profit = dboscalar("select price from hashrate where algo=:algo order by time desc limit 1", array(':algo'=>$algo));
		$dbalgo->rent = dboscalar("select rent from hashrate where algo=:algo order by time desc limit 1", array(':algo'=>$algo));
		
		$dbalgo->factor = $factor;
		$dbalgo->save();
	}

//	$d1 = microtime(true) - $t1xx;
//	controller()->memcache->add_monitoring_function(__METHOD__, $d1);
}


function BackendStatsUpdate2()
{
//	debuglog('----------------------------------');
//	debuglog(__FUNCTION__);
	
	////////////////////////////////////////////////////////////////////////////////////////////////////
	
	$step = 15;
	$t = floor(time()/$step/60)*$step*60;
	
	$list = dbolist("select userid, algo from shares where time>$t group by userid, algo");
	foreach($list as $item)
	{
		$stats = getdbosql('db_hashuser', "time=$t and algo=:algo and userid=:userid",
			array(':algo'=>$item['algo'], ':userid'=>$item['userid']));
		if(!$stats)
		{
			$stats = new db_hashuser;
			$stats->userid = $item['userid'];
			$stats->time = $t;
			$stats->hashrate = dboscalar("select hashrate from hashuser where algo=:algo and userid=:userid order by time desc limit 1",
				array(':algo'=>$item['algo'], ':userid'=>$item['userid']));
			$stats->hashrate_bad = 0;
			$stats->algo = $item['algo'];
		}
	
		$percent = 20;
		$user_rate = yaamp_user_rate($item['userid'], $item['algo']);
	
		$stats->hashrate = round(($stats->hashrate*(100-$percent) + $user_rate*$percent) / 100);
		if($stats->hashrate < 1000) $stats->hashrate = 0;
	
		$user_rate_bad = yaamp_user_rate_bad($item['userid'], $item['algo']);
	
		$stats->hashrate_bad = round(($stats->hashrate_bad*(100-$percent) + $user_rate_bad*$percent) / 100);
		if($stats->hashrate_bad < 1000) $stats->hashrate_bad = 0;
	
		$stats->save();
	}
	
	////////////////////////////////////////////////////////////////////////////////////////////////////
	
	$step = 15;
	$t = floor(time()/$step/60)*$step*60;
	
	$list = dbolist("select distinct jobid from jobsubmits where time>$t");
	foreach($list as $item)
	{
		$jobid = $item['jobid'];
	
		$stats = getdbosql('db_hashrenter', "time=$t and jobid=$jobid");
		if(!$stats)
		{
			$stats = new db_hashrenter;
			//	$stats->renterid = ;
			$stats->jobid = $item['jobid'];
			$stats->time = $t;
			$stats->hashrate = dboscalar("select hashrate from hashrenter where jobid=:jobid order by time desc limit 1", array(':jobid'=>$jobid));
			$stats->hashrate_bad = 0;	//dboscalar("select hashrate_bad from hashrenter where jobid=$jobid order by time desc limit 1");
		}
	
		$percent = 20;
		$job_rate = yaamp_job_rate($jobid);
	
		$stats->hashrate = round(($stats->hashrate*(100-$percent) + $job_rate*$percent) / 100);
		if($stats->hashrate < 1000) $stats->hashrate = 0;
	
		$job_rate_bad = yaamp_job_rate_bad($jobid);
	
		$stats->hashrate_bad = round(($stats->hashrate_bad*(100-$percent) + $job_rate_bad*$percent) / 100);
		if($stats->hashrate_bad < 1000) $stats->hashrate_bad = 0;
	
		$stats->save();
	}
	
	////////////////////////////////////////////////////////////////////////////////////////////////////
	
	$t = floor(time()/$step/60)*$step*60;
	$d = time()-24*60*60;
	
	$list = getdbolist('db_accounts', "balance>0 or last_login>$d");
	foreach($list as $user)
	{
		$stats = getdbosql('db_balanceuser', "time=$t and userid=$user->id");
		if(!$stats)
		{
			$stats = new db_balanceuser;
			$stats->userid = $user->id;
			$stats->time = $t;
		}
	
// 		$refcoin = getdbo('db_coins', $user->coinid);
// 		if(!$refcoin) $refcoin = getdbosql('db_coins', "symbol='BTC'");
// 		if(!$refcoin->price || !$refcoin->price2) continue;
	
// 		$pending1 = dboscalar("select sum(amount*price) from earnings where coinid=$refcoin->id and status!=2 and userid=$user->id");
// 		$pending2 = dboscalar("select sum(amount*price) from earnings where coinid!=$refcoin->id and status!=2 and userid=$user->id");
		
		$stats->pending = yaamp_convert_earnings_user($user, "status!=2");
		$stats->pending = bitcoinvaluetoa($stats->pending);
		
		$stats->balance = $user->balance;
		$stats->save();
	
		$id = dboscalar("select id from earnings where userid=$user->id order by id desc limit 100, 1");
		if($id) dborun("delete from earnings where status=2 and userid=$user->id and id<$id");
	}
	
	
}









