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

	/*不同请求类型的方法前缀*/
    private static $methodPrefix = [
        'get'    => 'get',
        'post'   => 'post',
        'put'    => 'put',
        'delete' => 'delete',
        'patch'  => 'patch',
    ];

    /*当前路由相关*/
    private static $subDomain = '';		// 子域名  
    private static $bind = [];			// 域名绑定  
    private static $group = [];			// 当前分组信息  
    private static $domainBind;			// 当前子域名绑定
    private static $domainRule; 		//
    private static $domain;				// 当前域名
    private static $option = [];		// 当前路由执行过程中的参数


	/*设置路由规则*/
	public static function Rule($rule,$route='',$type='*',$option=[],$pattern=[]){
		$group = self::getGroup('name');
		if(!is_null($group)){
			/*路由分组*/
			$option = array_merge(self::getGroup('option'),$option);
			$pattern = array_merge(self::getGroup('pattern'),$pattern);
		}
		$type = strtolower($type);

		if(strpos($type,'|')){
			$option['method'] = $type;
			$type = '*';
		}
		if(is_array($rule) && empty($route)){
			foreach($rule as $key => $val){
				if(is_numeric($key)){
					$key = array_shift($val);
				}
				if(is_array($val)){
					$route = $val[0];
					$option1 = array_merge($option,$val[1]);
					$pattern1 = array_merge($pattern,isset($val[2]) ? $val[2] : []);
				}else{
					$option1 = null;
					$patter1 = null;
					$route = $val;
				}
				self::setRule($key,$route,$type,!is_null($option1) ? $option1: $option,!is_null($pattern1) ? $pattern1 : $pattern,$group);
			}
		}else{
			self::setRule($rule,$route,$type,$option,$pattern,$group);
		}
	}
	/*设置路由规则*/
	protected static function setRule($rule,$route,$type='*',$option = [],$pattern = [],$group = ''){
		if(is_array($rule)){
			$name = $rule[0];
			$rule = $rule[1];
		}elseif(is_string($route)){
			$name = $route;
		}
		/*查看是否完整匹配*/
		if(!isset($option['complete_match'])){
			if(Config::get('route_complete_match')){
				$option['complete_match'] = true;
			}elseif('$' == substr($rule,-1,1)){
				$option['complete_match'] = true;
			}
		}elseif(empty($option['complete_match']) && '$' == substr($rule,-1,1)){
			$option['complete_match'] = true;
		}

		/*去掉$rule的后缀*/
		if('$' == substr($rule,-1,1)){
			$rule = substr($rule,0,-1);
		}
		if('/' != $rule || $group){
			$rule = trim($rule,'/');
		}

		/*取出变量*/
		$vars = self::parseVar($rule);


	}
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
}