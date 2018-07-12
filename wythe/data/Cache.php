<?php
namespace wythe\db;

class Cache{

	/*缓存的实例*/
	public function static $instance = [];

	/*缓存读取次数*/
	public static $readTimes = 0;

	/*缓存写入次数*/
	public static $writeTimes = 0;

	/*操作句柄*/
	public static $handler;


	/*连接缓存驱动*/
	public static function connect(array $options = [],$name = false){
		$type = !empty($options['type']) ? $options['type'] : 'File';

		if(false === $name){
			$name = md5(serialize($options));
		}

		if(true === $name || !isset(self::$instance[$name])){
			$class = false === strpos($type,'\\') ? __NAMESPACE__.'\\db\\'.ucwords($type) : $type;
		}
	}
}