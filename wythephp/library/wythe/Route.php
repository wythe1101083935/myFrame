<?php
namespace wythe;
class Route{
	/*路由规则*/
	private static $rules = [
		'get'		=> 	[],//get请求方法下的
		'post'		=>	[],//post请求方法下
		'put'		=>	[],//put请求方法下
		'delete'	=>	[],//delete请求方法下
		'patch'		=>	[],//patch请求方法下
		'head'		=>	[],//head请求方法下
		'options'	=>	[],//options请求方法下
		'*'			=>	[],
		'alias'		=>	[],//路由别名
		'domain'	=>	[],
		'pattern'	=>	[],
		'name'		=>	[],
	];

	/*reset路由操作方法定义,定义自己的路由操作方法*/
	private static $rest = [
        'index'  => ['get', '', 'index'],
        'create' => ['get', '/create', 'create'],
        'edit'   => ['get', '/:id/edit', 'edit'],
        'read'   => ['get', '/:id', 'read'],
        'save'   => ['post', '', 'save'],
        'update' => ['put', '/:id', 'update'],
        'delete' => ['delete', '/:id', 'delete'],
	];


    /*当前路由相关*/
    private static $subDomain = '';		// 子域名  
    private static $bind = [];			// 域名绑定  
    private static $group = [];			// 当前分组信息  
    private static $domainBind;			// 当前子域名绑定
    private static $domainRule; 		//
    private static $domain;				// 当前域名
    private static $option = [];		// 当前路由执行过程中的参数


    /**
     * 获取当前的分组信息
     * @access public
     * @param string    $type 分组信息名称 name option pattern
     * @return mixed
     */
	public static function getGroup($type){
		if(isset(self::$group[$type])){
			return sefl::$group[$type];
		}else{
			return 'name' == $type ? null:[];
		}
	}
	/*分析路由规则中的变量*/
	public static function parseVar($rule){
		$var = [];
		foreach (explode('/',$rule) as $val) {
			$optional = false;
			if(false !== strpos($val,'<') && preg_match_all('/<(\w+(\??))>/',$val,$matches)){
				foreach ($matches[1] as $name) {
					if(strpos($name,'?')){
						$name = substr($name,0,-1);
						$optional = true;
					}else{
						$optional = false;
					}
					$var[$name] = $optional ? 2 : 1;
				}
			}

			if(0===strpos($val,'[:')){
				$optional = true;
				$val = substr($val,1,-1);
			}

			if(0===strpos($val,':')){
				$name = substr($val,1);
				$var[$name] = $optional ? 2 : 1
			}
		}
		return $var;
	}


	/*导入配置文件的路由规则*/
	public static function import(array $rule){
		/*域名部署*/
		if(isset($rule['__domain__'])){
			self::domain($rule['__domain__']);
			unset($rule['__domain__']);
		}

		/*检查全局变量规则*/
		if(isset($rule['__pattern__'])){
			self::pattern($rule['__pattern__']);
			unset($rule['__pattern__']);
		}

		/*检查路由别名*/
		if(isset($rule['__pattern__'])){
			self::alias($rule['__alias__']);
			unset($rule['__alias__']);
		}

		/*检查资源路由*/
		if(isset($rule['__rest__'])){
			self::resource($rule['__rest__']);
			unset($rule['__rest__']);
		}

		/*注册其它路由*/
		self::registerRules($rule);
	}

