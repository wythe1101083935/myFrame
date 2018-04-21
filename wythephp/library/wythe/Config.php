<?php
namespace wythe;

class Config
{
	/*配置参数*/
	private static $config = [];

	/*参数作用域*/
	private static $range = '_sys_';

	/*设定参数的作用域*/
	public static function range($range){
		self::$range = $range;
		if(!isset(self::$config[$range])) self::$config[$range] = [];
	}

	/*解析配置文件或内容*/
	public static function parse($config,$type = '',$name = '',$range = ''){
		$range = $range ? : self::$range;

		if(empty($type)) $type = pathinfo($config,PATHINFO_EXTENSION);

		$class = false !== strpos($type,'\\') ? 
			$type :
			'\\wythe\\config\\driver\\' . uswords($type);

		return self::set((new $class())->parse($config),$name,$range)
	}

	/*设置配置参数*/
	public static function set($name,$value = null,$range = ''){
		$range = $range ? : self::$range;

		if(!isset(self::$config[$range])) self::$config[$range] = [];

		/*字符串则表示单个配置设置*/
		if(is_string($name)){
			if(!strpos($name,'.')){
				self::$config[$range][strtolower($name)] = $value;
			}else{
				$name = explode('.',$name,2);
				self::$config[$range][strtolower($name[0])][$name[1]] = $value;
			}
			return $value;
		}

		/*数组批量设置*/
		if(is_array($name)){
			if(!empty($value)){
				self::$config[$range][$value] = isset(self::$config[$range][$value]) ?
				array_merge(self::$config[$range][$value],$name) :
				$name;

				return self::$config[$range][$value];
			}

			return self::$config[$range] = array_merge(self::$config[$range],array_change_key_case($name));
		}

		return self::$config[$range];//为空取出所有值
	}

	/*重置配置参数*/
	public static function reset($range = ''){
		$range = $range ? : self::$range;

		if(true === $range){
			self::$config = [];
		} else {
			self::$config[$range] = [];
		}
	}

	/*获取配置参数，为空则获取所有配置*/
	public static function get($name = null, $range = ''){
		$range = $range ? : self::$range;

		/*无参数时获取所有*/
		if(empty($name) && isset(self::$config[$range])){
			return self::$config[$range];
		}
		/*直接取值*/
		if(!strpos($name,'.')){
			$name = strtolower($name);
			return isset(self::$config[$range][$anme]) ?
					self::$config[$range][$name] : null;
		/*取数组中的值*/
		} else {
			$name = explode('.',$name,2);
			$name[0] = strtolower($name[0]);
			if(!isset(self::$config[$range][$name[0]])){
				/*载入额外配置*/
				$module = Request::instance()->module();
				$file = CONF_PATH . ($module ? $module . DS : ''). 'extra'. DS . $name[0] . CONF_EXT;
				is_file($file) && self::load($file,$name[0]);
			}			

			return isset(self::$config[$range][$name[0]][$name[1]]) ?
				self::$config[$range][$name[0]][$name[1]] : 
				null;
		}

	}

	/*加载配置文件*/
	public static function load($file,$name = '',$range = ''){
		$range = $range ? : self::$range;

		if(!isset(self::$config[$range])) self::$config[$range] = [];

		if(is_file($file)){
			$name = strtolower($name);
			$type = pathinfo($file,PATHINFO_EXTENSION);

			if('php' == $type){
				return self::set(include $file,$name,$range);
			}

			if('yaml' == $type && function_exists('yaml_parse_file')){
				return self::set(yaml_parse_file($file),$name,$range);
			}
			return self::parse($file,$type,$name,$range);
		}
		return self::$config[$range];
	}


}