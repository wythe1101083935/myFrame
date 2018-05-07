<?php
namespace wythe\system;

class Request{
    /*object 对象实例*/
    protected static $instance;
    /*Hook扩展方法*/
    protected static $hook = [];

    protected $requestData = [
        'method'=>null,
        'domain'=>null,
        'host'=>null,
        'scheme'=>null,
        'url'=>null,
        'baseUrl'=>null,
        'baseFile'=>null,
        'root'=>null,
        'pathInfo'=>null,
        'path'=>null,
        'ext'=>null,
        'port'=>null,
        'routeInfo'=>null,
        'env'=>null,
        'dispatch'=>null,
        'module'=>null,
        'controller'=>null,
        'action'=>null,
        'langset'=>null,
        'param'=>null,
        'get'=>null,
        'post'=>null,
        'request'=>null,
        'route'=>null,
        'put'=>null,
        'session'=>null,
        'file'=>null,
        'cookie'=>null,
        'server'=>null,
        'header'=>null,
        'mimeType'=>[
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
        ],
        'type'=>null,
        'content'=>null,
        'filter'=>null,
        'bind'=>null,
        'input'=>null,
        'cache'=>null,
        'isCheckCache'=>null,
        'isMobile'=>null,
        'isGet'=>null,
        'isPost'=>null,
        'isPut'=>null,
        'isDelete'=>null,
        'isHead'=>null,
        'isPatch'=>null,
        'isOptions'=>null,
        'isCli'=>null,
        'isCgi'=>null,
        'isSsl'=>null,
        'isAjax'=>null,
        'isPjax'=>null,
    ];
    protected $config = [
        'filter'=>'',
        'var_pathinfo'=>'',
        'https_agent_name'=>'',
    ];
    /**
     * 构造函数
     * @access protected
     * @param array $options 参数
     */
    protected function __construct($options = []){
        foreach ($options as $name => $item) {
            if (isset($this->requestData[$name])) {
                $this->requestData[$name] = $item;
            }
        }
    }

    /*初始化*/
    public static function instance($options = [])
    {
        if (is_null(self::$instance)) {
            self::$instance = new static($options);
        }
        return self::$instance;
    }    

    /*设置属性，获取属性*/
    public function attr($name,$value=false){
    	if(!is_null($value) && $value !== false){//设置值
    		if(is_array($this->requestData[$name])){
    			if(is_array($value)){
    				$this->requestData[$name] = $value + $this->requestData[$name];
    			}
    		}else{
    			$this->requestData[$name]  = $value;
    		}
    		return $this;
    	}elseif($value === false && is_null($this->requestData[$name])){//取值
    		if(method_exists($this,'get'.ucfirst($name))){
    			$this->requestData[$name] = $this->{'get'.$name}(); 	
                return $this->requestData[$name];	
    		}else{
                return 'Error';
            }
    	}else{
    		return $this->requestData[$name]  ? : '';
    	}
    }	
    public function __set($name,$value){
    	$this->attr($name,$value);
    }
    public function __get($name){
    	return $this->attr($name,false);
    }
    /*获取server*/
    protected function getServer(){
        return $_SERVER;
    }

    /*获取isSsl*/
    protected function getIsSsl(){
        $server = $this->attr('server');
        if (isset($server['HTTPS']) && ('1' == $server['HTTPS'] || 'on' == strtolower($server['HTTPS']))) {
           return true;
        } elseif (isset($server['REQUEST_SCHEME']) && 'https' == $server['REQUEST_SCHEME']) {
           return true;
        } elseif (isset($server['SERVER_PORT']) && ('443' == $server['SERVER_PORT'])) {
           return true;
        } elseif (isset($server['HTTP_X_FORWARDED_PROTO']) && 'https' == $server['HTTP_X_FORWARDED_PROTO']) {
           return true;
        } elseif ($this->config['https_agent_name'] && isset($server[$this->config['https_agent_name']])) {
           return true;
        }else{
           return false;
        }
    }

