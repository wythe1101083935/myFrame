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

	//自动加载函数
	public static function autoload($class){
		//加命名空间别名;要别名干嘛，不要
		//加载类所在文件
		if($file = self::findFile($class)){
			//非Win环境 不严格区分大小写
			if(!IS_WIN || pathinfo($file,PATHINFO_FILENAME) == pathinfo(realpath($file),PATHINFO_FILENAME)){
				__include_file($file);
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
        $logicalPathPsr4 = strtr($class, '\\', DS) . EXT;
        $first           = $class[0];
        if (isset(self::$prefixLengthsPsr4[$first])) {
            foreach (self::$prefixLengthsPsr4[$first] as $prefix => $length) {
                if (0 === strpos($class, $prefix)) {
                    foreach (self::$prefixDirsPsr4[$prefix] as $dir) {
                        if (is_file($file = $dir . DS . substr($logicalPathPsr4, $length))) {
                            return $file;
                        }
                    }
                }
            }
        }
		//3.从PSR-4回退空间里找
        foreach (self::$fallbackDirsPsr4 as $dir) {
            if (is_file($file = $dir . DS . $logicalPathPsr4)) {
                return $file;
            }

        }
		//4.没有则设置映射 为false
		return self::$map[$class] = false;
	}
	//注册自动加载
    public static function register($autoload = null)
    {


        // Composer 自动加载支持,不用，不要

        // 注册默认的命名空间
        self::addNamespace([
            'wythe'    => LIB_PATH . 'wythe' . DS,
            'behavior' => LIB_PATH . 'behavior' . DS,
            'traits'   => LIB_PATH . 'traits' . DS,
        ]);
        // 加载缓存类库映射文件
        /*if (is_file(RUNTIME_PATH . 'classmap' . EXT)) {
            self::addClassMap(__include_file(RUNTIME_PATH . 'classmap' . EXT));
        }*/

        /*自动加载composer*/
        //self::loadComposerAutoloadFiles();

        // 自动加载 extend 目录
        self::$fallbackDirsPsr4[] = rtrim(EXTEND_PATH, DS);


        // 系统函数自动加载
        spl_autoload_register($autoload ?: 'wythe\\Loader::autoload', true, true);
    }
    /*增加空间定义*/
    public static function addNamespace($namespace,$path = ''){
    	if(is_array($namespace)){
    		foreach ($namespace as $prefix => $path) {
    			self::addPsr4($prefix . '\\',trim($path,DS),true);
    		}
    	}else{
    		self::addPsr4($namespace . '\\' ,rtrim($path,DS),true);
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
}

/*加载函数*/
function __include_file($file){
	return include $file;
}
function __require_file($file){
	return require $file;
}