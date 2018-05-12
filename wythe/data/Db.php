<?php

namespace wythe\data;
class Db{

    /*数据库连接句柄*/
    private static $instance = [];

    /*连接数据库*/
    public static function connect($config = []){
        $name = md5(serialize($config));
        /*需要重新连接*/
        if(!isset(self::$instance[$name])){
            /*1.根据数据库类型选择驱动*/
            $class = __NAMESPACE__.'\\db\\'.ucwords($config['type']);
            /*2.验证驱动是否存在*/

            /*3.生成连接实例*/
            self::$instance[$name] = new $class($config);           
        }

        return self::$instance[$name];
    }
}