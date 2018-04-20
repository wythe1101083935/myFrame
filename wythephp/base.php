<?php

define('WYTHE_VERSION','1.0.0');

define('WYTHE_START_TIME',microtime(true));//应用开始时间

define('WYTHE_START_MEM',memory_get_usage());//应用初始占用内存


define('EXT','.php');//类文件后缀


define('DS',DIRECTORY_SEPARATOR);//获取系统路径分隔符

defined('WYTHE_PATH') or define('WYTHE_PATH',__DIR__ . DS);//定义框架启动目录


define('LIB_PATH',WYTHE_PATH . 'library' . DS);//定义框架类库目录

define('CORE_PATH',LIB_PATH . 'wythe' . DS); //定义框架核心文件目录


define('TRAIT_PATH',LIB_PATH . 'traits' .DS);// 定义traits目录


defined('APP_PATH') or define('APP_PATH',dirname($_SERVER['SCRIPT_FILENAME']));//定义应用目录

defined('ROOT_PATH') or define('ROOT_PATH',dirname(realpath(APP_PATH)) . DS);//定义应用更目录

defined('EXTEND_PATH') or define('EXTEND_PATH', ROOT_PATH .'extend' . DS);//定义扩展目录

defined('VENDOR_PATH') or define('VENDOR_PATH',ROOT_PATH . 'vendor' . DS);//定义compose扩展目录

defined('RUNTIME_PATH') or define('RUNTIME_PATH',ROOT_PATH . 'runtime' . DS);//定义运行时的生成目录

defined('LOG_PATH') or define('LOG_PATH',RUNTIME_PATH . 'log' .DS);//定义日志目录

defined('CACHE_PATH') or define('CACHE_PATH',RUNTIME_PATH . 'cache' . DS);//定义缓存目录

defined('TEMP_PATH') or define('TEMP_PATH',RUNTIME_PATH . 'temp' . DS);//定义临时文件目录

defined('CONF_PATH') or define('CONF_PATH',APP_PATH);//定义配置文件目录

defined('CONF_EXT') or define('CONF_EXT',EXT);//定义配置文件后缀，默认使用类文件后缀

defined('ENV_PREFIX') or define('ENV_PREFIX','PHP_');//定义环境变量的配置前缀

define('IS_CLI',PHP_SAPI == 'cli' ? true : false);//判断是不是命令行模式

define('IS_WIN',strpos(PHP_OS,'WIN') !== false);//判断是不是在windows下


//加载环境变量配置文件
if(is_file(ROOT_PATH . '.env')){//存在文件再加载
	$env = parse_ini_file(ROOT_PATH . '.env',true);
	//parse_ini_file;解析一个类似php.ini的文件，加true将不同区块的分成不同的数组下
	foreach ($env as $key => $val) {
		$name = EVN_PREFIX . strtoupeer($key);

		if(is_array($val)){//有区块的局部变量
			foreach ($val as $k => $v) {
				$item = $name . '_' . strtoupper($k);
				putenv("$item=$v");//设置环境变量
			}
		} else {
			putenv("$name=$val");//设置环境变量
		}
	}

}
//自动加载类包含
require CORE_PATH . 'Loader.php';

/*注册自动加载*/
\wythe\Loader::register();

/*注册错误和异常处理机制*/
//\wythe\Error::register();

/*加载惯例配置文件*/
//\wythe\Config::set(include WYTHE_PATH . 'convention' .Ext);