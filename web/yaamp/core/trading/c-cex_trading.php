<?php

function doCCexTrading($quick=false)
{
	$flushall = rand(0, 4) == 0;
	if($quick) $flushall = false;
	
//	debuglog("-------------- doCCexTrading() $flushall");
	$ccex = new CcexAPI;
	
	// upgrade orders
	$coins = getdbolist('db_coins', "enable and id in (select distinct coinid from markets where name='c-cex')");
	foreach($coins as $coin)
	{
		if($coin->dontsell) continue;
		
		$market2 = getdbosql('db_markets', "coinid=$coin->id and (name='bittrex' or name='cryptsy')");
		if($market2) continue;
		
		$pair = strtolower($coin->symbol).'-btc';

//		debuglog("c-cex list order for $pair");
		sleep(5);
		
		$orders = $ccex->getOrders($pair, 1);
		if(!$orders || isset($orders['error'])) continue;
		
		foreach($orders['return'] as $uuid=>$order)
		{
			$ticker = $ccex->getTickerInfo($pair);
			if(!$ticker) continue;
				
			if($order['price'] > $ticker['sell']+0.00000005 || $flushall)
			{
//				debuglog("c-cex cancel order for $pair $uuid");
				sleep(5);
				
				$ccex->cancelOrder($uuid);
				
				$db_order = getdbosql('db_orders', "uuid=:uuid", array(':uuid'=>$uuid));
				if($db_order) $db_order->delete();
				
				sleep(1);
			}
			
			else
			{
				$db_order = getdbosql('db_orders', "uuid=:uuid", array(':uuid'=>$uuid));
				if($db_order) continue;
				
				debuglog("c-cex adding order $coin->symbol");
				
				$db_order = new db_orders;
				$db_order->market = 'c-cex';
				$db_order->coinid = $coin->id;
				$db_order->amount = $order['amount'];
				$db_order->price = $order['price'];
				$db_order->ask = $ticker['sell'];
				$db_order->bid = $ticker['buy'];
				$db_order->uuid = $uuid;
				$db_order->created = time();
				$db_order->save();
			}
		}
		
		$list = getdbolist('db_orders', "coinid=$coin->id and market='c-cex'");
		foreach($list as $db_order)
		{
			$found = false;
			foreach($orders['return'] as $uuid=>$order)
			{
				if($uuid == $db_order->uuid)
				{
					$found = true;
					break;
				}
			}
				
			if(!$found)
			{
				debuglog("c-cex deleting order $coin->name $db_order->amount");
				$db_order->delete();
			}
		}
	}
	
	sleep(2);
	
	//////////////////////////////////////////////////////////////////////////////////////////////////
	
	$savebalance = getdbosql('db_balances', "name='c-cex'");
	$savebalance->balance = 0;

//	debuglog("c-cex getbalance");
	sleep(5);
	
	$balances = $ccex->getBalance();
	if(!$balances || !isset($balances['return'])) return;
	
	foreach($balances['return'] as $balance) foreach($balance as $symbol=>$amount)
	{
		if(!$amount) continue;
		if($symbol == 'btc')
		{
			$savebalance->balance = $amount;
			continue;
		}
		
		$coin = getdbosql('db_coins', "symbol=:symbol", array(':symbol'=>$symbol));
		if(!$coin || $coin->dontsell) continue;
		
		$market = getdbosql('db_markets', "coinid=$coin->id and name='c-cex'");
		if($market)
		{
			$market->lasttraded = time();
			$market->save();
		}
		
		if($amount*$coin->price < 0.00001000) continue;
		$pair = "$symbol-btc";
		
		////////////////////////
		
		$maxprice = 0;
		$maxamount = 0;
		
//		debuglog("c-cex list order for $pair all");
		sleep(5);
		
		$orders = $ccex->getOrders($pair, 0);
		foreach($orders['return'] as $order)
		{
			if($order['type'] == 'sell') continue;
			if($order['price'] > $maxprice)
			{
				$maxprice = $order['price'];
				$maxamount = $order['amount'];
			}
		}
		
	//	debuglog("maxbuy for $pair $maxamount $maxprice");
		if($amount >= $maxamount && $maxamount*$maxprice > 0.00001000)
		{
			$sellprice = bitcoinvaluetoa($maxprice);
			
			debuglog("c-cex selling market $pair, $maxamount, $sellprice");
			sleep(5);
				
			$res = $ccex->makeOrder('sell', $pair, $maxamount, $sellprice);
			if(!$res || !isset($res['return']))
				debuglog($res);
			else
				$amount -= $maxamount;
				
			sleep(1);
		}
		
		///
		
		$ticker = $ccex->getTickerInfo($pair);
		if(!$ticker) continue;
		
		$sellprice = bitcoinvaluetoa($ticker['sell']);
		
//		debuglog("c-cex selling $pair, $amount, $sellprice");
		sleep(5);
		
		$res = $ccex->makeOrder('sell', $pair, $amount, $sellprice);
 		if(!$res || !isset($res['return'])) continue;
		
 		$db_order = new db_orders;
		$db_order->market = 'c-cex';
 		$db_order->coinid = $coin->id;
		$db_order->amount = $amount;
		$db_order->price = $sellprice;
		$db_order->ask = $ticker['sell'];
		$db_order->bid = $ticker['buy'];
		$db_order->uuid = $res['return'];
		$db_order->created = time();
		$db_order->save();
	}
	
	$savebalance->save();
	
//	debuglog('-------------- doCCexTrading() done');
}






