<?php

class YaampMemcache
{
	public $memcache = null;
	
	public function __construct()
	{
		if(!function_exists("memcache_connect")) return;
		$this->memcache = memcache_connect("127.0.0.1", 11211);
	}
	
	public function get($key)
	{
		return memcache_get($this->memcache, $key);
	}
	
	public function set($key, $value, $t=30)
	{
		memcache_set($this->memcache, $key, $value, 0, $t);
	}
	
	////////////////////////////////////////////////////////////////
	
	public function get_database_count($key, $query, $params=array())
	{
		
	}
	
	public function get_database_scalar($key, $query, $params=array())
	{
		$value = $this->get($key);
		if($value === false)
		{
			$value = dboscalar($query, $params);
			$this->set($key, $value);
		}
		
		return $value;
	}
	
	public function get_database_count_ex($key, $table, $query, $params=array())
	{
		$value = $this->get($key);
		if($value === false)
		{
			$value = getdbocount($table, $query, $params);
			$this->set($key, $value);
		}
		
		return $value;
	}
	
	public function get_database_row($key, $query, $params=array())
	{
		$value = $this->get($key);
		if($value === false)
		{
			$value = dborow($query, $params);
			$this->set($key, $value);
		}
		
		return $value;
	}
	
	public function add_monitoring_function($name, $d1)
	{
		return;
		$count = memcache_get($this->memcache, "$name-count");
		memcache_set($this->memcache, "$name-count", $count+1);
		
		$d = memcache_get($this->memcache, "$name-time");
		memcache_set($this->memcache, "$name-time", $d+$d1);
		
		$a = memcache_get($this->memcache, 'url-map');
		if(!$a) $a = array();
		
		$a[$name] = $count+1;
		memcache_set($this->memcache, 'url-map', $a);
	}
};




