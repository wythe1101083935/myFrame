<?php
namespace think\db;

abstract class Connection{


	//PDO操作实例
	protected $PDOStatement;

	/*当前sql指令*/
	protected $queryStr = '';

	/*返回或者影响行数*/
	protected $numRows = 0;

	/*事务指令*/
	protected $transTimes = 0;

	/*错误信息*/
	protected $error = '';

	/*数据库连接ID 支持多个连接*/
	protected $links = [];

	/*当前连接ID*/
	protected $linkID;

	protected $linkRead;

	protected $linkWrite;

	/*查询结果类型*/
	protected $fetchType = PDO::FETCH_ASSOC;

	/*字段属性大小写*/
	protected $attrCase = PDO::CASE_LOWER;

	/*监听回掉*/
	protected static $event = [];

	/*使用Builder类*/
	protected $builder;

	/*数据库连接参数配置*/
	protected $config = [
		'type'	=> '',
		'hostname'=>'',
		'database'=>'',
		'username'=>'',
		'password'=>'',
		'dsn'	=>'',
		'params' => '',
		'charset'=>'',
		'prefix'=>'',
		'debug'=>false,
		'deploy'=>0,
		'rw_separate'=>false,
		'master_num'=>1,
		'slave_no'=>'',
		'fields_strict'=>true,
		'result_type'=>PDO::FETCH_ASSOC,
		'auto_timestamp'=>false,
		'datetime_format'=>'Y-m-d H:i:s',
		'sql_explain'=>false,
		'builder'=>'',
		'query'=>'\\wythe\\data\\db\\Query',
		'break_reconnect'=>false,
	];

	/*PDO连接参数*/
	protected $params = [
		PDO::ATTR_CASE	=> PDO::CASE_NATURAL,
		PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_ORACLE_NULLS=>PDO::NULL_NATURAL,
		PDO::ATTR_STRINGIFY_FETCHES =>false,
		PDO::ATTR_EMULATE_PREPARES =>false,
	];

	/*绑定参数*/
	protected $bind = [];

	/*构造函数，读取数据库配置信息*/
	public function __construct(array $config=[]){
		if(!empty($config)){
			$this->config = array_merge($this->config,$config);
		}
	}

	/*获取新的查询对象*/
	protected function getQuery(){
		$class = $this->config['query'];
		return new $class($this);
	}

	/*获取当前连接器类对应的builder类*/
	public function getBuilder(){
		if(!empty($this->builder){
			return $this->builder;
		}else{
			return $this->getConfig('builder') ? '\\wythe\\db\\builder\\' . ucfirst($this->getConfig('type'));
		}
	}

	/*调用query类的查询方法*/
	public function __call($method,$args){
		return call_user_func_array([$this->getQuery(),$method],$args);
	}

	/*解析pdo连接的dsn信息*/
	abstract protected function parseDsn($config);

	/*取得数据表的字段信息*/
	abstract public function getFields($tableName);

	/*去读数据库的表信息*/
	abstract public function getTables($dbName);

	/*sql性能分析*/
	abstract protected function getExplain($sql);


	/*对返数据表字段信息进行大小写转换处理*/
	public function fieldCase($info){
		switch ($this->attrCase){
			case PDO::CASE_LOWER:
				$info = array_chage_key_case($info);
				break;
			case PDO::CASE_UPPER:
				$info = array_change_key_case($info,CASE_UPPER);
				break;
			case PDO::CASE_NATURAL:
			default:
				//不转换
		}
	}

	/*获取数据库的配置参数*/
	public function getConfig($config = ''){
		return $config ? $this->config[$config] : $this->config;
	}

	/*设置数据库的配置参数*/
	public function setConfig($config,$value=''){
		if(is_array($config)){
			$this->config = array_merge($this->config,$config);
		}else{
			$this->config[$config] = $value;
		}
	}

	/*连接数据库方法*/
	public function connect(array $config=[],$linkNum = 0 ,$autoConnection=false){
		if(!isset($this->links[$linkNum])){
			if(!$config){
				$config = $this->config;
			}else{
				$config = array_merge($this->config,$config);
			}
			/*连接参数*/
			if(isset($config['params']) && is_array($config['params'])){
				$params = $config['params'] + $this->params;
			}else{
				$params = $this->params;
			}
			//记录当前字段属性大小写设置
			$this->attrCase = $params[PDO::ATTR_CASE];

			/*数据返回类型*/
			if(isset($config['result_type'])){
				$this->fetchType = $config['result_type'];
			}

			try{
				if(empty($config['dsn'])){
					$config['dsn'] = $this->parseDsn($config);
				}

				if($config['debug']){
					$startTime = microtime(true);
				}

				$this->links[$linkNum] = new PDO($config['dsn'],$config['username'],$config['password'],$params);

				if($config['debug']){
					//记录数据库连接信息
				}


			} catch(\PDOException $e){
				if($autoConnection){
					//Log::record();
					return $this->connect($autoConnection,$linkNum);
				}else{
					throw $e;
				}
			}


		}
		return $this->link[$linkNum]
	}

	/*释放查询结果*/
	public function free(){
		$this->PDOStatement = null;
	}

	/*获取PDO对象*/
	public function getPdo(){
		if(!$this->linkID){
			return false;
		}else{
			return $this->linkID;
		}
	}

}