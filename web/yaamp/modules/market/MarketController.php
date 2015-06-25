<?php

class MarketController extends CommonController
{
	public function actionUpdate()
	{
		if(!$this->admin) return;
		
		$market = getdbo('db_markets', getiparam('id'));
		$coin = getdbo('db_coins', $market->coinid);
		
		if(isset($_POST['db_markets']))
		{
			$market->attributes = $_POST['db_markets'];
			if($market->save())
				$this->redirect(array('site/coin', 'id'=>$coin->id));
		}
		
		$this->render('update', array('market'=>$market, 'coin'=>$coin));
	}

	public function actionDelete()
	{
		if(!$this->admin) return;
		
		$market = getdbo('db_markets', getiparam('id'));
		$coin = getdbo('db_coins', $market->coinid);

		if($market) $market->delete();
		$this->redirect(array('site/coin', 'id'=>$coin->id));
	}

	public function actionSellto()
	{
		if(!$this->admin) return;
		
		$market = getdbo('db_markets', getiparam('id'));
		$coin = getdbo('db_coins', $market->coinid);
		$amount = getparam('amount');
		
		$remote = new Bitcoin($coin->rpcuser, $coin->rpcpasswd, $coin->rpchost, $coin->rpcport);

		$info = $remote->getinfo();
		if(!$info || !$info['balance']) return false;

		$deposit_info = $remote->validateaddress($market->deposit_address);
		if(!$deposit_info || !isset($deposit_info['isvalid']) || !$deposit_info['isvalid'])
		{
			user()->setFlash('error', "invalid address $coin->name, $market->deposit_address");
			$this->redirect(array('site/coin', 'id'=>$coin->id));
		}
	
		$amount = min($amount, $info['balance'] - $info['paytxfee']);
//		$amount = max($amount, $info['balance'] - $info['paytxfee']);
		$amount = round($amount, 8);
		
		debuglog("selling ($market->deposit_address, $amount)");
	
		$tx = $remote->sendtoaddress($market->deposit_address, $amount);
		if(!$tx)
		{
			user()->setFlash('error', $remote->error);
			$this->redirect(array('site/coin', 'id'=>$coin->id));
		}

		$exchange = new db_exchange;
		$exchange->market = $market->name;
		$exchange->coinid = $coin->id;
		$exchange->send_time = time();
		$exchange->quantity = $amount;
		$exchange->price_estimate = $coin->price;
		$exchange->status = 'waiting';
		$exchange->tx = $tx;
		$exchange->save();

		$this->redirect(array('site/coin', 'id'=>$coin->id));
	}

}







