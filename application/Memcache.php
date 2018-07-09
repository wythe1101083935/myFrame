<?php

namespace think\cache\driver;

use think\cache\Driver;

class Memcache extends Driver{
	protected $options = [
		'host' => '127.0.0.1',
		'port'	=> 11211,
		'expire'=> 0,
		'timeout'=>0,
		'persisitent'=>true,
		'prefix'=>'',
	];

	public function __construct($options = []){
		if(!extension_loaded('memcache')){
			echo 'error';
		}
		if(!empty($options)){
			$this->options = array_merge($this->options,$options);
		}

		$this->handler = new \Memecache;

		$hosts = explode(',',$this->options['host']);

		$ports = explode(',',$this->options['port']);

		if(empty($ports[0])){
			$ports[0] = 11211;
		}

		/*建立连接*/
		foreach ((array) $hosts  as $i => $host) {
			$port = isset($ports[$i]) ? $ports[$i] : $ports[0];
			$this->options['timeout'] > 0 ?
			$this->handler->addServer($host,$port,$this->options['persistent'],1,$this->options['timeout']) : 
			$this->handler->addServer($host,$port,$this->options['persistent'],1);
		}
	}

	/*判断缓存*/
	public function has($name){
		$key = $this->getCacheKey($name);
		return $this->handler->get($key) ? true : false;
	}

	/*读取缓存*/
	public function get($name,$default = false){
		$result = $this->handler->get($this->getCacheKey($name));
		return false !== $result ? $result : $default;
	}

	/*写入缓存*/
	public function set($name,$value,$expire = null){
		if(is_null($expire)){
			$expire = $this->options['expire'];
		}

		if($expire instanceof \DateTime){
			$expire = $expire->getTimestamp() - time();
		}

		if($this->tag && !$this->has($name)){
			$first = true;
		}

		$key = $this->getCacheKey($name);

		if($this->handler->set($key,$value,0,$expire)){
			isset($first) && $this->setTagItem($key);
			return true;
		}

		return false;
	}


	/*自增缓存*/
	public function inc($name,$step = 1){
		$key = $this->getCacheKey($name);
		if($this->handler->get($key)){
			return $this->handler->increment($key,$step);
		}

		return $this->handler->set($key,$step);
	}

	/*自减缓存*/
	public function dec($name,$step = 1){
		$key = $this->getCacheKey($name);
		$value = $this->handler->get($key) - $step;
		$res = $this->handler->set($key,$value);
		if(!$res){
			return false;
		} else {
			return $value;
		}
	}

	/*删除缓存*/
	public function rm($name,$ttl = false){
		$key = $this->getCacheKey($name);
		return false === $ttl ?
		$this->handler->delete($key) :
		$this->handler->delete($key,$ttl);
	}

	/*清除缓存*/
	public function clear($tag = null){
		if($tag){
			$keys = $this->getTagItem($tag);
			foreach ($keys as $key) {
				$this->handler->delete($key);
			}

			$this->rm('tag_' . md5($tag));
			return true;
		}
		return $this->handler->flush();
	}


}