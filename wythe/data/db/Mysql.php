<?php
namespace wythe\data\db;
use \PDO;
class Mysql{

	/*连接参数*/
	protected $config = [
        // 数据库类型
        'type'            => 'mysql',
        // 服务器地址
        'hostname'        => 'localhost',
        // 数据库名
        'database'        => 'knowlege',
        // 用户名
        'username'        => 'root',
        // 密码
        'password'        => '123456',
        // 端口
        'hostport'        => '3306',
        // 数据库连接参数
        'params'          => [],
        // 数据库编码默认采用utf8
        'charset'         => 'utf8',
        // 数据库表前缀
        'prefix'          => '',
        // 数据库调试模式
        'debug'           => false,
        // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
        //'deploy'          => false,
        // 数据库读写是否分离 主从式有效
        'rw_separate'     => false,
        'readServer'=>[
        	'hostname'=> '127.0.0.1','database' => 'knowlege','username' => 'root','password' => '123456','hostport' => '3306','params' => [],'charset'=> 'utf8','rate'=>1,
        	'hostname'=> '127.0.0.1','database' => 'knowlege','username' => 'root','password' => '123456','hostport' => '3306','params' => [],'charset'=> 'utf8','rate'=>1,
        	'hostname'=> '127.0.0.1','database' => 'knowlege','username' => 'root','password' => '123456','hostport' => '3306','params' => [],'charset'=> 'utf8','rate'=>1,
        ],
        // 读写分离后 主服务器数量
        //'master_num'      => 1,
        // 指定从服务器序号
        //'slave_no'        => '',
        // 是否严格检查字段是否存在
        //'fields_strict'   => true,
        // 数据返回类型
        'result_type'     => PDO::FETCH_ASSOC,
        // 数据集返回类型
        'resultset_type'  => 'array',
        // 自动写入时间戳字段
        //'auto_timestamp'  => false,
        // 时间字段取出后的默认时间格式
        //'datetime_format' => 'Y-m-d H:i:s',
        // 是否需要断线重连
        //'break_reconnect' => false,
	];

	/*数据库连接实例-读*/
	protected $readLink=null;

	/*数据库连接实例-写*/
	protected $writeLink=null;

	/*当前sql*/
	protected $queryStr = '';

	/*绑定参数*/
	protected $bind = [];

	/*当前预处理对象*/
	protected $PDOStatement;

	/*事务指令数*/
	protected $transTimes = 0;

	/*PDO连接参数*/
	protected $params = [
        PDO::ATTR_CASE              => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE           => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS      => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_EMULATE_PREPARES  => false,	
	];

	/*构造函数,获取连接参数 准备连接*/
	public function __construct(array $config=[]){
		$this->config = $config + $this->config;
	}

	/*连接master*/
	protected function connectExecute(){
		if(is_null($this->writeLink)){
			$this->writeLink = $this->createPDO($this->config);	
			return $this->writeLink;			
		}
	}	
	/*连接slave*/
	protected function conectQuery(){
		if(is_null($this->readLink)){
			if($this->config['rw_separate']){/*如果是读写分离*/
				$total = 0;
				foreach ($this->config['readServer'] as $val) {
					$total += $val['rate'];
				}
				$rand = random_int(0,$total-1);
				foreach ($this->config['readServer'] as $key => $val) {
					$rand -= $val['rate'];
					if($rand<0){
						$sign = $key;
						break;
					}
				}
				$readLink = $this->createPDO($this->config['readServer'][$sign]);
			}else{
				$this->readLink = $this->writeLink;
			}
		}
	}

	/*pdo连接数据库*/
	protected function createPDO($conf){
		$dsn = 'mysql:host=' . $conf['hostname'] . ';port=' . $conf['hostport'] . ';dbname=' . $conf['database'];
		if(!empty($config['charset'])){
			$dsn .= ';charset=' . $conf['charset'];
		}
		return new \PDO($dsn,$conf['username'],$conf['password']);
	}

	/*执行查询函数*/
	public function query($sql,$bind=[],$type="query"){
		/*记录sql语句*/
		$this->queryStr = $sql;
		/*数据库连接*/
		$connect = 'connect'.ucfirst($type);
		$link = $this->$connect($type);
		/*预处理*/
		$this->PDOStatement =$link->prepare($sql);
		/*绑定参数*/
		$this->bindValue($bind);
		/*执行*/
		$this->PDOStatement->execute();
		/*处理返回结果*/
		if($type == 'query'){
			return  $this->PDOStatement->fetchAll($this->config['result_type']);
		}else{
			return $this->PDOStatement->rowCount();
		}
	}

	/*参数绑定*/
	protected function bindValue(array $bind = []){
		foreach ($bind as $key => $val) {
			$param = $key;
			$result = $this->PDOStatement->bindValue($param,$val); //$this->bindType($val)
			if(!$result){
				echo 'error';
			}
		}
	}

	/*设置绑定类型*/
	protected function bindType($val){
		if(is_bool($val)) return PDO::PARAM_BOOL;
		if(is_int($val)) return PDO::PARAM_INT;
		if(is_string($val)) return PDO::PARAM_STR;
		return PDO::PARAM_STR;
	}
}
