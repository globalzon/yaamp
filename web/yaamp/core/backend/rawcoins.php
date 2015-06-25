<?php

function updateRawcoins()
{
//	debuglog(__FUNCTION__);
	
	$list = bittrex_api_query('public/getcurrencies');
	if(isset($list->result))
	{
		dborun("update markets set deleted=true where name='bittrex'");
		foreach($list->result as $currency)
			updateRawCoin('bittrex', $currency->Currency, $currency->CurrencyLong);
	}
	
	$list = bleutrade_api_query('public/getcurrencies');
	if(isset($list->result))
	{
		dborun("update markets set deleted=true where name='bleutrade'");
		foreach($list->result as $currency)
			updateRawCoin('bleutrade', $currency->Currency, $currency->CurrencyLong);
	}
	
	$list = cryptsy_api_query('getmarkets');
	if(isset($list['return']))
	{
		dborun("update markets set deleted=true where name='cryptsy'");
		foreach($list['return'] as $item)
			updateRawCoin('cryptsy', $item['primary_currency_code'], $item['primary_currency_name']);
	}
	
	$ccex = new CcexAPI;
	$list = $ccex->getPairs();
	if($list)
	{
		dborun("update markets set deleted=true where name='c-cex'");
		foreach($list as $item)
		{
			$e = explode('-', $item);
			$symbol = strtoupper($e[0]);
	
			updateRawCoin('c-cex', $symbol);
		}
	}
	
	$poloniex = new poloniex;
	$tickers = $poloniex->get_currencies();
	
	dborun("update markets set deleted=true where name='poloniex'");
	foreach($tickers as $symbol=>$ticker)
	{
		if($ticker['disabled']) continue;
		if($ticker['delisted']) continue;
		
		updateRawCoin('poloniex', $symbol);
	}
	
	$res = yobit_api_query('info');
	if($res)
	{
		dborun("update markets set deleted=true where name='yobit'");
		foreach($res->pairs as $i=>$item)
		{
			$e = explode('_', $i);
			$symbol = strtoupper($e[0]);
			
			updateRawCoin('yobit', $symbol);
		}
	}
		
	//////////////////////////////////////////////////////////

	dborun("delete from markets where deleted");
	
	$list = getdbolist('db_coins', "not enable and not installed and id not in (select distinct coinid from markets)");
	foreach($list as $coin)
	{
 		debuglog("$coin->symbol is not longer active");
 		$coin->delete();
	}
}

function updateRawCoin($marketname, $symbol, $name='unknown')
{
	if($symbol == 'BTC') return;
	
	$coin = getdbosql('db_coins', "symbol=:symbol", array(':symbol'=>$symbol));
	if(!$coin)
	{
		debuglog("new coin $marketname $symbol $name");
		
		$coin = new db_coins;
		$coin->txmessage = true;
		$coin->hassubmitblock = true;
		$coin->name = $name;
		$coin->symbol = $symbol;
		$coin->created = time();
		$coin->save();
		
		mail('yaamp201@gmail.com', "New coin $symbol", "new coin $symbol ($name) on $marketname");
		sleep(30);
	}
	
	else if($coin->name == 'unknown' && $name != 'unknown')
	{
		$coin->name = $name;
		$coin->save();
	}
	
	$list = getdbolist('db_coins', "symbol=:symbol or symbol2=:symbol", array(':symbol'=>$symbol));
	foreach($list as $coin)
	{
		$market = getdbosql('db_markets', "coinid=$coin->id and name='$marketname'");
		if(!$market)
		{
			$market = new db_markets;
			$market->coinid = $coin->id;
			$market->name = $marketname;
		}
		
		$market->deleted = false;
		$market->save();
	}
	
	/////////
	
// 	if($coin->enable || !empty($coin->algo) || !empty($coin->errors) || $coin->name == 'unknown') return;
// 	debuglog("http://www.cryptocoinrank.com/$coin->name");
	
//  	$data = file_get_contents("http://www.cryptocoinrank.com/$coin->name");
//  	if($data)
//  	{
// 	 	$b = preg_match('/Algo: <span class=\"d-gray\">(.*)<\/span>/', $data, $m);
// 	 	if($b)
// 	 	{
// 	 		$coin->errors = trim($m[1]);
// 			$coin->save();
// 	 	}
//  	}
 	
}




