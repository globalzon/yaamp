<?php

class NicehashController extends CommonController
{
	public $defaultAction='index';
	
	public function actionIndex()
	{
		if(!$this->admin) return;
		$this->render('index');
	}

	public function actionIndex_results()
	{
		if(!$this->admin) return;
		$this->renderPartial('index_results');
	}
	
	public function actionStart()
	{
		if(!$this->admin) return;
		$id = getiparam('id');
		
		$nicehash = getdbo('db_nicehash', $id);
		if(!$nicehash) return;

		$nicehash->active = true;
		$nicehash->save();
		
		$this->goback();
	}
	
	public function actionStop()
	{
		if(!$this->admin) return;
		$id = getiparam('id');
	
		$nicehash = getdbo('db_nicehash', $id);
		if(!$nicehash) return;
	
		$nicehash->active = false;
		$nicehash->save();
	
		$this->goback();
	}
	
	
}







