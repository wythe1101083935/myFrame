<?php
namespace wythe;

class Request{
    /*object 对象实例*/
    protected static $instance;

   	/*Hook扩展方法*/
	protected static $hook = [];

    /*url信息*/
    protected $method; 				//string 请求方法  
    protected $domain;				//string 域名（含协议和端口）    
    protected $url;					//string URL地址    
    protected $baseUrl;				//string 基础URL    
    protected $baseFile;			//string 当前执行的文件    
    protected $root;				//string 访问的ROOT地址    
    protected $pathinfo;			//string pathinfo
    protected $path; 				//string pathinfo（不含后缀）
    protected $ext;					//string url访问后缀

    /*array 当前路由信息*/
    protected $routeInfo;

    /*array 环境变量*/
    protected $env;

    /*array 当前调度信息*/
    protected $dispatch;			//array 生成的调度信息
    protected $module;				//访问模块
    protected $controller;			//访问控制器
    protected $action;				//访问操作

    /*当前语言集*/
    protected $langset;

    /*array 请求参数*/
    protected $param;				//
    protected $get;					//
    protected $post;				//
    protected $request;				//
    protected $route;				//
    protected $put;					//
    protected $session;				//
    protected $file;				//
    protected $cookie;				//
    protected $server;				//
    protected $header;				//

    /*array 资源类型*/
    protected $mimeType = [
        'xml'   => 'application/xml,text/xml,application/x-xml',
        'json'  => 'application/json,text/x-json,application/jsonrequest,text/json',
        'js'    => 'text/javascript,application/javascript,application/x-javascript',
        'css'   => 'text/css',
        'rss'   => 'application/rss+xml',
        'yaml'  => 'application/x-yaml,text/yaml',
        'atom'  => 'application/atom+xml',
        'pdf'   => 'application/pdf',
        'text'  => 'text/plain',
        'image' => 'image/png,image/jpg,image/jpeg,image/pjpeg,image/gif,image/webp,image/*',
        'csv'   => 'text/csv',
        'html'  => 'text/html,application/xhtml+xml,*/*',
    ];
    protected $type 				//当前请求的资源类型

    /*其他*/
    protected $content;  			// 
    protected $filter;				// 全局过滤规则     
    protected $bind = [];			// 绑定的属性    
    protected $input;				// php://input   
    protected $cache;				// 请求缓存 
    protected $isCheckCache;		// 缓存是否检查

    /*判断集合*/
    protected $isMobile;			//是否是手机访问
    protected $isGet;				//是否是get
    protected $isPost;				//是否是post
    protected $isPut;				//是否是put
    protected $isDelete;			//是否是delete
    protected $isHead;				//是否是head
    protected $isPatch;				//是否是patch
    protected $isOptions;			//是否是options
    protected $isClli;				//是否是cli
    protected $isCgi;				//是否是cgi
    protected $isSsl;				//是否是ssl
    protected $isAjax;				//是否是ajax
    protected $isPjax;				//是否是pjax

    /**
     * 构造函数
     * @access protected
     * @param array $options 参数
     */
    protected function __construct($options = []){
        foreach ($options as $name => $item) {
            if (property_exists($this, $name)) {
                $this->$name = $item;
            }
        }
        if (is_null($this->filter)) {
            $this->filter = Config::get('default_filter');
        }

        // 保存 php://input
        $this->input = file_get_contents('php://input');
    }

