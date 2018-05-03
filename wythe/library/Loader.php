<?php

namespace wythe;

class Loader{

	//类名映射
	protected static $map = [];

	//PSR-4命名空间前缀长度映射
	private static $prefixLengthsPsr4 = [];

	//PSR-4 加载目录
	private static $prefixDirsPsr4 = [];

	//PSR-4 加载失败的回退目录
	private static $fallbackDirsPsr4 = [];

	//自动加载的文件
	private static $autoloadFiles = [];

    //参数配置
    protected static $config = [
        'is_win'=>false,
        'cache'=>false,
        'cache_path'=>'',//缓存路径
        'default_namespace'=>[],
        'depr'=>'/',//分隔符
    ];
	//自动加载函数
	public static function autoload($class){
		//加载类所在文件
		if($file = self::findFile($class)){
			//非Win环境 不严格区分大小写
			if(!self::$config['is_win'] || pathinfo($file,PATHINFO_FILENAME) == pathinfo(realpath($file),PATHINFO_FILENAME)){
				include $file;
				return true;
			}
		}
		return false;
	}

	//查找文件
	private static function findFile($class){
		//1.先从映射里找
		if(!empty(self::$map[$class])){
			return self::$map[$class];
		}
		//2.从PSR-4空间里面找
        $logicalPathPsr4 = strtr($class, '\\', self::$config['depr']) . EXT;
        $first           = $class[0];
        if (isset(self::$prefixLengthsPsr4[$first])) {
            foreach (self::$prefixLengthsPsr4[$first] as $prefix => $length) {
                if (0 === strpos($class, $prefix)) {
                    foreach (self::$prefixDirsPsr4[$prefix] as $dir) {
                        if (is_file($file = $dir . self::$config['depr'] . substr($logicalPathPsr4, $length))) {
                            return $file;
                        }
                    }
                }
            }
        }
		//3.从PSR-4回退空间里找
        foreach (self::$fallbackDirsPsr4 as $dir) {
            if (is_file($file = $dir . self::$config['depr'] . $logicalPathPsr4)) {
                return $file;
            }

        }
		//4.没有则设置映射 为false
		return self::$map[$class] = false;
	}

	//注册自动加载
    public static function register($config)
    {
        self::$config = array_merge(self::$config,$config);

        // 注册默认的命名空间
        self::addNamespace(self::$config['default_namespace']);

        // 加载缓存类库映射文件
        /*if (self::$config['cache'] && is_file($config['cache_path'])) {
            self::addClassMap($config['cache_path']);
        }*/

        // 自动加载 extend 目录
        //self::$fallbackDirsPsr4[] = rtrim(EXTEND_PATH, self::$config['depr']);

        // 系统函数自动加载
        spl_autoload_register('wythe\\Loader::autoload', true, true);
    }

    /*增加空间定义*/
    public static function addNamespace($namespace,$path = ''){
    	if(is_array($namespace)){
    		foreach ($namespace as $prefix => $path) {
    			self::addPsr4($prefix . '\\',trim($path,self::$config['depr']),true);
    		}
    	}else{
    		self::addPsr4($namespace . '\\' ,rtrim($path,self::$config['depr']),true);
    	}
    }

    /*增加PSR-4加载目录*/
    private static function addPsr4($prefix,$paths,$prepend = false){
    	if(!isset(self::$prefixDirsPsr4[$prefix])){
    		//注册新的命名空间
    		$length = strlen($prefix);
    		self::$prefixLengthsPsr4[$prefix[0]][$prefix] = $length;
    		self::$prefixDirsPsr4[$prefix] = (array) $paths;
    	}
    }

    /*增加类库映射*/
    /*public static function addClassMap($class, $map = ''){
        if (is_array($class)) {
            self::$map = array_merge(self::$map, $class);
        } else {
            self::$map[$class] = $map;
        }
    }*/

}