<?php
/**
 +----------------------------------------------------------
 * memcached驱动
 +----------------------------------------------------------
 * CODE:
 +----------------------------------------------------------
 * TIME:2018-07-16 08:58:52
 +----------------------------------------------------------
 * author:wythe(汪志虹)
 +----------------------------------------------------------
 */
namespace wtyhe\data\cache;

class Memcached{
	protected $config = [
		'host'	=>	'127.0.0.1',
		'port'	=>	11211,
		'expire'=>	0,
		'timeout'=> 0,
		'prefix'=>'',
		'username'=>'',
		'password'=>'',
		'option'=>[],
	];

	protected $handler = null;

	public function __construct($config = []){
		if(!extension_loaded('memcahed')){
			echo 'error';
		}

		if(!empty($config)){
			$this->config = $config + $this->config;
		}

		$this->handler = new \Memcached;

		if(!empty($this->config['option'])){
			$this->handler->setOptions($this->config['option']);
		}

		/*设置链接超时时间*/
		if($this->config['timeout']){
			$this->handler->setOptions(\Memcached::OPT_CONNECT_TIMEOUT,$this->config['timeout']);
		}

		/*支持集群*/
		$hosts = explode(',',$this->config['host']);
		$ports = explode(',',$this->config['port']);

		if(empty($ports[0])){
			$ports[0] = 11211;
		}

		/*建立链接*/
		$servers = [];
		foreach ((array) $hosts as $i => $host) {
			$servers[] = [$host,isset($ports[$i]) ? $ports[$i] : $ports[0]),1];
		}
		$this->handler->addServers($servsers);
		if('' != $this->config['username']){
			$this->handler->setOption(\Memcached::OPT_BINARY_PROTOCOL,true);
			$this->handler->setSaslAuthDate($this->config['username'],$this->config['password']);
		}
	}

	/*判断缓存*/
	public function has(){
		$key = $this->getCacheKey($name);
		return $this->handler->get($key) ? true : false;
	}

	/*读取缓存*/
	public function get($name , $default = false){
		$result = $this->handler->get($this->getCacheKey($name));
		return false !== $result ? $result : $default;
	}

	/*写入缓存*/
	public function set($name,$value,$expire = null){
		if(is_null($expire)){
			$expire = $this->config['expire'];
		}
		$key = $this->getCacheKey($name);

		$expire = 0 == $expire ? 0 : time() + $expire;
		if($this->handler->set($key,$value,$expire)){
			return true;
		}
	}

	/*删除缓存*/
	public function delete($name,$ttl = false){
		$key = $this->getCacheKey($name);
		return $false === $ttl ? $this->handler->delete($key) : $this->handler->delete($key,$ttl)
	}
}