    /*初始化*/
    public static function instance($options = [])
    {
        if (is_null(self::$instance)) {
            self::$instance = new static($options);
        }
        return self::$instance;
    }    
    /*设置属性*/
    public1 function attr($name,$value=false){
    	if(property_exists($this, $name)){
	    	if(!is_null($value) && $value !== false){//设置值
	    		if(is_array($this->$name)){
	    			if(is_array($value)){
	    				$this->$name = array_merge($this->$name,$value);
	    			}
	    		}else{
	    			$this->$name = $value;
	    		}
	    		return $this;
	    	}elseif($value === false && is_null($this->$name)){//取值
	    		if(method_exists($this,'get'.ucfirst($name))){
	    			$this->{'get'.$name}(); 			
	    		}
	    		if(is_null($this->$name)){
	    			return 'RESEPONSE';
	    		}
	    		return $this->$name;
	    	}else{
	    		return $this->$name ? : '';
	    	}
    	}
    }	
    public1 function __set($name,$value){
    	$this->attr($name,$value)
    }
    public1 function __get($name){
    	return $this->attr($name,false);
    }
    /*获取url*/
    protected function getUrl(){
        if (IS_CLI) {
            $this->url = isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : '';
        } elseif (isset($_SERVER['HTTP_X_REWRITE_URL'])) {
            $this->url = $_SERVER['HTTP_X_REWRITE_URL'];
        } elseif (isset($_SERVER['REQUEST_URI'])) {
            $this->url = $_SERVER['REQUEST_URI'];
        } elseif (isset($_SERVER['ORIG_PATH_INFO'])) {
            $this->url = $_SERVER['ORIG_PATH_INFO'] . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
        }
    }  
    /*获取baseUrl*/  
    protected function getBaseUrl(){
        $str           = $this->attr('url');
        $this->baseUrl = strpos($str, '?') ? strstr($str, '?', true) : $str;    	
    }
    /*获取当前执行文件*/
    protected function getBaseFile(){
        if (!IS_CLI) {
            $script_name = basename($_SERVER['SCRIPT_FILENAME']);
            if (basename($_SERVER['SCRIPT_NAME']) === $script_name) {
                $this->baseFile = $_SERVER['SCRIPT_NAME'];
            } elseif (basename($_SERVER['PHP_SELF']) === $script_name) {
                $this->baseFile = $_SERVER['PHP_SELF'];
            } elseif (isset($_SERVER['ORIG_SCRIPT_NAME']) && basename($_SERVER['ORIG_SCRIPT_NAME']) === $script_name) {
               $this->baseFile = $_SERVER['ORIG_SCRIPT_NAME'];
            } elseif (($pos = strpos($_SERVER['PHP_SELF'], '/' . $script_name)) !== false) {
               $this->baseFile = substr($_SERVER['SCRIPT_NAME'], 0, $pos) . '/' . $script_name;
            } elseif (isset($_SERVER['DOCUMENT_ROOT']) && strpos($_SERVER['SCRIPT_FILENAME'], $_SERVER['DOCUMENT_ROOT']) === 0) {
                $this->baseFile = str_replace('\\', '/', str_replace($_SERVER['DOCUMENT_ROOT'], '', $_SERVER['SCRIPT_FILENAME']));
            }
        }   	
    }
    /*获取root*/
    protected function getRoot(){
        $file = $this->attr('baseFile');
        if ($file && 0 !== strpos($this->attr('url'), $file)) {
            $file = str_replace('\\', '/', dirname($file));
        }
        $this->root = rtrim($file, '/');    	
    }
    /*获取domain*/
    protected function getDomain(){
    	$this->domain =  $this->attr('scheme') . '://' . $this->attr('scheme');
    }
    /*获取scheme*/
    protected function getSheme(){
    	$this->attr('isSsl') ? 'https' : 'http';
    }
    /*获取isSsl*/
    protected function getIsSsl(){
        $server = array_merge($_SERVER, $this->attr('server'));
        if (isset($server['HTTPS']) && ('1' == $server['HTTPS'] || 'on' == strtolower($server['HTTPS']))) {
            return true;
        } elseif (isset($server['REQUEST_SCHEME']) && 'https' == $server['REQUEST_SCHEME']) {
            return true;
        } elseif (isset($server['SERVER_PORT']) && ('443' == $server['SERVER_PORT'])) {
            return true;
        } elseif (isset($server['HTTP_X_FORWARDED_PROTO']) && 'https' == $server['HTTP_X_FORWARDED_PROTO']) {
            return true;
        } elseif (Config::get('https_agent_name') && isset($server[Config::get('https_agent_name')])) {
            return true;
        }
        return false;   		
    }
    /*获取baseUrl*/
    protected function getBaseUrl(){
        $str           = $this->url();
        $this->baseUrl = strpos($str, '?') ? strstr($str, '?', true) : $str;
    }
    /*获取Root*/
    protected getRoot(){
        $file = $this->attr('baseFile');
        if ($file && 0 !== strpos($this->attr('url'), $file)) {
            $file = str_replace('\\', '/', dirname($file));
        }
        $this->root = rtrim($file, '/');    	
    }
    /*获取pathinfo*/
    protected getPathinfo(){     
        if (isset($_GET[Config::get('var_pathinfo')])) {
            // 判断URL里面是否有兼容模式参数
            $_SERVER['PATH_INFO'] = $_GET[Config::get('var_pathinfo')];
            unset($_GET[Config::get('var_pathinfo')]);
        } elseif (IS_CLI) {
            // CLI模式下 index.php module/controller/action/params/...
            $_SERVER['PATH_INFO'] = isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : '';
        }

        // 分析PATHINFO信息
        if (!isset($_SERVER['PATH_INFO'])) {
            foreach (Config::get('pathinfo_fetch') as $type) {
                if (!empty($_SERVER[$type])) {
                    $_SERVER['PATH_INFO'] = (0 === strpos($_SERVER[$type], $_SERVER['SCRIPT_NAME'])) ?
                    substr($_SERVER[$type], strlen($_SERVER['SCRIPT_NAME'])) : $_SERVER[$type];
                    break;
                }
            }
        }
        $this->pathinfo = empty($_SERVER['PATH_INFO']) ? '/' : ltrim($_SERVER['PATH_INFO'], '/');
    }
    /*获取path*/
    protected getPath(){
        $suffix   = Config::get('url_html_suffix');
        $pathinfo = $this->attr('pathinfo');
        if (false === $suffix) {
            // 禁止伪静态访问
            $this->path = $pathinfo;
        } elseif ($suffix) {
            // 去除正常的URL后缀
            $this->path = preg_replace('/\.(' . ltrim($suffix, '.') . ')$/i', '', $pathinfo);
        } else {
            // 允许任何后缀访问
            $this->path = preg_replace('/\.' . $this->ext() . '$/i', '', $pathinfo);
        }    	
    }
    /*获取当前url访问后缀*/
    protected function getExt(){
		$this->ext = $float ? $_SERVER['REQUEST_TIME_FLOAT'] : $_SERVER['REQUEST_TIME'];    	
    }
    /*获取当前请求的资源类型*/
    protected function getType(){
        $accept = $this->attr('server')['HTTP_ACCEPT'];
        if (empty($accept)) {
        	$this->type = false;
        }
        foreach ($this->mimeType as $key => $val) {
            $array = explode(',', $val);
            foreach ($array as $k => $v) {
                if (stristr($accept, $v)) {
                	$this->type = $key;
                    return;
                }
            }
        }
    }
    /*获取当前的请求类型*/
    /*protected function getMethod(){
        if (isset($_POST[Config::get('var_method')])) {
            $this->method = strtoupper($_POST[Config::get('var_method')]);
            $this->{$this->method}($_POST);
        } elseif (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
            $this->method = strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
        } else {
            $this->method = IS_CLI ? 'GET' : (isset($this->server['REQUEST_METHOD']) ? $this->server['REQUEST_METHOD'] : $_SERVER['REQUEST_METHOD']);
        }    	
    }*/
    protected function getIsGet(){
    	$this->isGet = $this->attr('method') == 'GET';
    }
    protected function getIsPost(){
    	$this->isPost = $this->attr('method') == 'POST';
    }
    protected function getIsPut(){
    	$this->isPut = $this->attr('method') == 'PUT';
    }
    protected function getIsDelete(){
    	$this->isDelete = $this->attr('method') == 'DELETE';
    }
    protected function getIsHead(){
    	$this->isHead = $this->attr('method') == 'HEAD';
    }
    protected function getIsPatch(){
    	$this->isPatch = $this->attr('method') == 'PATCH';
    }
    protected function getIsOptions(){
    	$this->isOpions = $this->attr('method') == 'OPTIONS';
    }
    protected function getIsCli(){
    	$this->isCli = IS_CLI;
    }
    protected function getIsCgi(){
    	$this->isCgi = strpos(PHP_SAPI,'cgi') === 0;
    }
    /*获取参数*/
    protected function getParam(){
        $method = $this->attr('method');
        // 自动获取请求变量
        switch ($method) {
            case 'POST':
                $vars = $this->attr('post');
                break;
            case 'PUT':
            case 'DELETE':
            case 'PATCH':
                $vars = $this->attr('put');
                break;
            default:
                $vars = [];
        }
        // 当前请求参数和URL地址中的参数合并
        $this->param = array_merge($this->attr('get'), $vars, $this->attr('route')); 
		$file = $this->attr('file');
        $data = is_array($file) ? array_merge($this->param, $file) : $this->param;
        //return $this->input($data, '', $default, $filter);   	
    }
    /*获取get*/
    protected function getGet(){
		$this->get = $_GET;    	
    }
    /*获取post*/
    protected function getPost(){
        $content = $this->input;
        if (false !== strpos($this->attr('contentType'), 'application/json')) {
            $this->post = (array) json_decode($content, true);
        } else {
            $this->post = $_POST;
        }
    }
    /*获取put*/

