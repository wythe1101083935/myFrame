<?php
namespace wythe\data;

use wythe\data\cache\;

class Cache{
	/*缓存实例*/
	public static $instance = [];


	/*缓存读取次数*/
	public static $readTimes = 0;

	/*缓存写入次数*/
	public static $writeTimes = 0;

	/*操作句柄*/
	public static $handler;


	/*连接缓存驱动*/
	public static function connect(array $options = [],$name = false){
		$type = !empty($options['type']) ? $options['type'] : 'Fiel';

		if(false === $name){
			$name = md5(serialize($options));
		}

		if(true === $name || !isset(self::$instance[$name])){
			$class = false === strpos($type,'\\') ?
			'\\wythe\\data\\cache\\' . ucwords($type) :
			$type;

			/*记录初始化信息*/
			App::$debug && Log::record('[ CACHE ] INIT ' . $type,'info');

			if(true === $name){
				return new $class($options);
			}

			self::$instance[$name] = new $class($options);
		}

		return self::$instance[$name];
	}


	/*自动初始化缓存*/
	public static function init(array $options = []){
		if(is_null(self::$handler)){
			if(empty($options) && 'complex' == Config::get('cache.type')){
				$default = Config::get('cache.default');
				/*获取默认缓存配置，并连接*/
				$options = Config::get('cache.' . $default['type']) ? $default;
			} elseif (empty($options)){
				$options = Config::get('cache');
			}

			self::$handler = self::connect($options);
		}
		return self::$handler;
	}

	/*切换缓存类型*/
	public  static function store($name = ''){
		if('' !== $name && 'complex' == Config::get('cache.type')){
			return self::connect(Config::get('cache.' . $name),strtolower($name))
		}
		return self::init();
	}

	/*判断缓存是否存在*/
	public static function has($name){
		self::$readTimes++;
		return self::init()->has($name);
	}

	/*读取缓存*/
	public static function get($name,$default = false){
		self::$readTimes++;

		return self::init()->get($name,$default);
	}

	/*写入缓存*/
	public static function set($name,$value,$expire = null){
		self::$writeTimes++;

		return self::init()->set($name,$value,$expire);
	}

	/*自增缓存*/
	public static function inc($name,$step = 1){
		self::$writeTimes++;
		return self::init()->inc($name,$step);
	}

	/*自减缓存*/
	public static function dec($name,$step = 1){
		self::$writeTimes++;

		return self::init()->dec($name,$step);
	}

	/*删除缓存*/
	public static function rm($name){
		self::$writeTimes++;

		return self::init()->rm($name);
	}

	/*清除缓存*/
	public static function clear($tag = null){
		self::$writeTimes++;

		return self::init()->clear($tag);
	}

	/*读取缓存并删除*/
	public static function pull($name){
		self::$readTimes++;
		self::$writeTimes++;

		return self::init()->pull($name);
	}

	/*如果不存在则写入缓存*/
	public static function remember($name,$value,$expire = null){
		self::$readTimes++;
		return self::init()->remember($name,$value,$expire);
	}

	/*缓存标签*/
	public static function tag($name,$keys = null,$overlay = false){
		return self::init()->tag($names,$keys,$overlay);
	}
}