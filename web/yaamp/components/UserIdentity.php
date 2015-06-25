<?php

class UserIdentity extends CUserIdentity
{
	private $_id;
	private $_fullname;
	
	public function __construct($user)
	{
		$this->_id = $user->id;
		$this->_fullname = $user->name;
		$this->username = $user->logon;
	}
	
	public function getId()
	{
		return $this->_id;
	}

	public function getFullname()
	{
		return $this->_fullname;
	}
}

