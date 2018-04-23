<?php

namespace wythe;

class Request{

	protected function __construct($options = []){
		foreach ($options as $name => $item) {
			if(property_exists($this,$name)){
				$this->$name = $item;
			}
		}
		$this->input = file_get_contents('php://input');
		$this->server = $_SERVER;
	}
	/*object 对象实例*/
	protected static $instance;
	public static function instance($options=[]){
		if(is_null(self::$instance)){
			self::$instance = new static($options);
		}
		return self::$instance;
	}

	/*请求类型*/
	protected $method;
	public function method($method = false){
		if(true === $method){
			//获取原始请求类型
			return IS_CLI ? 'GET' : $this->server['REQUEST_METHOD'];
		}elseif(!$this->method){
			//自定义类型
			if(isset($_POST[Config::get('var_method')])){
				$this->method = strtoupper($_POST[Config::get('var_method')]);
				$this->{$this->method}($_POST);
			}elseif(isset($this->server['HTTP_X_HTTP_METHOD_OVERRIDE'])){
				$this->method = strtoupper($this->server['HTTP_X_HTTP_METHOD_OVERRIDE']);
			}else{
				$this->method = IS_CLI ? 'GET' : $this->server['REQUEST_METHOD']
			}
		}
		return $this->method;
	}

	/*域名*/
	protected $domain;
	public function domain($domain = null){
		if(!is_null($domain)){
			$this->domain = $domain;
		}
		if(!$this->domain){
			$this->domain = $this->scheme() . '://' .$this->host();
		}
		return $this->domain;
	}
	/*URL地址*/
	protected $url;
    /**
     * 设置或获取当前完整URL 包括QUERY_STRING
     * @access public
     * @param string|true $url URL地址 true 带域名获取
     * @return string
     */
	public function url($url = null){
		if(!is_null($url) && true !== $url){
			$this->url = $url;
		}elseif(!$this->url){
			if(IS_CLI){
                $this->url = isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : '';
            } elseif (isset($_SERVER['HTTP_X_REWRITE_URL'])) {
                $this->url = $_SERVER['HTTP_X_REWRITE_URL'];
            } elseif (isset($_SERVER['REQUEST_URI'])) {
                $this->url = $_SERVER['REQUEST_URI'];
            } elseif (isset($_SERVER['ORIG_PATH_INFO'])) {
                $this->url = $_SERVER['ORIG_PATH_INFO'] . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
            } else {
                $this->url = '';
            }
		}
		return true === $url ? $this->domain() . $this->url : $this->url;
	}
	/*基础url*/
	protected $baseUrl;
	public function baseUrl($url = null){
		if(!is_null($url) && true !== $url){
			$this->baseUrl = $url;
			return $this;
		}elseif(!$this->baseUrl){
			$str = $this->url();
			$this->baseUrl = strpos($str,'?') ? strstr($str,'?',true) : $str;
		}
		return true===$url ? $this->domain() . $this->baseUrl : $this->baseUr;
	}
	/*保存server*/
	protected $server;
	public function server($name='',$default = null,$filter=''){
        if (empty($this->server)) {
            $this->server = $_SERVER;
        }
		if(is_array($name)){
			return $this->server = array_merge($this->server,$name);
		}
		return $this->input($this->server,false===$name ? false : strtoupper($name),$default,$filter);//过滤一遍？
	}

	/*当前执行的文件*/
	protected $baseFile;
    /**
     * 设置或获取当前执行的文件 SCRIPT_NAME
     * @access public
     * @param string $file 当前执行的文件
     * @return string
     */
    public function baseFile($file = null)
    {
        if (!is_null($file) && true !== $file) {
            $this->baseFile = $file;
            return $this;
        } elseif (!$this->baseFile) {
            $url = '';
            if (!IS_CLI) {
                $script_name = basename($_SERVER['SCRIPT_FILENAME']);
                if (basename($_SERVER['SCRIPT_NAME']) === $script_name) {
                    $url = $_SERVER['SCRIPT_NAME'];
                } elseif (basename($_SERVER['PHP_SELF']) === $script_name) {
                    $url = $_SERVER['PHP_SELF'];
                } elseif (isset($_SERVER['ORIG_SCRIPT_NAME']) && basename($_SERVER['ORIG_SCRIPT_NAME']) === $script_name) {
                    $url = $_SERVER['ORIG_SCRIPT_NAME'];
                } elseif (($pos = strpos($_SERVER['PHP_SELF'], '/' . $script_name)) !== false) {
                    $url = substr($_SERVER['SCRIPT_NAME'], 0, $pos) . '/' . $script_name;
                } elseif (isset($_SERVER['DOCUMENT_ROOT']) && strpos($_SERVER['SCRIPT_FILENAME'], $_SERVER['DOCUMENT_ROOT']) === 0) {
                    $url = str_replace('\\', '/', str_replace($_SERVER['DOCUMENT_ROOT'], '', $_SERVER['SCRIPT_FILENAME']));
                }
            }
            $this->baseFile = $url;
        }
        return true === $file ? $this->domain() . $this->baseFile : $this->baseFile;
    }

    /*全局过滤规则*/
    protected $filter;
    public function filter($filter = null){
    	if(is_null($fileter)){
    		return $this->filter;
    	}else{
    		$this->filter = $filter;
    	}
    }

    /*当前语言集*/
    protected $langset;
    public function langset($lang = null){
    	if(!is_null($lang)){
    		$this->langset = $lang;
    	}else{
    		return $this->langset ? : '';
    	}
    }

    /*当前调度信息*/
    protected $dispatch = [];
    protected $module;
    protected $controller;
    protected $action;
    public function dispatch($dispatch = null){
    	if(!is_null($dispatch)){
    		$this->dispatch = $dispatch;
    	}
    	return $this->dispatch;
    }
    public function module($module = null){
    	if(!is_null($module)){
    		$this->module = $module;
    		return $this;
    	}else{
    		return $this->module ? : '';
    	}
    }
    public function controller($controller = null){
    	if(!is_null($controller)){
    		$this->controller = $controller;
    		return $this;
    	}else{
    		return $this->controller ? : '';
    	}
    }
    public function action($action = null){
    	if(!is_null($action) && !is_bool($action)){
    		$this->action = $action;
    		return $this;
    	}else{
    		$name = $this->action ? : '';
    		return true === $action ? $name : strtolower($name);
    	}
    }

}