    /*获取input*/
   	/*protected function getInput()
    {
        if (false === $name) {
            // 获取原始数据
            return $data;
        }
        $name = (string) $name;
        if ('' != $name) {
            // 解析name
            if (strpos($name, '/')) {
                list($name, $type) = explode('/', $name);
            } else {
                $type = 's';
            }
            // 按.拆分成多维数组进行判断
            foreach (explode('.', $name) as $val) {
                if (isset($data[$val])) {
                    $data = $data[$val];
                } else {
                    // 无输入数据，返回默认值
                    return $default;
                }
            }
            if (is_object($data)) {
                return $data;
            }
        }

        // 解析过滤器
        $filter = $this->getFilter($filter, $default);

        if (is_array($data)) {
            array_walk_recursive($data, [$this, 'filterValue'], $filter);
            reset($data);
        } else {
            $this->filterValue($data, $name, $filter);
        }

        if (isset($type) && $data !== $default) {
            // 强制类型转换
            $this->typeCast($data, $type);
        }
        return $data;
    }*/

    /**
     * 设置或获取当前的过滤规则
     * @param mixed $filter 过滤规则
     * @return mixed
     */
    /*获取filter*/
/*    protected function Filter($filter = null)
    {
        if (is_null($filter)) {
            return $this->filter;
        } else {
            $this->filter = $filter;
        }
    }

    protected function getFilter($filter, $default)
    {
        if (is_null($filter)) {
            $filter = [];
        } else {
            $filter = $filter ?: $this->filter;
            if (is_string($filter) && false === strpos($filter, '/')) {
                $filter = explode(',', $filter);
            } else {
                $filter = (array) $filter;
            }
        }

        $filter[] = $default;
        return $filter;
    }*/

