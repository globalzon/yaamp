<?php

class ApiController extends CommonController
{
	public $defaultAction='status';
	
	/////////////////////////////////////////////////
	
//	debuglog("saving renter {$_SERVER['REMOTE_ADDR']} $renter->address");
	
	public function actionStatus()
	{
		if(!LimitRequest('api-status', 10)) return;
		
		echo "{";
		foreach(yaamp_get_algos() as $i=>$algo)
		{
			if($i) echo ", ";
			
			$coins = controller()->memcache->get_database_count_ex("api_status_coins-$algo", 
				'db_coins', "enable and visible and auto_ready and algo=:algo", array(':algo'=>$algo));
				
			$hashrate = controller()->memcache->get_database_scalar("api_status_hashrate-$algo",
				"select hashrate from hashrate where algo=:algo order by time desc limit 1", array(':algo'=>$algo));
			
			$price = controller()->memcache->get_database_scalar("api_status_price-$algo",
				"select price from hashrate where algo=:algo order by time desc limit 1", array(':algo'=>$algo));
			
			$price = bitcoinvaluetoa(take_yaamp_fee($price/1000, $algo));
		
			$rental = controller()->memcache->get_database_scalar("api_status_price-$algo",
				"select rent from hashrate where algo=:algo order by time desc limit 1", array(':algo'=>$algo));
			
			$rental = bitcoinvaluetoa($rental);
			
			$t = time() - 24*60*60;
			
			$avgprice = controller()->memcache->get_database_scalar("api_status_avgprice-$algo",
				"select avg(price) from hashrate where algo=:algo and time>$t", array(':algo'=>$algo));
			
			$avgprice = bitcoinvaluetoa(take_yaamp_fee($avgprice/1000, $algo));
				
			$total1 = controller()->memcache->get_database_scalar("api_status_total-$algo",
				"select sum(amount*price) from blocks where category!='orphan' and time>$t and algo=:algo", array(':algo'=>$algo));
			
			$hashrate1 = controller()->memcache->get_database_scalar("api_status_avghashrate-$algo",
				"select avg(hashrate) from hashrate where time>$t and algo=:algo", array(':algo'=>$algo));
			
//			$btcmhday1 = $hashrate1 != 0? bitcoinvaluetoa($total1 / $hashrate1 * 1000000): '0.00000000';
			if($algo == 'sha256')
				$btcmhday1 = $hashrate1 != 0? mbitcoinvaluetoa($total1 / $hashrate1 * 1000000 * 1000000): '0';
			else
				$btcmhday1 = $hashrate1 != 0? mbitcoinvaluetoa($total1 / $hashrate1 * 1000000 * 1000): '0';
				
			$fees = yaamp_fee($algo);
			$port = getAlgoPort($algo);
			if($port == '-') $port = 0;
			
			echo "\"$algo\": ";
			echo "{";
			echo "\"name\": \"$algo\", ";
			echo "\"port\": $port, ";
			echo "\"coins\": $coins, ";
			echo "\"fees\": $fees, ";
			echo "\"hashrate\": $hashrate, ";
			echo "\"estimate_current\": $price, ";
			echo "\"estimate_last24h\": $avgprice, ";
			echo "\"actual_last24h\": $btcmhday1, ";
			echo "\"rental_current\": $rental";
			echo "}";
		}
		
		echo "}";
	}

	public function actionWallet()
	{
		if(!LimitRequest('api-wallet', 10)) return;
		
		$wallet = getparam('address');
		$user = getuserparam($wallet);
		if(!$user || $user->is_locked) return;
		
		$total_unsold = yaamp_convert_earnings_user($user, "status!=2");
		
		$total_paid = bitcoinvaluetoa(controller()->memcache->get_database_scalar("api_wallet_paid-$user->id",
			"select sum(amount) from payouts where account_id=$user->id"));
		
		$balance = bitcoinvaluetoa($user->balance);
		$total_unpaid = bitcoinvaluetoa($balance + $total_unsold);
		$total_earned = bitcoinvaluetoa($total_unpaid + $total_paid);

		$coin = getdbo('db_coins', $user->coinid);
		if(!$coin) return;
		
		echo "{";
		echo "\"currency\": \"$coin->symbol\", ";
		echo "\"unsold\": $total_unsold, ";
		echo "\"balance\": $balance, ";
		echo "\"unpaid\": $total_unpaid, ";
		echo "\"paid\": $total_paid, ";
		echo "\"total\": $total_earned";
		echo "}";
	}
	
