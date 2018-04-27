<?php
/*
 +----------------------------------------------------------
 * 路由定义
 +----------------------------------------------------------
 * @param  ;
 +----------------------------------------------------------
 * @return json{status:bool,msg:string,data:mix,code:int}
 +----------------------------------------------------------
*/
 /*
	路由的分类：
	1.通用路由
	2.域名路由
	每一个下又作分类
		1.模型、控制器、操作
		2.重定向
		3.到控制器的方法
		4.到闭包函数
		5.到到类的方法
		6.路由别名
 */
namespace wythe;
class Route(){
	/*路由定义：优先级从上往下*/
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

	private static $domain;//当前域名

	private static $option = [];//当前路由执行过程中的参数

	private static $name;//当前路由名称

	private static $dispatch = [];//当前路由信息

    /**
     * 导入配置文件的路由规则
     * @access public
     * @param array     $rule 路由规则
     * @param string    $type 请求类型
     * @return void
     */
    public static function import(array $rule, $type = '*')
    {
        // 检查域名部署
        if (isset($rule['__domain__'])) {
            self::domain($rule['__domain__']);
            unset($rule['__domain__']);
        }

        // 检查变量规则
        if (isset($rule['__pattern__'])) {
            self::pattern($rule['__pattern__']);
            unset($rule['__pattern__']);
        }

        // 检查路由别名
        if (isset($rule['__alias__'])) {
            self::alias($rule['__alias__']);
            unset($rule['__alias__']);
        }

        // 检查资源路由
      /*  if (isset($rule['__rest__'])) {
            self::resource($rule['__rest__']);
            unset($rule['__rest__']);
        }*/

        self::registerRules($rule, strtolower($type));
    }

    // 批量注册路由
    protected static function registerRules($rules, $type = '*')
    {
        foreach ($rules as $key => $val) {
            if (is_numeric($key)) {
                $key = array_shift($val);
            }
            if (empty($val)) {
                continue;
            }
            if (is_string($key) && 0 === strpos($key, '[')) {
                $key = substr($key, 1, -1);
                self::group($key, $val);

            } elseif (is_array($val)) {
                self::setRule($key, $val[0], $type, $val[1], isset($val[2]) ? $val[2] : []);
            } else {
                self::setRule($key, $val, $type);
            }
        }
    }

    /**
     * 检测子域名部署
     * @access public
     * @param Request   $request Request请求对象
     * @param array     $currentRules 当前路由规则
     * @param string    $method 请求类型
     * @return void
     */
    public function checkDomain($request,&$currentRules,$method = 'get'){
    	$rules = self::$rules['domain'];

    	if(!emtpy($rules)){
    		$host = $request->host();
    		if(isset($rules[$host])){
    			$item = $rules[$host];
    		}else{
    			//根域名配置
    			$rootDomain = Config::get('url_domain_root');
    			//获取三级域名
    			if($rooteDoamin){
    				$domain = explode('.', rtrim(stristr($host, $rootDomain, true), '.'));
    			}else{
    				$domain = explode('.',$host,-2);
    			}

    			//子域名配置
    			if(!empty($domain)){
    				//当前子域名
    				$subDomain = implode('.',$domain);
    				self::$subDomain = $subDomain;
    				$domain2 = array_pop($domain);
    				if($domain){
    					$domain3 = array_pop($domain);
    				}
    				if($subDomain && isset($rules[$subDomain])){
    					$item = $rules[$subDomain];
    				}elseif(isset($rules['*.'.$domain2]) && !empty($domain3)){
    					$item = $rules['*.'.$domain2];
    					$panDomain = $domain3;
    				}elseif(isset($rules['*']) && !empty($domain2)){
    					if('www' != $domain2){
    						$item = $rules['*'];
    						$panDomain = $domain2;
    					}
    				}

    			}
    		}
    		/*如果匹配到了路由*/
    		if(!empty($item)){
    			if(isset($panDomain)){
    				$request->route(['__domain__'=>$panDomain]);
    			}
    			if(isset($item['[bind]'])){
    				list($rule,$option,$pattern) = $item['[bind]'];
    				/*如果是加密路由，抛出异常*/
    				if(!empty($option['httpds']) && !$request->isSsl()){

    				}
    				if(strpos($rule,'?')){
    					$array = parse_url($rule);
    					$result = $array['path'];
    					parse_str($array['query'],$params);
    					if(isset($panDomain)){
    						$pos = array_search('*',$params);
    						if(false !== $pos){
    							$params[$pos] = $panDomain;
    						}
    					}
    					$_GET = array_merge($_GET,$params);
    				}else{
    					$result = $rule;
    				}
    				if(0 === strpos($result,'\\')){
    					self::$bind = ['type'=>'namespace','namespace'=>$result];
    				}elseif(0===strpos($result,'@')){
    					self::$bind = ['type'=>'class','class'=>substr($result,1)];
    				}else{
    					self::$bind = ['type'=>'module','module'=>$result];
    				}
    				self::$domainRule = $item;
    			}
    		}else{
    			self::$domainRule = $item;
    			$currentRules = isset($item[$method]) ? $item[$method] : $item['*'];
    		}
    	}
    }

