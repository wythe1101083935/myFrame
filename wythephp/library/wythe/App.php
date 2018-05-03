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
	/*应用命名空间*/
	protected static $dispatch;

	/*应用配置参数*/
	protected static $config = [];

	/*APP类配置*/
	protected static $appConfig = [
		'config_path'=>'',//应用配置文件路径
		'depr'=>'/',
		'module_path'=>'/',//模块配置文件路径
	];

	/*应用程序入口*/
	public static function run($config){
		/*1.加载app类配置*/
		self::$appConfig = array_merge(self::$appConfig,$config);

		/*2.加载应用配置文件*/
		self::$config = array_merge(self::$config,include self::$appConfig['config_path']);

		/*3.系统参数设置*/
		date_default_timezone_set($config['default_timezone']);//设置系统时区	
		//Lang::range($config['default_lang']);//默认语言	 	
		//$config['lang_switch_on'] && Lang::detect();//是否开启语言自动检测

		// /*4.获取请求信息*/
		$request = Request::instance();

		dump($request->pathInfo);

		// /*5.获取路由*/
		self::$dispatch = Route::routeStart([
           		'rule_path'=>'',//路由配置文件
           		'cache'=>false, //是否缓存路由配置
           		'cache_path'=>''//缓存路径
           	],$request->url);

		/*6.加载应用*/
		//Loader::addNamespace(self::$config['app_namespace'],APP_PATH);
		//self::exec();				
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
    public static function module($module,$controller,$action){
        $requestController = '\\'.self::$config['app_namespace'].'\\'.($module ? $module.'\\' : '').'controller' .'\\'.$controller;
        if(class_exists($requestController)){
            $data = (new $requestController())->$action();
        }
    }
}