	public function actionWalletEx()
	{
		if(!LimitRequest('api-wallet', 10)) return;
		
		$wallet = getparam('address');
		$user = getuserparam($wallet);
		if(!$user || $user->is_locked) return;
		
		$total_unsold = yaamp_convert_earnings_user($user, "status!=2");
		
		$total_paid = bitcoinvaluetoa(controller()->memcache->get_database_scalar("api_wallet_paid-$user->id",
			"select sum(amount) from payouts where account_id=$user->id"));
		
		$balance = bitcoinvaluetoa($user->balance);
		$total_unpaid = bitcoinvaluetoa($balance + $total_unsold);
		$total_earned = bitcoinvaluetoa($total_unpaid + $total_paid);

		$coin = getdbo('db_coins', $user->coinid);
		if(!$coin) return;
		
		echo "{";
		echo "\"currency\": \"$coin->symbol\", ";
		echo "\"unsold\": $total_unsold, ";
		echo "\"balance\": $balance, ";
		echo "\"unpaid\": $total_unpaid, ";
		echo "\"paid\": $total_paid, ";
		echo "\"total\": $total_earned, ";
		
 		echo "\"miners\": ";
 		echo "[";
		
		$workers = getdbolist('db_workers', "userid=$user->id order by password");
		foreach($workers as $i=>$worker)
		{
			$user_rate1 = yaamp_worker_rate($worker->id, $worker->algo);
			$user_rate1_bad = yaamp_worker_rate_bad($worker->id, $worker->algo);
			
			if($i) echo ", ";
			
			echo "{";
			echo "\"version\": \"$worker->version\", ";
			echo "\"password\": \"$worker->password\", ";
			echo "\"ID\": \"$worker->worker\", ";
			echo "\"algo\": \"$worker->algo\", ";
			echo "\"difficulty\": $worker->difficulty, ";
			echo "\"subscribe\": $worker->subscribe, ";
			echo "\"accepted\": $user_rate1, ";
			echo "\"rejected\": $user_rate1_bad";
			echo "}";
		}

 		echo "]";
		echo "}";
	}
	
	public function actionRental()
	{
		if(!LimitRequest('api-rental', 10)) return;
		
		$key = getparam('key');
		$renter = getdbosql('db_renters', "apikey=:apikey", array(':apikey'=>$key));
		if(!$renter) return;
		
		$balance = bitcoinvaluetoa($renter->balance);
		$unconfirmed = bitcoinvaluetoa($renter->unconfirmed);
		
		echo "{";
		echo "\"balance\": $balance, ";
		echo "\"unconfirmed\": $unconfirmed, ";
		
		echo "\"jobs\": [";
		$list = getdbolist('db_jobs', "renterid=$renter->id");
		foreach($list as $i=>$job)
		{
			if($i) echo ", ";
			
			$hashrate = yaamp_job_rate($job->id);
			$hashrate_bad = yaamp_job_rate_bad($job->id);
				
			echo '{';
			echo "\"jobid\": \"$job->id\", ";
			echo "\"algo\": \"$job->algo\", ";
			echo "\"price\": \"$job->price\", ";
			echo "\"hashrate\": \"$job->speed\", ";
			echo "\"server\": \"$job->host\", ";
			echo "\"port\": \"$job->port\", ";
			echo "\"username\": \"$job->username\", ";
			echo "\"password\": \"$job->password\", ";
			echo "\"started\": \"$job->ready\", ";
			echo "\"active\": \"$job->active\", ";
			echo "\"accepted\": \"$hashrate\", ";
			echo "\"rejected\": \"$hashrate_bad\", ";
			echo "\"diff\": \"$job->difficulty\"";
				
			echo '}';
		}
		
		echo "]}";
	}

	public function actionRental_price()
	{
		$key = getparam('key');
		$renter = getdbosql('db_renters', "apikey=:apikey", array(':apikey'=>$key));
		if(!$renter) return;
	
		$jobid = getparam('jobid');
		$price = getparam('price');
		
		$job = getdbo('db_jobs', $jobid);
		if($job->renterid != $renter->id) return;
		
		$job->price = $price;
		$job->time = time();
		$job->save();
	}
	
	public function actionRental_hashrate()
	{
		$key = getparam('key');
		$renter = getdbosql('db_renters', "apikey=:apikey", array(':apikey'=>$key));
		if(!$renter) return;
	
		$jobid = getparam('jobid');
		$hashrate = getparam('hashrate');

		$job = getdbo('db_jobs', $jobid);
		if($job->renterid != $renter->id) return;
		
		$job->speed = $hashrate;
		$job->time = time();
		$job->save();
	}
	
	public function actionRental_start()
	{
		$key = getparam('key');
		$renter = getdbosql('db_renters', "apikey=:apikey", array(':apikey'=>$key));
		if(!$renter || $renter->balance<=0) return;
			
		$jobid = getparam('jobid');

		$job = getdbo('db_jobs', $jobid);
		if($job->renterid != $renter->id) return;
		
		$job->ready = true;
		$job->time = time();
		$job->save();
	}
	
	public function actionRental_stop()
	{
		$key = getparam('key');
		$renter = getdbosql('db_renters', "apikey=:apikey", array(':apikey'=>$key));
		if(!$renter) return;
			
		$jobid = getparam('jobid');

		$job = getdbo('db_jobs', $jobid);
		if($job->renterid != $renter->id) return;
		
		$job->ready = false;
		$job->time = time();
		$job->save();
	}
	
// 	public function actionNodeReport()
// 	{
// 		$name = getparam('name');
// 		$uptime = getparam('uptime');
		
// 		$server = getdbosql('db_servers', "name='$name'");
// 		if(!$server)
// 		{
// 			$server = new db_servers;
// 			$server->name = $name;
// 		}
		
// 		$server->uptime = $uptime;
// 		$server->save();
// 	}
	
}

// function dummy()
// {
// 	$uptime = system('uptime');
// 	$name = system('hostname');
	
// 	fetch_url("http://yaamp.com/api/nodereport?name=$name&uptime=$uptime");
// }