	/*批量注册路由*/
	protected static function registerRules($rules){
		foreach ($rules as $key => $val) {
			if(empty($val)){
				continue;
			}
			/*设置分组路由*/
			if(is_string($key) && 0 === strpos($key,'[')){
				$key = substr($key,1,-1);
				self::group($key,$val);
			/*设置单个路由*/
			}elseif(is_array($val)){
				self::setRule($key,$val[0],$val[1],isset($val[2]) ? $val[2] : []);
			/*设置静态路由*/
			}else{
				self::setRule($key,$val);
			}
		}
	}
	/*设置路由规则*/
	protected static function setRule($rule,$route,$option = [],$pattern = [],$group = ''){

		/*判断是否完整匹配*/
		if(!isset($option['complete_match']) || empty($option['complete_match'])){
			if(Config::get('route_complete_match')){
				$option['complete_match'] = true;
			}elseif('$' == substr($rule,-1,1)){
				$option['complete_match'] = true;
				$rule = substr($rule,0,-1);
			}else{
				$option['complete_match'] = false;
			}
		}

		//如果不是定义根目录或者是分组
		if('/' != $rule || $group){
			$rule = trim($rule,'/');
		}

		/*取出变量*/
		$vars = self::parseVar($rule);

		$key = $group ? $group . '/' . $rule : $rule;

		$suffix = isset($option['ext']) ? $option['ext'] : null;

		/*建立反向路由*/
		self::name($route,[$key,$vars,self::$domain,$suffix]);

		/*不明路由参数*/
		/*if(isset($option['modular'])){
			$route = $option['modular'] .'/' . $route;
		}*/

		/*写入路由表*/
        if ($group) {
            if (self::$domain) {
                self::$rules['domain'][self::$domain]['*'][$group]['rule'][] = ['rule' => $rule, 'route' => $route, 'var' => $vars, 'option' => $option, 'pattern' => $pattern];
            } else {
                self::$rules['*'][$group]['rule'][] = ['rule' => $rule, 'route' => $route, 'var' => $vars, 'option' => $option, 'pattern' => $pattern];
            }
        } else {
            if (isset(self::$rules['*'][$rule])) {
                unset(self::$rules['*'][$rule]);
            }
            if (self::$domain) {
                self::$rules['domain'][self::$domain][$type][$rule] = ['rule' => $rule, 'route' => $route, 'var' => $vars, 'option' => $option, 'pattern' => $pattern];
            } else {
                self::$rules[$type][$rule] = ['rule' => $rule, 'route' => $route, 'var' => $vars, 'option' => $option, 'pattern' => $pattern];
            }
            if ('*' == $type) {
                // 注册路由快捷方式
                foreach (['get', 'post', 'put', 'delete', 'patch', 'head', 'options'] as $method) {
                    if (self::$domain && !isset(self::$rules['domain'][self::$domain][$method][$rule])) {
                        self::$rules['domain'][self::$domain][$method][$rule] = true;
                    } elseif (!self::$domain && !isset(self::$rules[$method][$rule])) {
                        self::$rules[$method][$rule] = true;
                    }
                }
            }
        }

	}
	/*注册子域名部署规则*/
	public static function domain($domain,$rule='',$option=[],$pattern=[]){
		if(is_array($domain)){
			foreach ($domain as $key => $item) {
				self::domain($key,$item,$option,$pattern);
			}
		}elseif($rule instanceof \Closure){
			self::$domain = $domain;
			call_user_func_array($rule,[]);
			self::$domain = $domain;
		}elseif(is_array($rule)){
			self::$domain = $domain;
			self::group('',function() use ($rule){
				self::registerRules($rule);
			},$option,$pattern);
		}else{
			self::$rules['domain'][$domain]['[bind]'] = [$rule,$option,$pattern]
		}
	}

	/*注册别名路由*/
	public static function alias($rule = null, $route='',$option=[]){
		if(is_array($rule)){
			self::$rules['alias'] = array_merge(self::$rules['alias'],$rule);
		}else{
			self::$rules['alias'][$rule] = $option ? [$route,$option] : $route;
		}
	}

	/*注册全局变量规则*/
	public function pattern($name = null,$rule =''){
		if(is_array($name)){
			self::$rules['patter'] = array_merge(self::$rules['pattern'],$name);
		}else{
			self::$rules['patter'][$name] = $rule;
		}
	}

