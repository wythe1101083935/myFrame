<?php
namespace wythe\data;


/*
 
*/

class Db{
	private static $instance = [];

	public static $queryTimes = 0 ;

	public static $executeTimes = 0;


	public static function connect($config=[],$name= false){
		if(false === $name){
			$name = md5(serialize($config));
		}

        if (true === $name || !isset(self::$instance[$name])) {
            // 解析连接参数 支持数组和字符串
            $options = self::parseConfig($config);

            if (empty($options['type'])) {
                throw new \InvalidArgumentException('Undefined db type');
            }

            $class = false !== strpos($options['type'], '\\') ?
            $options['type'] :
            '\\think\\db\\connector\\' . ucwords($options['type']);

            // 记录初始化信息
            if (App::$debug) {
                Log::record('[ DB ] INIT ' . $options['type'], 'info');
            }

            if (true === $name) {
                $name = md5(serialize($config));
            }

            self::$instance[$name] = new $class($options);
        }

        return self::$instance[$name];
	}
}