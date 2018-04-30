<?php

namespace wythe;
class Route{
    /*路由表*/
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

    /*配置*/
    private static $config = [
        'rule_path' => '',//配置路径
        'cache' => false, //缓存
        'cache_path' => '',//缓存路径
    ];

    /*当前路由*/
    public static $route = [
        'type'=>'';
    ];


    /*检测路由*/  
    private static function check($url,$domain = false){
        $url = rtrim($url,'/');
        $name = strstr($url,'/',true);
        $urlParam = explode('/',substr(strstr($url,'/'),1));
        $urlVar = [];
        for ($i=0; $i < count($urlParam); $i+=2) { 
            $urlVar[$urlParam[$i]] = isset($urlParam[$i+1]) ? $urlParam[$i+1] : null;
        }
        if(isset(self::$rules['domain'][$domain][$name])){
            $rules = self::$rules['domain'][$domain][$name];
        }elseif(isset(self::$rules['*'][$name])){
            $rules = self::$rules['*'][$name];
        }else{
            $rules = nulll;
        }
    	/*检测路由*/
    	if(!is_null($rules)){
    		/*刷选当前使用的路由是哪一个*/      
            $rule = $rules['rule'];
            $options = array_merge($rules['option'],self::$rules['option']);
            $pattern = array_merge($rules['pattern'],self::$rules['pattern']);
            if(is_array($rule)){
                foreach ($rule as $key => $val) {
                    $ruleVar = $val['var'];
                    $options = array_merge($val['option'],$options);
                    $return = self::match($ruleVar,$urlVar,$options,$pattern);
                    if($return) 
                        return array('route'=>$rule['route'],'param'=>$urlVar,'type'=>'module');      
                }
            }else{
               $ruleVar = $rules['var'];
               $return = self::match($ruleVar,$urlVar,$options,$pattern);
               if($return) 
                 return array('route'=>$rules['route'],'param'=>$urlVar,'type'=>'module');
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
            if($isRequired && !isset($urlVar[$key])){
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
            $rule = include self::$config['rule_path'];
          //$rule = include 'C:\wamp\www\mylunzi\application\route.php';
        }
        /*测试用DEBUG*/
        return $rule;
        /*设置路由生成数组*/

        /*生成根据请求方式的快捷判断*/
    }
    
    /*路由接口*/
    public static function routeStart($config,$url,$domain,$rule=false){
        /*1.加载配置文件*/
        self::$config = array_merge(self::$config,$config);
        /*2.加载路由*/
        if(self::$config['cache']){
            self::$rules = include self::$config['cache_path'];
        }else{
            self::$rules = self::initRule($rule);
        }
        /*3.检查路由*/
        $return = self::check($url,$domain);
        return $return;
    }
}
 

