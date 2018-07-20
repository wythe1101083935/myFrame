<?php
namespace wythe\data\cache;
use \Memcache;
class Memcache{
	protected $config = [
		'host'=>[
			['127.0.0.1',11211],
			['127.0.0.1',11211],
			['127.0.0.1',11211],
		],
        'expire'=> 0,
        'timeout'=>0,
        'persisitent'=>true,
        'prefix'=> 'wythe',//缓存前缀
	];

	/*真实节点下的虚拟节点*/
	protected $nodes = []; 

	/*所有虚拟节点，指向真实节点*/
	protected $position = [];

	/*每个memcache分化的虚拟节点个数*/
	protected $mul = 64;

	/*使用php memcache扩展*/
	protected $handler;

	public function __construct($config=[]){
		$this->config = $config + $this->config;
		$this->initHost();
		$this->handler = new \Memcache();
	}

	/*节点初始化*/
	protected function initHost(){
		foreach ($this->config['host'] as $key => $val) {
			for ($i=0; $i < $this->mul; $i++) { 
				$pos = $this->hash($key . '-' .$i);
				$this->position[$pos] = $node;
				$this->nodes[$key][] = $pos;
			}
		}
		ksort($this->position,SORT_REGULAR);;//虚拟节点盘成一圈，排序		
	}

	/*根据键查找应存(取)服务器*/
	protected function lookup($key){
		$point = $this->hash($key);
		$node = current($this->position);//如果超出了最大值，那么就取最小值
		foreach ($this->position as $k=>$v) {
			if($point <= $k){
				$node = $v;
				break;
			}
		}
		reset($this->position);
		return $node;
	}

	/*连接memcache*/
	protected function connect($key){
		list($host,$port) = $this->config['host'][$this->lookup($key)];
		$this->handler->connect('memcache_host', 11211);
	}

	/*consistent;hash转换唯一数字*/
	protected function hash($str){
		return sprintf('%u',crc32($str));
	}

	/*获取缓存标识*/
	protected function getCacheKey($name){
		return $this->options['prefix'] . $name;
	}

	/*读取缓存*/
	public function get($name){
		$name = $this->getCacheKey($name);
		$this->connect($name);
		return $this->handler->get($name);
	}

	/*写入缓存*/
	public function set($name,$value,$expire =null){
		$name = $this->getCacheKey($name);
		$this->connect($name);
		return $this->handler->set($name,$value,0,$expire ? : $this->config['expire'])
	}

	/*删除缓存*/
	public function delete($name,$time = 0){
		$name = $this->getCachekey($name);
		$this->connect($name);
		$this->handler->delete($name,$time);
	}

	/*清空缓存*/
	public function clear($prefix = null){
		return false;
	}


}

