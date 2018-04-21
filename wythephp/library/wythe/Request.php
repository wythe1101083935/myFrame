<?php
namespace wythe;
class Request{
	/*对象实例,单态*/
	protected static $instance;

	/**/
	protected $method;

	/*域名*/
	protected $domain;

	/*url地址*/
	protected $url;

	/*基础url*/
	protected $baseUrl;

	/*当前执行的文件*/
	protected $baseFiel;

	/*访问的ROOT地址*/
	protected $root;

	/*pathinfo*/
	protected $pathinfo;

	/*pathinfo 不含后缀*/
	protected $path;

	/*当前路由信息*/
	protected $routeInfo = [];

	/*环境变量*/
	protected $env;

	/*当前调度信息*/
	protected $dispatch = [];
	protected $module;
	protected $controller;
	protected $action;

	/*请求参数*/
	protected $param = [];
	protected $get = [];
	protected $post = [];
	protected $request = [];
	protected $route = [];
	protected $put;
	protected $session = [];
	protected $file = [];
	protected $cookie = [];
	protected $server = [];
	protected $header = [];


	/*资源类型*/
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

	/**/
	protected $content;

	/*全局过滤规则*/
	protected $filter;

	/*Hook扩展方法*/
	protected static $hook = [];

	/*绑定的属性*/
	protected $bing = [];

	//php://input
	protected $input;

	//请求缓存
	protected $cache;

	//缓存是否检查
	protected $isCheckCache;

	/*构造函数*/
	protected function __construct($options = []){
		foreach($options as $name =>$item){
			if(property_exists($this,$name)){
				$this->$name = $item;
			}
		}

		if(is_null($this->filter)){
			$this->filter = Config::get('default_filter');
		}

		/*保存php://input*/
		$this->input = file_get_contents('php://input');
	}

	/*初始化单态*/
	public static function instance($opions = []){
		if(is_null(self::$instance)){
			self::$instance = new static($options);
		}
		return self::$instance;
	}

	/*创建一个URL请求*/
	public static function create($uri,$method = 'GET',$params=[],$cookie = [] ,$files = [],$server = [],$content = null){
		$server['PATH_INFO'] = '';
		$server['REQUEST_METHOD'] = strtoupper($method);
		$info = parse_url($uri);
		if(isset($info['host'])){
			$server['SERVER_NAME'] = $info['hose'];
			$server['HTTP_HOST'] = $info['host'];
		}

		if(isset($info['scheme'])){
			if('https' == $info['scheme']){
				$server['HTTPS'] = 'on';
				$server['SERVER_PORT'] = 80;
			}
		}

		if(isset($info['port'])){
			$server['SERVER_PORT'] = $info['port'];
			$server['HTTP_HOST'] = $server['HTTP_HOST'] . ':' . $info['port'];
		}

		if(isset($info['user'])){
			$server['PHP_AUTH_USER'] = $info['user'];
		}

		if(isset($info['pass'])){
			$server['PHP_AUTH_PW'] = $info['pass'];
		}

		if(!isset($info['path'])){
			$info['path'] = '/';
		}

		$options = [];
		$options[strtolower($method)] = $params;
		$queryString = '';
		if(isset($info['query'])){
			parse_str(html_entity_decode($info['query'],$query));
			if(!empty($params)){
				$params = array_replace($query,$params);
				$queryString = http_biuld_query($params,'','&');
			} else {
				$params = $query;
				$queryString = $info['query'];
			}
		} elseif (!empty($params)){
			$queryString = http_build_query($params,'','&');
		}

		if($queryString){
			parse_str($queryString,$get);
			$options['get'] = isset($options['get']) ? array_merge($get,$options['get']) : $get;
		}

		$server['REQUEST_URI'] = $info['path'] . ('' !== $queryString ? '?'.$queryString : '');
		$server['QUERY_STRING'] = $queryString;
		$options['cookie'] = $cookie;
		$options['param'] = $params;
		$options['file'] = $files;
		$options['server'] = $server;
		$options['url'] = $server['REQUEST_URI'];
		$options['pathinfo'] = '/'== $info['path'] ? '/' : ltrim($info['path'],'/');
		$options['method'] = $server['REQUESET_METHOD'];
		$options['domain'] = isset($info['scheme']) ? $info['scheme'] . '://' . $server['HTTP_HOST'] : '';
		$options['content'] = $content;
		self::$instance = new static($options);
		return self::$instance;
	}
}