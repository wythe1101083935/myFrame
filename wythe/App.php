<?php
/**
 +----------------------------------------------------------
 * 框架流程类
 +----------------------------------------------------------
 * CODE:
 +----------------------------------------------------------
 * TIME:2018-05-03 14:42:36
 +----------------------------------------------------------
 * author:Wythe(汪志虹)
 +----------------------------------------------------------
 */
namespace wythe;
use wythe\system\Loader;
use wythe\system\Request;
use wythe\system\Route;
/*系统常量定义*/
define('WYTHE_VERSION','1.0.0');
/*定义文件全局配置*/
//类文件后缀
define('EXT','.php');
//定义配置文件后缀，默认使用类文件后缀
defined('CONF_EXT') or define('CONF_EXT',EXT);	
//获取系统路径分隔符														
define('DS',DIRECTORY_SEPARATOR);
//定义环境变量的配置前缀
defined('ENV_PREFIX') or define('ENV_PREFIX','PHP_');
//判断是不是命令行模式
define('IS_CLI',PHP_SAPI == 'cli' ? true : false);
//判断是不是在windows下
define('IS_WIN',strpos(PHP_OS,'WIN') !== false);

/*使用常量给文件布局*/

//定义框架启动目录	
defined('WYTHE_PATH') or define('WYTHE_PATH',__DIR__ . DS);
	//定义框架类库目录	
	define('SYSTEM_PATH',WYTHE_PATH . 'system' . DS);

//定义应用目录
defined('APP_PATH') or define('APP_PATH',dirname($_SERVER['SCRIPT_FILENAME']));
//定义配置文件目录
defined('CONF_PATH') or define('CONF_PATH',APP_PATH);
//定义应用根目录
defined('ROOT_PATH') or define('ROOT_PATH',dirname(realpath(APP_PATH)) . DS);
	//定义扩展目录
	defined('EXTEND_PATH') or define('EXTEND_PATH', ROOT_PATH .'extend' . DS);
	//定义compose扩展目录
	defined('VENDOR_PATH') or define('VENDOR_PATH', ROOT_PATH . 'vendor' . DS);
	//定义运行时的生成目录
	defined('RUNTIME_PATH') or define('RUNTIME_PATH',ROOT_PATH . 'runtime' . DS);
		//定义日志目录
		defined('LOG_PATH') or define('LOG_PATH',RUNTIME_PATH . 'log' .DS);
		//定义缓存目录
		defined('CACHE_PATH') or define('CACHE_PATH',RUNTIME_PATH . 'cache' . DS);
		//定义临时文件目录
		defined('TEMP_PATH') or define('TEMP_PATH',RUNTIME_PATH . 'temp' . DS);
App::run();
class App{
	/*应用配置参数*/
	protected static $config = [
		'default_timezone'=>'',
	];

	/*配置文件路径*/
	protected static $configPath;

	/*当前请求*/
	protected static $request;

	/*当前路由*/
	protected static $dispatch;

	/*应用入口*/
	public static function run(){
		/*1.手动加载Loader*/
			require SYSTEM_PATH . 'Loader.php';

		/*2.注册自动加载，并注册框架根命名空间*/
			Loader::register([
				'is_win'=>IS_WIN,
			    'depr'=>DS,
			    'ext'=>EXT,
			]);
			Loader::addNamespace('wythe',WYTHE_PATH);

		/*3.加载应用配置文件并设置系统参数*/
			//1)获取配置文件路径
			self::$configPath = APP_PATH . 'config'. EXT; 
			//2)加载应用配置文件
			self::$config = (include self::$configPath) + self::$config;
			//3)注册应用根命名空间
			Loader::addNamespace(self::$config['app_namespace'],APP_PATH);
			//4)注册额外根命名空间
			Loader::addNamespace(self::$config['root_namespace']);
			//5)设置系统时区
			date_default_timezone_set(self::$config['default_timezone']);
			//6)设置系统语言
			//Lang::range($config['default_lang']);//默认语言	 	
			//$config['lang_switch_on'] && Lang::detect();//是否开启语言自动检测

		/*4.获取请求信息*/
			self::$request = Request::instance();

		/*5.获取路由*/
			self::$dispatch = Route::routeStart(self::$config['route'],self::$request->pathInfo,self::$request->domain);

		/*6.执行应用*/
			$data = self::exec();	

		/*7.写入日志*/
			//\tools\Log::write();	
	}
	
	/*执行应用*/
	public static function exec(){
        switch (self::$dispatch['type']) {
        	case 'pathInfo':
            case 'module': 
            	list($module,$controller,$action)= explode('/',self::$dispatch['route']);
                $data = self::module(strtolower($module),ucfirst($controller),$action);
                break;
            default:
                $data = 'error route';	
        }
        return $data;	
	}

    /*执行控制器*/
    public static function module($module,$controller,$action){
    	/*1.加载模块配置文件*/
    	$path = APP_PATH . $module . DS . 'config' . EXT;

    	if(is_file($path)){
    		self::$config = (include $path) + self::$config;
    	}

    	/*2.创建控制器访问路径*/
        $requestController = '\\'.self::$config['app_namespace'].'\\'.($module ? $module.'\\' : '').'controller' .'\\'.$controller;

        /*3.验证控制器是否存在*/
        if(!class_exists($requestController)){
        	echo 'no controller';
        	return false;
        }

        /*4.实例化控制器对象*/
        $controller = new $requestController();

        /*5.验证操作是否存在*/
        if(!method_exists($controller,$action)){
        	echo 'no action';
        	return false;
        }

        /*6.执行操作*/   
        return $controller->$action();
    }
}