	/*注册资源路由*/
    public static function resource($rule, $route = '', $option = [], $pattern = []){
        if (is_array($rule)) {
            foreach ($rule as $key => $val) {
                if (is_array($val)) {
                    list($val, $option, $pattern) = array_pad($val, 3, []);
                }
                self::resource($key, $val, $option, $pattern);
            }
        } else {
            if (strpos($rule, '.')) {
                // 注册嵌套资源路由
                $array = explode('.', $rule);
                $last  = array_pop($array);
                $item  = [];
                foreach ($array as $val) {
                    $item[] = $val . '/:' . (isset($option['var'][$val]) ? $option['var'][$val] : $val . '_id');
                }
                $rule = implode('/', $item) . '/' . $last;
            }
            // 注册资源路由
            foreach (self::$rest as $key => $val) {
                if ((isset($option['only']) && !in_array($key, $option['only']))
                    || (isset($option['except']) && in_array($key, $option['except']))) {
                    continue;
                }
                if (isset($last) && strpos($val[1], ':id') && isset($option['var'][$last])) {
                    $val[1] = str_replace(':id', ':' . $option['var'][$last], $val[1]);
                } elseif (strpos($val[1], ':id') && isset($option['var'][$rule])) {
                    $val[1] = str_replace(':id', ':' . $option['var'][$rule], $val[1]);
                }
                $item           = ltrim($rule . $val[1], '/');
                $option['rest'] = $key;
                self::rule($item . '$', $route . '/' . $val[2], $val[0], $option, $pattern);
            }
        }
    }

    /*设置分组路由*/
    public static function group($name,$routes,$option=[],$pattern=[]){
    	if(is_array($name)){
    		$option = $name;
    		$name = isset($option['name']) ? $option['name'] : '';
    	}

    	$currentGroup = self::getGroup('name');

    	if($currentGroup){
    		$name = $currentGroup . ($name ? '/' . ltrim($name,'/') : '');
    	}

    	if(!emtpy($name)){
    		if($routes instanceof \Closure){
    			$currentOption = self::getGroup('option');
    			$currentPattern = self::getGroup('pattern');
    			self::setGroup($name,array_merge($currentOption,$option),array_merge($currentPattern,$pattern));
    			call_user_func_array($routes,[]);
    			self::setGroup($currentGroup,$currentOption,$currentPattern);
    			if($currentGroup ! = $name){
    				self::$rules['*'][$name]['route'] = '';
    				self::$rules['*'][$name]['var'] = self::parseVar($name);
    				self::$rules['*'][$name]['option'] = $option;
    				self::$rules['*'][$name]['pattern'] = $pattern;
    			}
    		}else{
    			$time = [];
    			$completeMatch = Config::get('route_complete_match');
    			foreach ($routes as $key => $val) {
    				if(is_numeric($key)){
    					$key = array_shift($val);
    				}
    				if(is_array($val)){
    					$route = $val[0];
    					$option1 = array_merge($option,isset($val[1]) ? $val[1] : []);
    					$pattern1 = array_merge($pattern,isset($val[2]) ? $val[2] : []);
    				}else{
    					$route = $val;
    				}

    				$options = isset($optin1) ? $option1 : $option;
    				$patterns = isset($pattern1) ? $pattern1 : $pattern;
    				if('$' == substr($key,-1,1)){
    					$options['complete_match'] = true;
    					$key = substr($key,0,-1);
    				}elseif($completeMatch){
    					$options['complete_match'] = true;
    				}
    				$key = trim($key,'/');
    				$vars = self::parseVar($key);
    				$item[] = ['rule'=>$key,'route'=>$route,'var'=>$vars,'option'=>$options,'pattern'=>$patterns];
    				$suffix = isset($options['ext']) ? $options['ext'] : null;
    				self::name($route,[$name.($key ? '/'.$key :'')],$vars,self::$domain,$suffix);
    			}
    			self::$rules['*'][$name] =  ['rule' => $item, 'route' => '', 'var' => [], 'option' => $option, 'pattern' => $pattern]
    		}
    	}
    }


	/*设置路由标识*/
	public static function name($name= '',$value = null){
		if(is_array($name)){
			return self::$rules['name'] = $name;
		}elseif('' === $name){
			return self::$rules['name'];
		}elseif(!is_null($value)){
			self::$rules['name'][strtolower($name)][] = $value;
		}else{
			$name = strtolower($name);
			return isset(self::$rules['name'][$name]) ? self::$rules['name'][$name] : null;
		}
	}

