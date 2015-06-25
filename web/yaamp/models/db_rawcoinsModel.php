<?php

class db_rawcoins extends CActiveRecord
{
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	public function tableName()
	{
		return 'rawcoins';
	}

	public function rules()
	{
		return array(
//			array('name', 'required'),
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
}

