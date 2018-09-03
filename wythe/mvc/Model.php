<?php
/**
 +----------------------------------------------------------
 * mvc 模型类 $request->user()->tasks()
 +----------------------------------------------------------
 * CODE:
 +----------------------------------------------------------
 * TIME:alt+t
 +----------------------------------------------------------
 * author:wythe(汪志虹)
 +----------------------------------------------------------
 */
namespace wythe\mvc;

abstract class Model{
	/*主导地位的模型*/
	protected $main;

	/*被关系的模型，例如：user有多个task，user是主导地位模型，task是被关系的模型，task属于一个user，这里则相反*/
	protected $related;

	/*正在构造的sql语句*/
	protected $query;
 
	/*表名*/
	protected $tableName;

	/*主键*/
	protected $primaryKey = 'id';


	public function __construct(){
		$this->tableName = strtolower(get_called_class());//获取表名
		$this->primaryKey = $this->tableName.'_id';//获取主键
	}

	/*建立关系*/
	public function relate($relationShip,$related,$main = $this){
		$realtionClass = __NAMESPACE__.'\\model\\'.ucwords($relationShip)
		new $relationClass($main,$related);
	}

	/**/

}