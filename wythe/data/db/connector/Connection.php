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
}