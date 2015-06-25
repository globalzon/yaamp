<?php

function BackendClearEarnings()
{
//	debuglog(__FUNCTION__);

	$delay = time() - 150*60;
	$total_cleared = 0;

 	$list = getdbolist('db_earnings', "status=1 and mature_time<$delay");
 	foreach($list as $earning)
 	{
		$user = getdbo('db_accounts', $earning->userid);
		if(!$user)
		{
			$earning->delete();
			continue;
		}
		
		$coin = getdbo('db_coins', $earning->coinid);
		if(!$coin)
		{
			$earning->delete();
			continue;
		}
		
 		$earning->status = 2;		// cleared
 		$earning->price = $coin->price;
 		$earning->save();
 		
// 		$refcoin = getdbo('db_coins', $user->coinid);
// 		if($refcoin && $refcoin->price<=0) continue;
// 		$value = $earning->amount * $coin->price / ($refcoin? $refcoin->price: 1);
	
		$value = yaamp_convert_amount_user($coin, $earning->amount, $user);
		
		$user->balance += $value;
		$user->save();
		
		if($user->coinid == 6)
			$total_cleared += $value;
 	}
 	
 	if($total_cleared>0)
	 	debuglog("total cleared from mining $total_cleared BTC");
}

