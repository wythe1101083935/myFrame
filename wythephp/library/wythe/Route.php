<?php

namespace wythe;
class Route{
	private static $rules = [
		'domain' => [ 
			'alias'=>[],//域名路由别名
		], 		//域名路由
		'*'	=> 		[
			'alias'=>[], //通用路由别名

		],			//通用路由
		'pattern'=>	[],			//全局变量验证
		'name'	=> [
			'*'=>[],
			'domain'=>[]
		],			//逆向路由，用来生成路由
		'option'=>[]
	];

    private static $config = [
        'config_path' => '',//配置路径
        'cache' => false, //缓存
        'cache_path' => '',//缓存路径
    ]


    /*检测路由*/  
    private static function check($url,$domain = false){
        $name = '';
        $param = '';
        $rules = self::$rules['domain'][$domain][$name] ? :
                    (self::$rules['*'][$name] ? : null);
    	/*检测路由*/
    	if(!is_null($rules)){
    		/*刷选当前使用的路由是哪一个*/      
            $rule = $rules['rule'];

            $urlVar = explode('/',$param);

            $options = array_merge($rules['option'],self::$options);
            $pattern = array_merge($rules['pattern',self::$pattern]);
            if(is_array($rule)){
                foreach ($rule as $key => $val) {
                    $ruleVar = $val['var'];
                    $options = array_merge($val['option'],$options);
                    $return = self::match($ruleVar,$urlVar,$options,$pattern);
                    if($return) 
                        return array('route'=>$rule['route'],'param'=>$urlVar,'type'=>'');      
                }
            }else{
               $ruleVar = $rules['var'];
               $return = self::match($ruleVar,$urlVar,$options,$pattern);
               if($return) 
                 return array('route'=>$rules['name']['route'],'param'=>$urlVar,'type'=>'');
            }
    	}
        /*没有检测到路由*/
         

    }

    /*路由验证*/
    private static function match($ruleVar,$urlVar,$options,$pattern){
        /*选项验证*/
        foreach ($options as $key => $val) {
            /*验证全匹配*/
            if(isset($options['complete_match']) && $options['complete_match']){
                if(count($ruleVar)!=count($urlVar)){
                    return false;
                }
            }
            /**/
        }
        /*参数验证*/
        foreach ($ruleVar as $key => $isRequired) {
            /*必填，而url没有*/
            if($isRequired && !isset($url[$key])){
                return false;
            }
            /*变量有验证规则*/
            if(isset($pattern[$key])){
                /*闭包验证*/
                if ($pattern[$key] instanceof \Closure) {
                    $result = call_user_func_array($pattern[$key], [$key]);
                    if (false === $result) return false;   
                /*正则验证*/
                } elseif (!preg_match(0 === strpos($pattern[$key], '/') ? $pattern[$key] : '/^' . $pattern[$key] . '$/', $val)) {
                    return false;
                }                 
            }
        }
        return true;
    }

    /*设置路由参数*/
    private static function initRule($rule=false){
        if(false===$rule){
            $rule = include self::$config['config_path'];
        }
        /*测试用DEBUG*/
        self::$rules = $rule;
        /*设置路由生成数组*/

        /*生成根据请求方式的快捷判断*/
    }

    /*路由接口*/
    public static function route($config,$url,$domain,$rule=false){
        /*1.加载配置文件*/
        self::$config = array_merge(self::$config,$config);
        /*2.加载路由*/
        self::$config['cache'] ?
        self::$rules = include self::$config['cache_path'] :
        self::$rules = self::initRule($rule); 
        /*3.检查路由*/
        return self::check();
    }



}
 

