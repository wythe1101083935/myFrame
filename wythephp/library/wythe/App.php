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
	protected static $routeMust;

	/*请求调度分发*/
	protected static $dispatch;

	/*额外加载文件*/
	protected static $file = [];


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
			$config = self::loadConfig();
			//XXX后缀???
			self::$suffix = $config['class_suffix'];
			//设置调试模式
			self::debugSet($config['app_debug']);
			//自定义命名空间
			!empty($config['root_namespace']) ? Loader::addNamespace($config['root_namespace']) : '';
			//设置系统时区
			date_default_timezone_set($config['default_timezone']);
			//默认语言
		 	//Lang::range($config['default_lang']);
		 	//是否开启语言自动检测
		 	//$config['lang_switch_on'] && Lang::detect();

		/*4.请求--URL路由放在请求中*/
			//获取请求信息
			$request = Request::instance();
		/*5.获取路由调度信息*/
            $dispatch = self::$dispatch;
            // 未设置调度信息则进行 URL 路由检测
            if (empty($dispatch)) {
                $dispatch = self::routeCheck($request, $config);
            }            
		/*5.执行应用*/

		

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

	/*加载配置文件*/
	private static function loadConfig($module = ''){
		/*定位模块目录,当module为空时加载的是应用配置文件，当module为具体模块，加载模块配置文件*/
		$module = $module ? $module . DS : '';

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

            // 加载公共文件
            $path = APP_PATH . $module;
            if (is_file($path . 'common' . EXT)) {
                include $path . 'common' . EXT;
            }
		}
		return Config::get();
	}

	/*URL路由检测*/
	public static function routeCheck($request,array $config){
        $path = $request->attr('path');
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

	/*执行应用*/
	public function exec($dispatch,$config){
        switch ($$dispatch['type']) {
            case 'redirect': // 重定向跳转
                $data = Response::create($dispatch['url'], 'redirect')
                    ->code($dispatch['status']);
                break;
            case 'module': // 模块/控制器/操作
                $data = self::module(
                    $dispatch['module'],
                    $config,
                    isset($dispatch['convert']) ? $dispatch['convert'] : null
                );
                break;
            case 'controller': // 执行控制器操作
                $vars = array_merge(Request::instance()->param(), $dispatch['var']);
                $data = Loader::action(
                    $dispatch['controller'],
                    $vars,
                    $config['url_controller_layer'],
                    $config['controller_suffix']
                );
                break;
            case 'method': // 回调方法
                $vars = array_merge(Request::instance()->param(), $dispatch['var']);
                $data = self::invokeMethod($dispatch['method'], $vars);
                break;
            case 'function': // 闭包
                $data = self::invokeFunction($dispatch['function']);
                break;
            case 'response': // Response 实例
                $data = $dispatch['response'];
                break;
            default:
                throw new \InvalidArgumentException('dispatch type not support');	
        }
        return $data;	
	}

    /**
     * 执行模块
     * @access public
     * @param array $result  模块/控制器/操作
     * @param array $config  配置参数
     * @param bool  $convert 是否自动转换控制器和操作名
     * @return mixed
     * @throws HttpException
     */
    public static function module($result, $config, $convert = null)
    {
        if (is_string($result)) {
            $result = explode('/', $result);
        }

        $request = Request::instance();

        if ($config['app_multi_module']) {
            // 多模块部署
            $module    = strip_tags(strtolower($result[0] ?: $config['default_module']));
            $bind      = Route::getBind('module');
            $available = false;

            if ($bind) {
                // 绑定模块
                list($bindModule) = explode('/', $bind);

                if (empty($result[0])) {
                    $module    = $bindModule;
                    $available = true;
                } elseif ($module == $bindModule) {
                    $available = true;
                }
            } elseif (!in_array($module, $config['deny_module_list']) && is_dir(APP_PATH . $module)) {
                $available = true;
            }

            // 模块初始化
            if ($module && $available) {
                // 初始化模块
                $request->module($module);
                $config = self::loadConfig($module);

                // 模块请求缓存检查
                $request->cache(
                    $config['request_cache'],
                    $config['request_cache_expire'],
                    $config['request_cache_except']
                );
            } else {
                throw new HttpException(404, 'module not exists:' . $module);
            }
        } else {
            // 单一模块部署
            $module = '';
            $request->module($module);
        }

        // 设置默认过滤机制
        $request->filter($config['default_filter']);

        // 当前模块路径
        App::$modulePath = APP_PATH . ($module ? $module . DS : '');

        // 是否自动转换控制器和操作名
        $convert = is_bool($convert) ? $convert : $config['url_convert'];

        // 获取控制器名
        $controller = strip_tags($result[1] ?: $config['default_controller']);
        $controller = $convert ? strtolower($controller) : $controller;

        // 获取操作名
        $actionName = strip_tags($result[2] ?: $config['default_action']);
        $actionName = $convert ? strtolower($actionName) : $actionName;

        // 设置当前请求的控制器、操作
        $request->controller(Loader::parseName($controller, 1))->action($actionName);

        // 监听module_init
        Hook::listen('module_init', $request);

        try {
            $instance = Loader::controller(
                $controller,
                $config['url_controller_layer'],
                $config['controller_suffix'],
                $config['empty_controller']
            );
        } catch (ClassNotFoundException $e) {
            throw new HttpException(404, 'controller not exists:' . $e->getClass());
        }

        // 获取当前操作名
        $action = $actionName . $config['action_suffix'];

        $vars = [];
        if (is_callable([$instance, $action])) {
            // 执行操作方法
            $call = [$instance, $action];
            // 严格获取当前操作方法名
            $reflect    = new \ReflectionMethod($instance, $action);
            $methodName = $reflect->getName();
            $suffix     = $config['action_suffix'];
            $actionName = $suffix ? substr($methodName, 0, -strlen($suffix)) : $methodName;
            $request->action($actionName);

        } elseif (is_callable([$instance, '_empty'])) {
            // 空操作
            $call = [$instance, '_empty'];
            $vars = [$actionName];
        } else {
            // 操作不存在
            throw new HttpException(404, 'method not exists:' . get_class($instance) . '->' . $action . '()');
        }

        Hook::listen('action_begin', $call);

        return self::invokeMethod($call, $vars);
    }
}