    /**
     * 检测URL路由
     * @access public
     * @param Request   $request Request请求对象
     * @param string    $url URL地址
     * @param string    $depr URL分隔符
     * @param bool      $checkDomain 是否检测域名规则
     * @return false|array
     */
    public static function check($request, $url, $depr = '/', $host)
    {
        // 分隔符替换 确保路由定义使用统一的分隔符
        $url = str_replace($depr, '|', $url);
        /*检测是否是域名路由*/
        if(isset(self::$rules['domain'][self::$domain][$request->host()])){
			return self::checkRoute($request,$rules,$url,$depr,true);	
        }else{
        	return self::checkRoute($request,$rules,$url,$depr,false)
        }
        
    }

    private static function checkRoute($request,$name,$checkDomain = false){


    	if($checkDomain){
    		$rules = self::$rules['domain'];
    	}else{
    		$rules = self::$rules['*'];
    	}

    	/*1.alias 别名解析 优先级：1*/
    	if(isset(self::$rules['alias'][$name])){
    		$result = self::checkRouteAlias($request,$name);
    		if(false !== $result){
    			return $result;
    		}
    	}

    	/*2.检测URL绑定 优先级：2*/
    	/*if(!empty(self::$bind)){
    		$result = self::checkUrlBind($url,$rules,$depr);
    		if(false !== $result){
    			return $result;
    		}    		
    	}*/

    	/*3.检测其它路由方式：3*/
    	if(isset($rules[$name])){
    		$rule = self::$rules['*'][$name]['rule'];
    		$route = self::$rules['*'][$name]['route'];
    		$vars = self::$rules['*'][$name]['vars'];
    		$option = self::$rules['*'][$name]['option'];
    		$pattern = self::$rules['*'][$name]['pattern'];
    		if(is_array($rule)){
                $pos = strpos(str_replace('<', ':', $name), ':');
                if (false !== $pos) {
                    $str = substr($key, 0, $pos);
                } else {
                    $str = $key;
                }
                if (is_string($str) && $str && 0 !== stripos(str_replace('|', '/', $url), $str)) {
                    continue;
                }
                self::setOption($option);
                $result = self::checkRoute($request, $rule, $url, $depr, $key, $option);
                if (false !== $result) {
                    return $result;
                }
    		}else{

    		}
    	}



    	 	

    	/*4.优先级：4*/
    	/*5.优先级：5*/
    	/*6.优先级：6*/



    }
    private static function getRouteExpress($key)
    {
        return self::$domainRule ? self::$domainRule['*'][$key] : self::$rules['*'][$key];
    }


 

