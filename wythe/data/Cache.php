<?php
/**
 +----------------------------------------------------------
 * 缓存工具
 +----------------------------------------------------------
 * CODE:
 +----------------------------------------------------------
 * TIME:2018-07-13 11:32:21
 +----------------------------------------------------------
 * author:wythe(汪志虹)
 +----------------------------------------------------------
 */
namespace wythe\data;
class Cache{

	/*单例*/
	private static $cache = null;

	/*缓存连接句柄*/
	private static $instance = null;

	/*缓存配置*/
	protected $config = [
		0=>[
	        'expire'        => 0,
	        'cache_subdir'  => true, //是否分割目录
	        'prefix'        => 'wythe',//缓存前缀
	        'path'          => '',
	        'data_compress' => false,
		],
	];

	/*当前使用的数据*/
	protected $currCache = 0;

	/*获取单例*/
	public static function getCache($config){
		if(is_null(self::$cache)){
			self::$cache = new self($config);
		}
		return self::$cache;
	}

	/*初始化*/
	private function __construct($config){
		$this->config[0] = $config;
	}

    /*设置数据库*/
    public function addCache($config,$configSign = 1){
        $this->config[$configSign] = $config;
        return $this;
    }

    /*选择当前使用的cache*/
    public function using($sign){
    	$this->currCache = $sign;
    	return $this;
    }

	/*连接缓存*/
	protected function connect(){
		$config = $this->config[$this->currCache];
		$name = md5(serialize($config));
		if(!isset(self::$instance[$name])){
			/*根据类型选择cache*/
			$class = __NAMESPACE__.'\\cache\\'.ucwords($config['type']);
			/*生成连接句柄*/
			self::$instance[$name] = new $class($config);
		}
		return self::$instance[$name];
	}

	/*写入缓存*/
	public function set($field,$value,$expire = null){
		$cache = $this->connect();
		return $cache->set($field,$value,$expire);
	}

	/*读取缓存*/
	public function get($field){
		$cache = $this->connect();
		return $cache->get($field);
	}

	/*判断缓存是否存在*/
	public function has($field){
		return $this->get($field) === false ? false : true;
	}

	/*删除缓存*/
	public function delete($field){
		$cache = $this->connect();
		return $cache->delete($field);
	}

	/*清空缓存*/
	public function clear($prefix=null){
		$cache = $this->connect();
		return $cache->clear($prefix);
	}

	public function __set($field,$value){
		return isset($this->config[$field]) ? $this->config[$field] = $value : $this->set($field,$value);
	}

	public function __get($field){
		return isset($this->config[$field]) ? $this->config[$field] : $this->get($field);
	}
}