<?php

class db_coins extends CActiveRecord
{
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	public function tableName()
	{
		return 'coins';
	}

	public function rules()
	{
		return array(
			array('name', 'required'),
			array('symbol', 'required'),
			array('symbol', 'unique'),
		);
	}

	public function relations()
	{
		return array(
		);
	}

	public function attributeLabels()
	{
		return array(
		);
	}
	
	public function getSymbol_show()
	{
		if(!empty($this->symbol2))
			return $this->symbol2;
		else
			return $this->symbol;
	}
	
	
}