    /**
     * 递归过滤给定的值
     * @param mixed     $value 键值
     * @param mixed     $key 键名
     * @param array     $filters 过滤方法+默认值
     * @return mixed
     */
    private function filterValue(&$value, $key, $filters)
    {
        $default = array_pop($filters);
        foreach ($filters as $filter) {
            if (is_callable($filter)) {
                // 调用函数或者方法过滤
                $value = call_user_func($filter, $value);
            } elseif (is_scalar($value)) {
                if (false !== strpos($filter, '/')) {
                    // 正则过滤
                    if (!preg_match($filter, $value)) {
                        // 匹配不成功返回默认值
                        $value = $default;
                        break;
                    }
                } elseif (!empty($filter)) {
                    // filter函数不存在时, 则使用filter_var进行过滤
                    // filter为非整形值时, 调用filter_id取得过滤id
                    $value = filter_var($value, is_int($filter) ? $filter : filter_id($filter));
                    if (false === $value) {
                        $value = $default;
                        break;
                    }
                }
            }
        }
        return $this->filterExp($value);
    }

    /**
     * 过滤表单中的表达式
     * @param string $value
     * @return void
     */
    /*获取filterExp*/
    protected function Filterexp(&$value)
    {
        // 过滤查询特殊字符
        if (is_string($value) && preg_match('/^(EXP|NEQ|GT|EGT|LT|ELT|OR|XOR|LIKE|NOTLIKE|NOT LIKE|NOT BETWEEN|NOTBETWEEN|BETWEEN|NOT EXISTS|NOTEXISTS|EXISTS|NOT NULL|NOTNULL|NULL|BETWEEN TIME|NOT BETWEEN TIME|NOTBETWEEN TIME|NOTIN|NOT IN|IN)$/i', $value)) {
            $value .= ' ';
        }
        // TODO 其他安全过滤
    }

    /**
     * 强制类型转换
     * @param string $data
     * @param string $type
     * @return mixed
     */
    private function typeCast(&$data, $type)
    {
        switch (strtolower($type)) {
            // 数组
            case 'a':
                $data = (array) $data;
                break;
            // 数字
            case 'd':
                $data = (int) $data;
                break;
            // 浮点
            case 'f':
                $data = (float) $data;
                break;
            // 布尔
            case 'b':
                $data = (boolean) $data;
                break;
            // 字符串
            case 's':
            default:
                if (is_scalar($data)) {
                    $data = (string) $data;
                } else {
                    throw new \InvalidArgumentException('variable type error：' . gettype($data));
                }
        }
    }

