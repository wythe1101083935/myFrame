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
}