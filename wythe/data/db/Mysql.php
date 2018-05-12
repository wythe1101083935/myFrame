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
        'database'        => 'wf_ffl',
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
        'deploy'          => 0,
        // 数据库读写是否分离 主从式有效
        'rw_separate'     => false,
        // 读写分离后 主服务器数量
        'master_num'      => 1,
        // 指定从服务器序号
        'slave_no'        => '',
        // 是否严格检查字段是否存在
        'fields_strict'   => true,
        // 数据返回类型
        'result_type'     => PDO::FETCH_ASSOC,
        // 数据集返回类型
        'resultset_type'  => 'array',
        // 自动写入时间戳字段
        'auto_timestamp'  => false,
        // 时间字段取出后的默认时间格式
        'datetime_format' => 'Y-m-d H:i:s',
        // 是否需要进行SQL性能分析
        'sql_explain'     => false,
        // 是否需要断线重连
        'break_reconnect' => false,
	];

	/*数据库连接实例*/
	protected $links = [];

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

	/*拼接DSN*/
	protected function parseDsn(){
		$config = $this->config;
		if(!empty($config['socket'])){
			$dsn = 'mysql:unix_soket=' . $config['socket'];
		}elseif(!empty($config['hostport'])){
			$dsn = 'mysql:host=' . $config['hostname'] . ';port=' . $config['hostport'];
		}
		$dsn .= ';dbname=' . $config['database'];

		if(!empty($config['charset'])){
			$dsn .= ';charset=' . $config['charset'];
		}
		return $dsn;
	}
	/*数据库连接*/
	protected function connect($linkNum){
		$config = $this->config;
		if(!isset($this->links[$linkNum])){
			/*获得DSN*/
			$dsn = $this->parseDsn();

			/*连接开始时间*/
			if($config['debug']){
				$startTime = microtime(true);
			}

			/*数据库连接*/
			$this->links[$linkNum] = new \PDO($dsn,$config['username'],$config['password'],$config['params']);

			/*连接结束时间*/
			if($config['debug']){
				echo 'contact time :' . number_format(microtime(true)-$startTime,6);
			}

		}
		return $this->links[$linkNum];
	}	

	/*执行查询函数*/
	public function query($sql,$bind=[],$master=false){
		/*记录sql语句*/
		$this->queryStr = $sql;
		/*数据库连接*/
		$this->connect();
		/*预处理*/
		$this->PDOStatement = $this->linkID->prepare($sql);
		/*绑定参数*/
		$this->bindValue($bind);
		/*执行*/
		$this->PDOStatement->execute();
		/*处理返回结果*/
		return  $this->PDOStatement->fetchAll($this->$config['result_type']);;
	}

	/*参数绑定*/
	protected function bindValue(array $bind = []){
		foreach ($bind as $key => $val) {
			$param = is_numeric($key) ? $key + 1 : ':' . $key;
			if(is_array($val)){
				if(PDO::PARAM_INT == $val[1] && '' == $val[0]){
					$val[0] = 0;
				}
				$result = $this->PDOStatement->bindValue($param,$val[0],$val[1]);
			}else{
				$result = $this->PDOStatement->bindValue($param,$val);
			}
			if(!$result){
				echo 'error';
			}
		}
	}
}