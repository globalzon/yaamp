<?php

function doYobitTrading($quick=false)
{
	$flushall = rand(0, 4) == 0;
	if($quick) $flushall = false;

	$coins = getdbolist('db_coins', "installed and id in (select distinct coinid from markets where name='yobit')");
	foreach($coins as $coin)
	{
 		if($coin->dontsell) continue;
 		$pair = strtolower("{$coin->symbol}_btc");
	
 		$orders = yobit_api_query2('ActiveOrders', array('pair'=>$pair));
 		if(isset($orders['return'])) foreach($orders['return'] as $uuid=>$order)
 		{
			$ticker = yobit_api_query("ticker/$pair");
			if(!$ticker) continue;
			
			if($order['rate'] > $ticker->$pair->sell + 0.00000005 || $flushall)
			{
//				debuglog("yobit cancel order for $pair $uuid");
		 		$res = yobit_api_query2('CancelOrder', array('order_id'=>$uuid));
							
				$db_order = getdbosql('db_orders', "uuid=:uuid", array(':uuid'=>$uuid));
				if($db_order) $db_order->delete();
			
				sleep(1);
			}

			else
			{
				$db_order = getdbosql('db_orders', "uuid=:uuid", array(':uuid'=>$uuid));
				if($db_order) continue;
			
				debuglog("yobit adding order $coin->symbol");
			
				$db_order = new db_orders;
				$db_order->market = 'yobit';
				$db_order->coinid = $coin->id;
				$db_order->amount = $order['amount'];
				$db_order->price = $order['rate'];
				$db_order->ask = $ticker->$pair->sell;
				$db_order->bid = $ticker->$pair->buy;
				$db_order->uuid = $uuid;
				$db_order->created = time();
				$db_order->save();
			}
 		}
 		
		$list = getdbolist('db_orders', "coinid=$coin->id and market='yobit'");
		foreach($list as $db_order)
		{
			$found = false;
			if(isset($orders['return'])) foreach($orders['return'] as $uuid=>$order)
			{
				if($uuid == $db_order->uuid)
				{
					$found = true;
					break;
				}
			}
				
			if(!$found)
			{
				debuglog("yobit deleting order $coin->name $db_order->amount");
				$db_order->delete();
			}
		}
	}

	sleep(2);
 	
 	//////////////////////////////////////////////////////////////////////////////////////////////////
 	
 	$savebalance = getdbosql('db_balances', "name='yobit'");
 	if(!$savebalance) return;
 	
 	$savebalance->balance = 0;
 	
	$balances = yobit_api_query2('getInfo');
 	if(!$balances || !isset($balances['return'])) return;
 	
	foreach($balances['return']['funds'] as $symbol=>$amount)
 	{
// 		debuglog("$symbol, $amount");
 		$amount -= 0.0001;
 		if($amount<=0) continue;
 		if($symbol == 'btc')
 		{
 			$savebalance->balance = $amount;
 			continue;
 		}
 		
 		$coin = getdbosql('db_coins', "symbol=:symbol", array(':symbol'=>$symbol));
 		if(!$coin || $coin->dontsell) continue;
 	
 		$market = getdbosql('db_markets', "coinid=$coin->id and name='yobit'");
 		if($market)
 		{
 			$market->lasttraded = time();
 			$market->save();
 		}

 		if($amount*$coin->price < 0.00001000) continue;
 		$pair = "{$symbol}_btc";
 			
 		$data = yobit_api_query("depth/$pair?limit=11");
 		if(!$data) continue;

 		$sold_amount = 0;
		for($i = 0; $i < 10 && $amount >= 0; $i++)
		{
			if(!isset($data->$pair->bids[$i])) break;
			
			$nextbuy = $data->$pair->bids[$i];
			if($amount*1.1 < $nextbuy[1]) break;
			
			$sellprice = bitcoinvaluetoa($nextbuy[0]);
			$sellamount = min($amount, $nextbuy[1]);

			if($sellamount*$sellprice < 0.00010000) continue;
			
			debuglog("yobit selling market $pair, $sellamount, $sellprice");
			$res = yobit_api_query2('Trade', array('pair'=>$pair, 'type'=>'sell', 'rate'=>$sellprice, 'amount'=>$sellamount));

			if(!$res || !$res['success'])
			{
				debuglog($res);
				break;
			}
				
			$amount -= $sellamount;
 			$sold_amount += $sellamount;
			
// 			sleep(1);
		}
		
		$ticker = yobit_api_query("ticker/$pair");
		if(!$ticker) continue;

//		if(!$coin->sellonbid && $sold_amount*$coin->price > 0.002)
//		{
//			sleep(5);

//			$buyprice = bitcoinvaluetoa($ticker->$pair->sell);
//			$buyamount = bitcoinvaluetoa(0.00011/$ticker->$pair->sell);

//			debuglog("yobit buyback $pair, $buyamount, $buyprice");
//			$res = yobit_api_query2('Trade', array('pair'=>$pair, 'type'=>'buy', 'rate'=>$buyprice, 'amount'=>$buyamount));

//			sleep(5);
//		}

		if($amount <= 0) continue;
		
		if($coin->sellonbid)
 			$sellprice = bitcoinvaluetoa($ticker->$pair->buy);
 		else
 			$sellprice = bitcoinvaluetoa($ticker->$pair->sell);
		if($amount*$sellprice < 0.00010000) continue;
		
// 		debuglog("yobit selling $pair, $amount, $sellprice");
		
		$res = yobit_api_query2('Trade', array('pair'=>$pair, 'type'=>'sell', 'rate'=>$sellprice, 'amount'=>$amount));
		if(!$res || !$res['success'])
		{
			debuglog($res);
			continue;
		}

		$db_order = new db_orders;
		$db_order->market = 'yobit';
		$db_order->coinid = $coin->id;
		$db_order->amount = $amount;
		$db_order->price = $sellprice;
		$db_order->ask = $ticker->$pair->sell;
		$db_order->bid = $ticker->$pair->buy;
		$db_order->uuid = $res['return']['order_id'];
		$db_order->created = time();
		$db_order->save();
		
		sleep(1);
	}
	
	$savebalance->save();

}

















