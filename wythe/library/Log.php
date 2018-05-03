<?php

namespace wythe;

class Log{
	const LOG = 'log';
	const ERROR = 'error';
	const INFO = 'info';
	const SQL = 'sql';
	const NOTICE = 'notice';
	const WARNING = 'warning';
	const DEBUG = 'debug';

	/*日志信息*/
	protected static $log = [];

	/*配置参数*/
	protected static $config = [];

	/*日志类型*/
	protected static $type = ['log','error','info','sql','notice','warning','debug'];

	protected static $driver;

	/*当前日志授权 key*/
	protected static $key;

	/*日志初始化*/
	public static function init($config = []){
		$type = isset($config['type']) ? $config['type'] : 'File';
		$class = false !== strpos($type,'\\') ? $type : '\\wythe\\log\\driver' . ucwords($type);

		self::$config = $config;
		unset($config['type']);

		if(class_exists($class)){
			self::$driver = new $class($config);
		}else{
			return false;
		}

		APP:$debug && Log::record('[ LOG ] INIT' . $type,'info');
	}

	/*获取日志信息*/
	public static function getLog($type = ''){
		return $type ? self::$log[$type] : self::$log;
	}

	/*记录调试信息*/
	public static function record($msg,$type = 'log'){
		self::$log[$type][] = $msg;

		IS_CLI && self::save();
	}

	/*清空日志信息*/
	public static function clear(){
		self::$log = [];
	}

	/*设置当前日志记录的授权 key*/
	public static function key($key){
		self::$key = $key;
	}

	/*检查日志写入权限*/
	public static function check($config){
		return !self::$key || empty($config['allow_key']) || in_array(self::$key,$config['allow_key']);
	}

	/*保存调试信息*/
	public static function save(){
		if(empty(self::$log)){
			return true;
		}

		is_null(self::$driver) && self::init(Config::get('log'));

		/*检测日志写入权限*/
		if(!self::check(self::$config)){
			return false;
		}

		if(empty(self::$config['level'])){
			$log = self::$log;
			if(!App::$deug && isset($log['debug'])){
				unset($log['debug']);
			}
		}else{
			$log = [];
			foreach (self::$config['level'] as $level) {
				if(isste(self::$log[$level])){
					$log[$level] = self::$log[$level];
				}
			}
		}

		if($result = self::$driver->save($log)){
			self::$log = [];
		}

		Hook::listen('log_write_done',$log);

		return $result;
	}

	/*写入日志记录*/
	public static function write($msg,$type = 'log',$force = false){
		$log = self::$log;

		if(true !== $force && !empty(self::$config['level']) && !in_array($type,self::$config['level'])){
			return false;
		}

		$log[$type][] = $msg;

		Hook::listen('log_write',$log);

		is_null(self::$driver) && self::init(Config::get('log'));

		//写入日志
		if($result = self::$driver->save($log)){
			self::$log = [];
		}
		return $result;
	}

	/*静态方法调用*/
	public static function __callStatic($method,$args){	
		if(in_array($method,self::$type)){
			array_push($args,$method);
			call_user_func_array('\\wythe\\Log::record',$args);
		}
	}
}