    /*获取isAjax*/
    protected function getIsjax(){
        $value = strtolower($this->attr('server')['HTTP_X_REQUESTED_WITH']);
        $result = ('xmlhttprequest' == $value) ? true : false;
        return $result;
    }
    /*获取isPjax*/
    protected function getIsPjax(){
        $result = !is_null($this->attr('server')['HTTP_X_PJAX']) ? true : false;
        return $result;
    }
    /*获取query*/
    protected function getQuery(){
       return $this->attr('server')['QUERY_STRING'];
    }
    /*获取host*/
    protected function getHost(){
        if(isset($this->attr('server')['HTTP_X_REAL_HOST'])){
            return $this->attr('server')['HTTP_X_REAL_HOST'];
        }else{
            return $this->attr('server')['HTTP_HOST'];
        }
        return ;
    }
    /*获取port*/
    protected function getPort(){
        return $this->attr('server')['SERVER_PORT'];
    }
    /*获取url*/
    protected function getUrl(){
        $server = $this->attr('server');
        if (IS_CLI) {
           return isset($server['argv'][1]) ? $server['argv'][1] : '';
        } elseif (isset($server['HTTP_X_REWRITE_URL'])) {
           return $server['HTTP_X_REWRITE_URL'];
        } elseif (isset($server['REQUEST_URI'])) {
           return $server['REQUEST_URI'];
        } elseif (isset($server['ORIG_PATH_INFO'])) {
           return $server['ORIG_PATH_INFO'] . (!empty($server['QUERY_STRING']) ? '?' . $server['QUERY_STRING'] : '');
        }else{
            return false;
        }
    }  

    /*获取baseUrl*/  
    protected function getBaseUrl(){
        $str           = $this->attr('url');
        return strpos($str, '?') ? strstr($str, '?', true) : $str;    	
    }

    /*获取当前执行文件*/
    protected function getBaseFile(){
        if (!IS_CLI) {
            $server = $this->attr('server');
            $script_name = basename($server['SCRIPT_FILENAME']);
            if (basename($server['SCRIPT_NAME']) === $script_name) {
               return $server['SCRIPT_NAME'];
            } elseif (basename($server['PHP_SELF']) === $script_name) {
               return $server['PHP_SELF'];
            } elseif (isset($server['ORIG_SCRIPT_NAME']) && basename($server['ORIG_SCRIPT_NAME']) === $script_name) {
              return $server['ORIG_SCRIPT_NAME'];
            } elseif (($pos = strpos($server['PHP_SELF'], '/' . $script_name)) !== false) {
              return substr($server['SCRIPT_NAME'], 0, $pos) . '/' . $script_name;
            } elseif (isset($server['DOCUMENT_ROOT']) && strpos($server['SCRIPT_FILENAME'], $server['DOCUMENT_ROOT']) === 0) {
               return str_replace('\\', '/', str_replace($server['DOCUMENT_ROOT'], '', $server['SCRIPT_FILENAME']));
            }else{
                return false;
            }
        }   	
    }

    /*获取root*/
    protected function getRoot(){
        $file = $this->attr('baseFile');
        if ($file && 0 !== strpos($this->attr('url'), $file)) {
            $file = str_replace('\\', '/', dirname($file));
        }
        return rtrim($file, '/');    	
    }

    /*获取scheme*/
    protected function getSheme(){
        return $this->attr('isSsl') ? 'https' : 'http';
    }
    /*获取domain*/
    protected function getDomain(){
    	return  $this->attr('scheme') . '://' . $this->attr('host');
    }