    /**
     * 是否存在某个请求参数
     * @access public
     * @param string    $name 变量名
     * @param string    $type 变量类型
     * @param bool      $checkEmpty 是否检测空值
     * @return mixed
     */
    /*获取has*/
    protected function Has($name, $type = 'param', $checkEmpty = false)
    {
        if (empty($this->$type)) {
            $param = $this->$type();
        } else {
            $param = $this->$type;
        }
        // 按.拆分成多维数组进行判断
        foreach (explode('.', $name) as $val) {
            if (isset($param[$val])) {
                $param = $param[$val];
            } else {
                return false;
            }
        }
        return ($checkEmpty && '' === $param) ? false : true;
    }

    /**
     * 获取指定的参数
     * @access public
     * @param string|array  $name 变量名
     * @param string        $type 变量类型
     * @return mixed
     */
    /*获取only*/
    protected function Only($name, $type = 'param')
    {
        $param = $this->$type();
        if (is_string($name)) {
            $name = explode(',', $name);
        }
        $item = [];
        foreach ($name as $key) {
            if (isset($param[$key])) {
                $item[$key] = $param[$key];
            }
        }
        return $item;
    }

    /**
     * 排除指定参数获取
     * @access public
     * @param string|array  $name 变量名
     * @param string        $type 变量类型
     * @return mixed
     */
    /*获取except*/
    protected function Except($name, $type = 'param')
    {
        $param = $this->$type();
        if (is_string($name)) {
            $name = explode(',', $name);
        }
        foreach ($name as $key) {
            if (isset($param[$key])) {
                unset($param[$key]);
            }
        }
        return $param;
    }

    /**
     * 当前是否ssl
     * @access public
     * @return bool
     */
    /*获取isSsl*/
    protected function getIsSsl()
    {
        $server = $this->attr('servers');
        if (isset($server['HTTPS']) && ('1' == $server['HTTPS'] || 'on' == strtolower($server['HTTPS']))) {
            $this->isSsl true;
        } elseif (isset($server['REQUEST_SCHEME']) && 'https' == $server['REQUEST_SCHEME']) {
            $this->isSsl true;
        } elseif (isset($server['SERVER_PORT']) && ('443' == $server['SERVER_PORT'])) {
            $this->isSsl true;
        } elseif (isset($server['HTTP_X_FORWARDED_PROTO']) && 'https' == $server['HTTP_X_FORWARDED_PROTO']) {
            $this->isSsl true;
        } elseif (Config::get('https_agent_name') && isset($server[Config::get('https_agent_name')])) {
            $this->isSsl true;
        }
        $this->isSsl false;
    }

    /**
     * 当前是否Ajax请求
     * @access public
     * @param bool $ajax  true 获取原始ajax请求
     * @return bool
     */
    /*获取isAjax*/
    protected function Isjax($ajax = false)
    {
        $value  = $this->server('HTTP_X_REQUESTED_WITH', '', 'strtolower');
        $result = ('xmlhttprequest' == $value) ? true : false;
        if (true === $ajax) {
            return $result;
        } else {
            return $this->param(Config::get('var_ajax')) ? true : $result;
        }
    }

    /**
     * 当前是否Pjax请求
     * @access public
     * @param bool $pjax  true 获取原始pjax请求
     * @return bool
     */
    /*获取isPjax*/
    protected function Ispjax($pjax = false)
    {
        $result = !is_null($this->server('HTTP_X_PJAX')) ? true : false;
        if (true === $pjax) {
            return $result;
        } else {
            return $this->param(Config::get('var_pjax')) ? true : $result;
        }
    }

