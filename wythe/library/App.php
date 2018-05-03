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
class App{
	/*应用配置参数*/
	protected static $config = [
		'app_namespace'=>'application',
		'default_timezone'=>''
	];

	/*APP类配置*/
	protected static $appConfig = [
		'config_path'=>'',
		'depr'=>'/',
		'ext'=>'.php',
		'module_config_path'=>'',
		'module_config_name'=>'config',
	];

	/*当前路由*/
	protected static $dispatch;

	/*应用程序入口*/
	public static function run($config){
		/*1.加载app类配置*/
		self::$appConfig = array_merge(self::$appConfig,$config);

		/*2.加载应用配置文件*/
		self::$config = array_merge(self::$config,include self::$appConfig['config_path']);

		/*3.系统参数设置*/
		date_default_timezone_set(self::$config['default_timezone']);//设置系统时区	
		//Lang::range($config['default_lang']);//默认语言	 	
		//$config['lang_switch_on'] && Lang::detect();//是否开启语言自动检测

		/*4.获取请求信息*/
		$request = Request::instance();

		/*5.获取路由*/
		self::$dispatch = Route::routeStart(self::$config['route'],$request->pathInfo,$request->domain);

		/*6.加载应用*/
		Loader::addNamespace(self::$config['app_namespace'],APP_PATH);
		$data = self::exec();
	}

	/*执行应用*/
	public static function exec(){
        switch (self::$dispatch['type']) {
        	case 'pathInfo':
            case 'module': //调用模块控制器方法 
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
    	$path = self::$appConfig['module_config_path'] . $module . self::$appConfig['depr'] . self::$appConfig['module_config_name'] . self::$appConfig['ext'];
    	if(is_file($path)){
    		self::$config = array_merge(self::$config,include $path);
    	}
    	/*2.执行*/
        $requestController = '\\'.self::$config['app_namespace'].'\\'.($module ? $module.'\\' : '').'controller' .'\\'.$controller;
        if(class_exists($requestController)){
        	$controller = new $requestController();
        	if(method_exists($controller,$action)){
        		$data = $controller->$action();
        	}else{
        		echo 'no action';
        	}    
        }else{
        	echo 'no controller ';
        }
    }
}