<?php

namespace wythe\data\cache;


class Redis{
	protected $config = [
		'host' => '127.0.0.1',
		'port' => 6379,
		'password'=>'',
		'select'=>0,
		'timeout'=>0,
		'expire'=>0,
		'persistent'=>false,
		'prefix'=>'',
	];

	protected $handler = null;
	public function __construct($config = []){
		if(!extension_loaded('redis')){
			echo 'error';
		}

		$this->config = $config + $this->confg;

		$this->handler = new \Redis;

		if($this->config['persistent']){
			$this->handler->pconnect($this->config['host'],$this->config['port'],$this->config['timeout'],'persistent_id' . $this->config['select'])
		} else {
			$this->handler->connect($this->config['host'],$this->config['port'],$this->config['timeout']);
		}

		if('' != $this->config['password']){
			$this->handler->auth($this->config['password']);
		}

		if(0 != $this->config['select']){
			$this->handler->select($this->config['select']);
		}
	}

	public function get($name,$default = false){
		$value = $this->handler->get($this->getCacheKey($name));
		if(is_null($value) || false === $value){
			return $default;
		}

		try{
			$result = 0 === strpos($value,'wythe_serialize') ? unserialize(substr($value,16)) : $value;
		} catch (\Exception $e){
			$default = $default;
		}
		return $result;
	}

	/*写入缓存*/
	public function set($name,$value,$expire = null){
		if(is_null($expire)){
			$expire = $this->config['expire'];
		}

		$key = $this->getCacheKey($name);

		$value = is_scalar($value) ? $value : 'think_serialize' . serialize($value);

		if($expire){
			$result = $this->handler->setex($key,$expire,$value);
		} else{
			$result = $this->handler->set($key,$value);
		}
		return $result;
	}

	public function delete($name){
		return $this->handler->delete($this->getCacheKey($name));
	}
}