    /**
     * 获取客户端IP地址
     * @param integer   $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
     * @param boolean   $adv 是否进行高级模式获取（有可能被伪装）
     * @return mixed
     */
    获取/*ip*/
    protected function Ip($type = 0, $adv = true)
    {
        $type      = $type ? 1 : 0;
        static $ip = null;
        if (null !== $ip) {
            return $ip[$type];
        }

        $httpAgentIp = Config::get('http_agent_ip');

        if ($httpAgentIp && isset($_SERVER[$httpAgentIp])) {
            $ip = $_SERVER[$httpAgentIp];
        } elseif ($adv) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $pos = array_search('unknown', $arr);
                if (false !== $pos) {
                    unset($arr[$pos]);
                }
                $ip = trim(current($arr));
            } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (isset($_SERVER['REMOTE_ADDR'])) {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        // IP地址合法验证
        $long = sprintf("%u", ip2long($ip));
        $ip   = $long ? [$ip, $long] : ['0.0.0.0', 0];
        return $ip[$type];
    }

    /**
     * 检测是否使用手机访问
     * @access public
     * @return bool
     */
    /*获取isMobile*/
    protected function Ismobile()
    {
        if (isset($_SERVER['HTTP_VIA']) && stristr($_SERVER['HTTP_VIA'], "wap")) {
            return true;
        } elseif (isset($_SERVER['HTTP_ACCEPT']) && strpos(strtoupper($_SERVER['HTTP_ACCEPT']), "VND.WAP.WML")) {
            return true;
        } elseif (isset($_SERVER['HTTP_X_WAP_PROFILE']) || isset($_SERVER['HTTP_PROFILE'])) {
            return true;
        } elseif (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/(blackberry|configuration\/cldc|hp |hp-|htc |htc_|htc-|iemobile|kindle|midp|mmp|motorola|mobile|nokia|opera mini|opera |Googlebot-Mobile|YahooSeeker\/M1A1-R2D2|android|iphone|ipod|mobi|palm|palmos|pocket|portalmmm|ppc;|smartphone|sonyericsson|sqh|spv|symbian|treo|up.browser|up.link|vodafone|windows ce|xda |xda_)/i', $_SERVER['HTTP_USER_AGENT'])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 当前URL地址中的scheme参数
     * @access public
     * @return string
     */
    /*获取scheme*/
    protected function Scheme()
    {
        return $this->isSsl() ? 'https' : 'http';
    }

    /**
     * 当前请求URL地址中的query参数
     * @access public
     * @return string
     */
    /*获取query*/
    protected function Query()
    {
        return $this->server('QUERY_STRING');
    }

    /**
     * 当前请求的host
     * @access public
     * @return string
     */
    /*获取host*/
    protected function Host()
    {
        if (isset($_SERVER['HTTP_X_REAL_HOST'])) {
            return $_SERVER['HTTP_X_REAL_HOST'];
        }
        return $this->server('HTTP_HOST');
    }

    /**
     * 当前请求URL地址中的port参数
     * @access public
     * @return integer
     */
    /*获取port*/
    protected function Port()
    {
        return $this->server('SERVER_PORT');
    }

    /**
     * 当前请求 HTTP_CONTENT_TYPE
     * @access public
     * @return string
     */
    /*获取contentType*/
    protected function Contenttype()
    {
        $contentType = $this->server('CONTENT_TYPE');
        if ($contentType) {
            if (strpos($contentType, ';')) {
                list($type) = explode(';', $contentType);
            } else {
                $type = $contentType;
            }
            return trim($type);
        }
        return '';
    }

    /**
     * 获取当前请求的路由信息
     * @access public
     * @param array $route 路由名称
     * @return array
     */
    /*获取routeInfo*/
    protected function Routeinfo($route = [])
    {
        if (!empty($route)) {
            $this->routeInfo = $route;
        } else {
            return $this->routeInfo;
        }
    }

    /**
     * 设置或者获取当前请求的调度信息
     * @access public
     * @param array  $dispatch 调度信息
     * @return array
     */
    /*获取dispatch*/
    protected function Dispatch($dispatch = null)
    {
        if (!is_null($dispatch)) {
            $this->dispatch = $dispatch;
        }
        return $this->dispatch;
    }

    /**
     * 设置或者获取当前的模块名
     * @access public
     * @param string $module 模块名
     * @return string|Request
     */
    /*获取module*/
    protected function Module($module = null)
    {
        if (!is_null($module)) {
            $this->module = $module;
            return $this;
        } else {
            return $this->module ?: '';
        }
    }

    /**
     * 设置或者获取当前的控制器名
     * @access public
     * @param string $controller 控制器名
     * @return string|Request
     */
    /*获取controller*/
    protected function Controller($controller = null)
    {
        if (!is_null($controller)) {
            $this->controller = $controller;
            return $this;
        } else {
            return $this->controller ?: '';
        }
    }

    /**
     * 设置或者获取当前的操作名
     * @access public
     * @param string $action 操作名
     * @return string|Request
     */
    /*获取action*/
    protected function Action($action = null)
    {
        if (!is_null($action) && !is_bool($action)) {
            $this->action = $action;
            return $this;
        } else {
            $name = $this->action ?: '';
            return true === $action ? $name : strtolower($name);
        }
    }

    /**
     * 设置或者获取当前的语言
     * @access public
     * @param string $lang 语言名
     * @return string|Request
     */
    /*获取langset*/
    protected function Langset($lang = null)
    {
        if (!is_null($lang)) {
            $this->langset = $lang;
            return $this;
        } else {
            return $this->langset ?: '';
        }
    }

    /**
     * 设置或者获取当前请求的content
     * @access public
     * @return string
     */
    /*获取getContent*/
    protected function Getcontent()
    {
        if (is_null($this->content)) {
            $this->content = $this->input;
        }
        return $this->content;
    }

    /**
     * 获取当前请求的php://input
     * @access public
     * @return string
     */
    /*获取getInput*/
    protected function Getinput()
    {
        return $this->input;
    }

    /**
     * 生成请求令牌
     * @access public
     * @param string $name 令牌名称
     * @param mixed  $type 令牌生成方法
     * @return string
     */
    /*获取token*/
    protected function Token($name = '__token__', $type = 'md5')
    {
        $type  = is_callable($type) ? $type : 'md5';
        $token = call_user_func($type, $_SERVER['REQUEST_TIME_FLOAT']);
        if ($this->isAjax()) {
            header($name . ': ' . $token);
        }
        Session::set($name, $token);
        return $token;
    }

    /**
     * 设置当前地址的请求缓存
     * @access public
     * @param string $key 缓存标识，支持变量规则 ，例如 item/:name/:id
     * @param mixed  $expire 缓存有效期
     * @param array  $except 缓存排除
     * @param string $tag    缓存标签
     * @return void
     */
    /*获取cache*/
    protected function Cache($key, $expire = null, $except = [], $tag = null)
    {
        if (!is_array($except)) {
            $tag    = $except;
            $except = [];
        }

        if (false !== $key && $this->isGet() && !$this->isCheckCache) {
            // 标记请求缓存检查
            $this->isCheckCache = true;
            if (false === $expire) {
                // 关闭当前缓存
                return;
            }
            if ($key instanceof \Closure) {
                $key = call_user_func_array($key, [$this]);
            } elseif (true === $key) {
                foreach ($except as $rule) {
                    if (0 === stripos($this->url(), $rule)) {
                        return;
                    }
                }
                // 自动缓存功能
                $key = '__URL__';
            } elseif (strpos($key, '|')) {
                list($key, $fun) = explode('|', $key);
            }
            // 特殊规则替换
            if (false !== strpos($key, '__')) {
                $key = str_replace(['__MODULE__', '__CONTROLLER__', '__ACTION__', '__URL__', ''], [$this->module, $this->controller, $this->action, md5($this->url(true))], $key);
            }

            if (false !== strpos($key, ':')) {
                $param = $this->param();
                foreach ($param as $item => $val) {
                    if (is_string($val) && false !== strpos($key, ':' . $item)) {
                        $key = str_replace(':' . $item, $val, $key);
                    }
                }
            } elseif (strpos($key, ']')) {
                if ('[' . $this->ext() . ']' == $key) {
                    // 缓存某个后缀的请求
                    $key = md5($this->url());
                } else {
                    return;
                }
            }
            if (isset($fun)) {
                $key = $fun($key);
            }

            if (strtotime($this->server('HTTP_IF_MODIFIED_SINCE')) + $expire > $_SERVER['REQUEST_TIME']) {
                // 读取缓存
                $response = Response::create()->code(304);
                throw new \think\exception\HttpResponseException($response);
            } elseif (Cache::has($key)) {
                list($content, $header) = Cache::get($key);
                $response               = Response::create($content)->header($header);
                throw new \think\exception\HttpResponseException($response);
            } else {
                $this->cache = [$key, $expire, $tag];
            }
        }
    }

    /**
     * 读取请求缓存设置
     * @access public
     * @return array
     */
    /*获取getCache*/
    protected function Getcache()
    {
        return $this->cache;
    }




}