    /*获取pathinfo*/
    protected function getPathinfo(){     
        if (isset($_GET[$this->config['var_pathinfo']])) {
            // 判断URL里面是否有兼容模式参数
            $_SERVER['PATH_INFO'] = $_GET[$this->config['var_pathinfo']];
            unset($_GET[$this->config['var_pathinfo']]);
        } elseif (IS_CLI) {
            // CLI模式下 index.php module/controller/action/params/...
            $_SERVER['PATH_INFO'] = isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : '';
        }

        // 分析PATHINFO信息
        if (!isset($_SERVER['PATH_INFO'])) {
            foreach ($this->config['pathinfo_fetch'] as $type) {
                if (!empty($_SERVER[$type])) {
                    $_SERVER['PATH_INFO'] = (0 === strpos($_SERVER[$type], $_SERVER['SCRIPT_NAME'])) ?
                    substr($_SERVER[$type], strlen($_SERVER['SCRIPT_NAME'])) : $_SERVER[$type];
                    break;
                }
            }
        }
        return empty($_SERVER['PATH_INFO']) ? '/' : ltrim($_SERVER['PATH_INFO'], '/');
    }
    /*获取path*/
    protected function getPath(){
        $suffix   = $this->config['url_html_suffix'];
        $pathinfo = $this->attr('pathinfo');
        if (false === $suffix) {
            // 禁止伪静态访问
            return $pathinfo;
        } elseif ($suffix) {
            // 去除正常的URL后缀
            return preg_replace('/\.(' . ltrim($suffix, '.') . ')$/i', '', $pathinfo);
        } else {
            // 允许任何后缀访问
            return preg_replace('/\.' . $this->ext() . '$/i', '', $pathinfo);
        }    	
    }
    /*获取当前url访问后缀*/
    protected function getExt(){
		return $float ? $_SERVER['REQUEST_TIME_FLOAT'] : $_SERVER['REQUEST_TIME'];    	
    }
    /*获取当前请求的资源类型*/
    protected function getType(){
        $accept = $this->attr('server')['HTTP_ACCEPT'];
        if (empty($accept)) {
        	$this->type = false;
        }
        foreach ($this->attr('mimeType') as $key => $val) {
            $array = explode(',', $val);
            foreach ($array as $k => $v) {
                if (stristr($accept, $v)) {
                	return $key;
                    
                }
            }
        }
        return false;
    }
    /*获取当前的请求类型*/
    protected function getMethod(){
        if (isset($_POST[$this->config['var_method']])) {
            return strtoupper($_POST[$this->config['var_method']]);
        } elseif (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
            return strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
        } else {
            return IS_CLI ? 'GET' : $this->attr('server')['REQUEST_METHOD'];
        }    	
    }
    protected function getIsGet(){
    	return $this->attr('method') == 'GET';
    }
    protected function getIsPost(){
    	return $this->attr('method') == 'POST';
    }
    protected function getIsPut(){
    	return $this->attr('method') == 'PUT';
    }
    protected function getIsDelete(){
    	return $this->attr('method') == 'DELETE';
    }
    protected function getIsHead(){
    	return $this->attr('method') == 'HEAD';
    }
    protected function getIsPatch(){
    	return $this->attr('method') == 'PATCH';
    }
    protected function getIsOptions(){
    	return $this->attr('method') == 'OPTIONS';
    }
    protected function getIsCli(){
    	return IS_CLI;
    }

    protected function getIsCgi(){
    	return strpos(PHP_SAPI,'cgi') === 0;
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
        return $this->attr('route')+$vars+$this->attr('get');  	
    }
    /*获取get*/
    protected function getGet(){
		return $_GET;    	
    }
    /*获取post*/
    protected function getPost(){
        $content = $this->input;
        if (false !== strpos($this->attr('contentType'), 'application/json')) {
            return (array) json_decode($content, true);
        } else {
            return $_POST;
        }
    }
    /*获取put*/
    protected function getPut(){
        $content = $this->input;
        if (false !== strpos($this->attr('contentType'), 'application/json')) {
            return (array) json_decode($content, true);
        } else {
            parse_str($content, $data);
            return $data;
        }        
    }

    /*获取contentType*/
    protected function getContentType(){
        $contentType = $this->attr('server')['CONTENT_TYPE'];
        if ($contentType) {
            if (strpos($contentType, ';')) {
                list($type) = explode(';', $contentType);
            } else {
                $type = $contentType;
            }
            return trim($type);
        }else{
            return '';
        }
    }

    /*获取scheme*/
    protected function getScheme(){
       return $this->attr('isSsl') ? 'https' : 'http';
    }


    /*获取coentent*/
    protected function getContent(){
        return $this->attr('input');
    }
    /*获取input*/
    protected function getInput(){
        return file_get_contents('php://input');
    }
}