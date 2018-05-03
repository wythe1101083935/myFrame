<?php
/*
	应用程序类
使用类：
	Config:纯静态类，一次应用调用一次
	Route:纯静态类，一次应用调用一次
	Request:纯静态类，内部变量调用才生成一次
*/
namespace wythe;
class App{
	/*是否初始化过*/
	protected static $isConifgLoad = false;

	/*当前模块路径*/
	public static $modulePath;

	/*应用调试模式*/
	public static $debug = true;

	/*应用命名空间*/
	public static $namespace = 'application';

	/*请求调度分发*/
	protected static $dispatch;

	protected static $config = [];

	/*应用程序入口*/
	public static function run(){
		/*1.命名空间*/
			/*增加应用命名空间*/
			if(defined('APP_NAMESPACE')){
				self::$namespace = APP_NAMESPACE;
			}
			Loader::addNamespace(self::$namespace,APP_PATH);

		/*2.应用配置文件加载及系统设置*/
			//加载应用配置文件
			Config::loadConfig();
			//应用类配置
			

			//自定义命名空间
			//!empty($config['root_namespace']) ? Loader::addNamespace($config['root_namespace']) : '';
			//设置系统时区
			//date_default_timezone_set($config['default_timezone']);
			//默认语言
		 	//Lang::range($config['default_lang']);
		 	//是否开启语言自动检测
		 	//$config['lang_switch_on'] && Lang::detect();

		/*4.获取请求信息*/
			$request = Request::instance();

		/*5.获取路由调度信息*/
            self::$dispatch = Route::routeStart([
           		'rule_path'=>'',//路由配置文件
           		'cache'=>false, //是否缓存路由配置
           		'cache_path'=>''//缓存路径
           	],$request->url);
            
		/*5.执行应用*/
			self::exec();

		/*6.应用结束*/
	}
	/*调试模式*/
	public static function debugSet($debug){
			//应用调试模式
			self::$debug = $debug;
			if(!self::$debug){//不是调试，关闭错误输出
				ini_set('display_errors','Off');
			}elseif (!IS_CLI){
				//重新申请一块比较大的buffer
				if(ob_get_leve() > 0){
					$output = ob_get_clean();
				}
				ob_start();

				if(!empty($output)){
					echo $output;
				}
			}		
	}


	/*执行应用*/
	public function exec($dispatch,$config){
        switch ($$dispatch['type']) {
            case 'method': // 回调方法
                $vars = array_merge(Request::instance()->param(), $dispatch['var']);
                $data = self::invokeMethod($dispatch['method'], $vars);
                break;
            default:
                $data = 'error route';	
        }
        return $data;	
	}

    /*执行控制器*/
    public static function module($module,$controller,$action)
    {
        $requestController = '\\'.self::$namespace.'\\'.($module ? $module.'\\' ? '').'controller' .'\\'.$controller;
        if(class_exists($requestController)){
            $data = (new $requestController())->$action();
        }
    }
	/*加载应用配置文件*/
	public static function loadAppConfig(){

	}


	public static function loadConfig($module = ''){
		/*定位模块目录,当module为空时加载的是应用配置文件，当module为具体模块，加载模块配置文件*/
		$module = $module ? $module . DS : '';
		if($module){
			$module = $module . DS : '';
		}else{
			/*如果是加载应用配置,先加载惯例配置文件*/

		}
		/*加载配置文件*/
		if(self::$config['cache'])
		if(is_file(CONF_PATH . $module . 'config' .EXT)){
			self::$config = array_merge(self::$config,include CONF_PATH . $module .'config' . EXT);
		}

		//加载初始化文件
		if(is_file(APP_PATH . $module . 'init' .EXT)){
			include APP_PATH . $module . 'init' . EXT;
		} elseif (is_file(RUNTIME_PATH . $module . 'init' . EXT)){
			include RUNTIME_PATH. $module . 'init' . EXT; //直接加载缓存的配置文件
		} else {
			/*加载模块配置*/
			$config = Config::load(CONF_PATH . $module . 'config' . CONF_EXT);

			/*读取数据库配置文件*/
			$filename = CONF_PATH . $module . 'config' . CONF_EXT;
			Config::load($filename,'databse');

			/*读取扩展配置文件*/
			if(is_dir(CONF_PATH . $module . 'extra')){
				$dir = CONF_PATH . $module . 'extra';
				$files = scandir($dir);
				foreach ($files as $file) {
					if('.' . pathinfo($file,PATHINFO_EXTENSION) == CONF_EXT){
						$filename = $dir . DS . $file;
						Config::load($filename,pathinfo($file,PATHINFO_FILENAME));
					}
				}
			}
            // 加载应用状态配置
            if ($config['app_status']) {
                Config::load(CONF_PATH . $module . $config['app_status'] . CONF_EXT);
            }
		}
		return Config::get();
	}
}