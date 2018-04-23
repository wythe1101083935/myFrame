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
    /**
     * 获取变量 支持过滤和默认值
     * @param array         $data 数据源
     * @param string|false  $name 字段名
     * @param mixed         $default 默认值
     * @param string|array  $filter 过滤函数
     * @return mixed
     */
    public function input($data = [], $name = '', $default = null, $filter = '')
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
    }
}