	/*检测url路由*/
	public static function check($request,$url,$depr='/',$checkDomain = false){
		$url = str_replace($depr,'|',$url);

		if(isset(self::$rules['alias'][$url]) || isset(self::$rules['alias'][strstr($url,'|',true)])){
			$result = self::checkRouteAlias($request,$url,$depr);
			if(false !== $result){
				return $result;
			}
		}
		$method = strtolower($request->method());

		$rules = isset(self::$rules[$method]) ? self::$rules[$method] : [];

		if($checkDoamin){
			self::checkDomain($request,$rules,$method);
		}

		/*检测url绑定*/
		$return = self::checkUrlBind($url,$rules,$depr);

		if(false!== $return){
			return $return;
		}

		if('|' != $url){
			$url = rtrim($url,'|');
		}
		$item = str_replace('|','/',$url);
		if(isset($rules[$item])){
			$rule = $rules[$item];
			$rule = $rules[$item];
			if(true === $rule){
				$rule = self::getRouteExpress($item);
			}
			if(!empty($rule['rote']) && self::checkOption($rule['option'],$request)){
				self::setOption($rule['option']);
				return self::parseRule($item,$rule['route'],$url,$rule['option']);
			}
		}

		/*路由规则检测*/
		if(!empty($rules)){
			return self::checkRoute($request,$rules,$url,$depr);
		}
		return false;
	}

	/*检测路由别名*/
	private static function checkRouteAlias($request,$url,$depr){
		$array = explode('|',$url);

		$alias = array_shift($array);

		$item = self::$rules['alias'][$alias];

		if(is_array($item)){
			list($rule,$option) = $item;
			$action = $array[0];
			if(isset($option['allow']) && !in_array($action,explode(',',$option['allow']))){
				return false;
			}elseif(isset($option['except']) && in_array($action,explode(',',$option['except']))){
				return false;
			}
			if(isset($option['method'][$action])){
				$option['method'] = $option['method'][$action];
			}
		} else{
			$rule = $item;
		}
		$bind = implode('|',$array);
	}

	/*检测路由*/
	private static function checkRoute($request,$rules,$url,$depr='/',$group='',$options=[]){
		foreach ($rules as $key => $item) {
			if(true === $itme){
				$time = self::getRouteExpress($key);
			}
			if(!isset($item['rule'])){
				continue;
			}
			if(!self::checkOption($item['option'],$request)){
				continue;
			}
			if(isset($item['option']['ext'])){
				$url = preg_replace('/\.'.$request->ext().'$/i','',$url);
			}
			if(is_array($item['rule'])){
				$pos = strpos(str_replace('<',':',$key),':');
				if(false !== $pos){
					$str = substr($key,0,$pos);
				}else{
					$str = $key;
				}
				if(is_string($str) && $str && 0 !== stropos(str_replace('|','/',$url),$str)){
					continue;
				}
				self::setOption($item['option']);
				$result = self::checkRoute($request,$rule,$url,$depr,$key,$option);
				if(false !== $result){
					return $result;
				}
			}elseif($route){
				if('__miss__' == $rule || '__auto__' == $rule){
					$var = trim($rule,'__');
					${$var} = $item;
					continue;
				}
				if($group){
					$rule = $group . ($rule ? '/' .ltrim($rule,'/') : '');
				}

				self::setOption($option);
				if(isset($options['bind_model']) && isset($option['bind_mode'])){
					$option['bind_mode'] = array_merge($options['bind_model'],$option['bind_model']);
				}
				$result = self::checkRule($rule,$route,$url,$pattern,$potion,$depr);
				if(false !== $result){
					return $result;
				}
			}

		}
		if(isset($auto)){
			return self::parseUrl($auto['route'] .'/' .$url,$depr);
		}elseif(isset($miss)){
			return self::parseRule('',$miss['route'],$url,$miss['option']);
		}

		return false;
	}

}