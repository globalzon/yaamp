<?php

include "util.php";

class ExplorerController extends CommonController
{
	public $defaultAction='index';
	
	/////////////////////////////////////////////////
	
	public function actionIndex()
	{
		if(isset($_COOKIE['mainbtc'])) return;
		if(!LimitRequest('explorer')) return;
	
		$id = getiparam('id');
		$coin = getdbo('db_coins', $id);
		
		$height = getparam('height');
		if($coin && intval($height)>0)
		{
			$remote = new Bitcoin($coin->rpcuser, $coin->rpcpasswd, $coin->rpchost, $coin->rpcport);
			$hash = $remote->getblockhash(intval($height));
		}
		
		else
			$hash = getparam('hash');
		
		$txid = getparam('txid');
		if($coin && !empty($txid) && ctype_alnum($txid))
		{
			$remote = new Bitcoin($coin->rpcuser, $coin->rpcpasswd, $coin->rpchost, $coin->rpcport);
			$tx = $remote->getrawtransaction($txid, 1);
				
			$hash = $tx['blockhash'];
		}
		
		if($coin && !empty($hash) && ctype_alnum($hash))
			$this->render('block', array('coin'=>$coin, 'hash'=>substr($hash, 0, 64)));
		
		else if($coin)
			$this->render('coin', array('coin'=>$coin));
		
		else
			$this->render('index');
	}


}







