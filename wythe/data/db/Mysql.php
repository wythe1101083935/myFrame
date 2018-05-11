<?php
namespace wythe\data\db;
class Mysql{

	/*连接参数*/
	protected $config = [


	];

	/*数据库连接实例*/
	protected $links = [];

	/*当前sql*/
	protected $queryStr = '';

	/*查询结果*/
	protected $PDOStatement;

	/*构造函数,获取连接参数 准备连接*/
	public function __construct(array $config=[]){
		$this->config = $config + $this->config;
	}


	/*数据库连接*/
	protected function connect($linkNum,$config){
		if(!isset($this->links[$linkNum])){
			/*记录当前字段属性大小写设置*/
			$this->attrCase = $params[\PDO::ATTR_CASE];

			/*记录返回数据类型*/
			$this->fetchType = $config['result_type'];

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
		$this->initConnect($master);

		/*预处理*/
		$this->PDOStatement = $this->linkID->prepare($sql);
		/*绑定参数*/
		$this->bindValue($bind);



		/*执行*/
		


		/*处理返回结果*/
	}


}