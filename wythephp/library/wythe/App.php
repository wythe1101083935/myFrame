<?php
namespace wythe;

class App{
	/*是否初始化过*/
	protected static $init = false;

	/*当前模块路径*/
	public static $modulePath;

	/*应用调试模式*/
	public static $debug = true;

	/*应用命名空间*/
	public static $namespace = 'app';

	/*应用类库后缀*/
	public static $suffix = false;

	/*应用路由检测*/
	protected static $routeCheck;

	/*严格检测路由*/
	protected static $routeCheck;

	/*请求调度分发*/
	protected static $dispatch;

	/*额外加载文件*/
	protected static $file = [];


	/*应用程序入口*/
	public static function run(Request $request = null){
		/*1.初始化请求类，包括请求的所有信息*/
		$requrest = is_null($request) ? Request::instance() : $request;
		
		/*2.应用配置参数初始化*/
		$config = self::init();

		/*3.模块控制器绑定*/
		if(defined('BIND_MODULE')){
			BIND_MODULE && Route::bind(BIND_MODULE);
		} elseif ($config['auto_bind_module']){
			//入口自动绑定
			$name = pathinfo($request->baseFile(),PAHTINFO_FILENAME)
		}

		/*4.语言设置*/
		$request->filter($config['default_filter']);
		Lang::range($config['default_lang']);//默认语言



	}

	/*初始化应用返回配置信息*/
	public static function init(){
		if(empty(self:$init)){
			if(defined('APP_NAMESPACE')){
				self::$namespace = APP_NAMESPACE;
			}

			Loader::addNamespace($self::$namespace,APP_PATH);

			//加载配置文件
			$config = self::loadConfig();
			self::$suffix = $config['class_suffix'];

			//应用调试模式
			self::$debug = Env::get('app_debug',Config::get('app_debug'));

			/*根据配置文件开启应用*/

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

			if(!empty($config['root_namespace'])){
				Loader::addNamespace($config['root_namespace']);
			}

			//加载额外文件，额外都去掉去掉
			/*if(!empty($config['extra_file_list'])){
				foreach($config['extra_file_list'] as $file){
					$file = strpos($file,'.') ? $file : APP_PATH . $file .EXT;
					if(is_file($file) && !isset(self::$file[$file])){
						include $file;
						self::$file[$file] = true;
					}
				}
			}*/
			/*设置系统时区*/
			date_default_timezone_set($config['default_timezone']);

			/*监听app_init*/
			Hook::listen('app_init');

			self::$init = true;

		}
		return Config::get();
	}

	/*初始化应用或模块*/
	private static function loadConfig($module = ''){
		/*定位模块目录*/
		$module = $module ? $module . DS : '';

		//加载初始化文件
		if(is_file(APP_PATH . $module . 'init' .EXT)){
			include APP_PATH . $module . 'init' . EXT;
		} elseif (is_file(RUNTIME_PATH . $module . 'init' . EXT)){
			include RUNTIME_PATH. $module . 'init' . EXT;
		} else {
			/*加载模块配置*/
			$config = Config::load(CONF_PAHT . $module . 'config' . CONF_EXT);

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
						Config::load($filename,pathinfo($file,PATHINFO_FILENAME))
					}
				}
			}
            // 加载应用状态配置
            if ($config['app_status']) {
                Config::load(CONF_PATH . $module . $config['app_status'] . CONF_EXT);
            }

            // 加载行为扩展文件
            if (is_file(CONF_PATH . $module . 'tags' . EXT)) {
                Hook::import(include CONF_PATH . $module . 'tags' . EXT);
            }

            // 加载公共文件
            $path = APP_PATH . $module;
            if (is_file($path . 'common' . EXT)) {
                include $path . 'common' . EXT;
            }

            // 加载当前模块语言包
            if ($module) {
                Lang::load($path . 'lang' . DS . Request::instance()->langset() . EXT);
            }
		}
		return Config::get();
	}
}