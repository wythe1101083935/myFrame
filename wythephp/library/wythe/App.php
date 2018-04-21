<?php
namespace wythe;

class App{
	/*是否初始化过*/
	protected static $isConifgLoad = false;

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
		/*1.加载配置参数文件*/
		self::loadConfig();

		/*2.解析配置参数*/
		$config = self::parseConfig();

		/*3.系统设置*/	
		date_default_timezone_set($config['default_timezone']);/*设置系统时区*/

		Lang::range($config['default_lang']);//默认语言

		$config['lang_switch_on'] && Lang::detect();//是否开启语言自动检测
		
		/*4.请求数据加载*/
		$requrest = is_null($request) ? Request::instance() : $request;

		/*5.请求设置默认过滤规则*/
		$request->filter($config['default_filter']); //加载默认过滤规则
	
		/*6.根据请求覆盖默认语言并加载语言文件*/
		$request->langset(Lang::range());
		Lang::load([
			WYTHE_PATH . 'lang' . DS . $request->langset() . EXT,
			APP_PATH . 'lang' . DS .$request->langset() . EXT,
		]);

		/*7.入口模块控制器绑定*/
		if(defined('BIND_MODULE')){
			BIND_MODULE && Route::bind(BIND_MODULE);
		} elseif ($config['auto_bind_module']){
			//入口自动绑定
			$name = pathinfo($request->baseFile(),PAHTINFO_FILENAME)
		}
		
		/*8.监听路由开启事件app_dispatch*/
		Hook::listen('app_dispatch',self::$dispatch);

		/*9.获取应用调度信息*/
		if(empty(self::$dispatch)){
			$dispatch = self::routeCheck($request,$config);
		}
		$dispatch = self::$dispatch;
		
		/*10.request实例记录当前调度信息*/
		$request->dispatch($dispatch);

		/*11.记录路由和请求信息日志*/

		/*12.监听应用开始事件app_begin*/
		Hook::listen('app_begin',$displatch);

		/*13.请求缓存检查*/
		$request->cache(
			$config['request_cache'],
			$config['request_cache_expire'],
			$config['request_cache_except']
		);

		/*14.执行应用*/
		$data = self::exec($dispatch,$config);
		Loader::clearInstance();/*清空类的实例化,提前释放内存*/

		/*15.处理返回数据*/
		if($data instanceof Response){
			$response = $data;
		} elseif( !is_null($data)){
			$type = $request->isAjax() ?
			Config::get('default_ajax_return'):
			Config::get('default_return_type');
			$response = Response::create($data,$type);
		}else{
			$response = Response::create();
		}

		/*16.监听应用结束事件app_end*/
		Hook::listen('app_end',$response);

		/*17.返回结果*/
		return $reponse;
	}

	/*获取配置参数*/
	public static function parseConfig(){
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
			
			/*监听app_init*/
			Hook::listen('app_init');

			self::$init = true;
		}
		return Config::get();
	}

	/*加载配置文件*/
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

	/*URL路由检测*/
	public static function routeCheck($request,array $config){
		$path = $request->path();
		$depr = $config['pathinfo_depr'];
		$result = false;

		/*路由检测*/
		$check = !is_null(self::$routeCheck) ? self::$routeCheck : $config['url_route_on'];

		if($check){
			if(is_file(RUNTIME_PATH . 'route.php')){
				$rules = include RUNTIME_PATH .'route.php';
				is_array($rules) && Route::rules($rules);
			}else{
				$files = $config['route_config_file'];
				foreach ($files as $file) {
					if(is_file(CONF_PATH . $file . CONF_EXT)){
						$rules = include CONF_PATH .$file .CONF_EXT;
						is_array($rules) && Route::import($rules);
					}
				}				
			}

			//路由检测
			$result = Route::check($request,$path,$depr,$config['url_domain_deploy']);

			$must = self::$routeMust ? : $config['url_route_must'];

			if($must && false === $result){
				//抛出路由无效异常
			}	
		}

		//路由无效， 解析默认
		if(false === $result){
			$result = Route::parseUrl($path,$depr,$config['controller_auto_search']);
		}
		return $result;
	}
}