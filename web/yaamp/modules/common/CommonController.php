<?php

class CommonController extends CController
{
	public $admin = false;
	public $memcache;
	
	private $t1;
	
	public function goback($count=-1)
	{
		Javascript("window.history.go($count)");
		die;
	}

	public function beforeAction($action)
	{
	//	session_start();
		
		$this->memcache = new YaampMemcache;
		$this->t1 = microtime(true);
		
		if(user()->getState('yaamp_admin'))
			$this->admin = true;
		
		$algo = user()->getState('yaamp-algo');
		if(!$algo) user()->setState('yaamp-algo', 'x11');
		
		return true;
	}
	
	public function afterAction($action)
	{
	//	debuglog("after action");
		
		$t2 = microtime(true);
		$d1 = $t2 - $this->t1;
		
		$url = "$this->id/{$this->action->id}";
		$this->memcache->add_monitoring_function($url, $d1);
	}
	
	public function actionMaintenance()
	{
		$this->render('maintenance');
	}
	
}







