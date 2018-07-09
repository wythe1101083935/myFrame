<?php
namespace wythe\data\db;

use \PDO;


abstract class Builder{
  /*connection对象实例*/
  protected $connection;

  /*查询对象实例*/
  protected $query;

  /*数据库表达式*/
  protected $exp = [
    'eq' => '=', 
    'neq' => '<>', 
    'gt' => '>', 
    'egt' => '>=', 
    'lt' => '<', 
    'elt' => '<=', 
    'notlike' => 'NOT LIKE', 
    'not like' => 'NOT LIKE', 
    'like' => 'LIKE', 
    'in' => 'IN', 
    'exp' => 'EXP', 
    'notin' => 'NOT IN', 
    'not in' => 'NOT IN', 
    'between' => 'BETWEEN', 
    'not between' => 'NOT BETWEEN', 
    'notbetween' => 'NOT BETWEEN', 
    'exists' => 'EXISTS', 
    'notexists' => 'NOT EXISTS', 
    'not exists' => 'NOT EXISTS', 
    'null' => 'NULL', 
    'notnull' => 'NOT NULL', 
    'not null' => 'NOT NULL', 
    '> time' => '> TIME', 
    '< time' => '< TIME', 
    '>= time' => '>= TIME', 
    '<= time' => '<= TIME', 
    'between time' => 'BETWEEN TIME', 
    'not between time' => 'NOT BETWEEN TIME', 
    'notbetween time' => 'NOT BETWEEN TIME'
  ];
  
  /*SQL表达式*/
  protected $selectSql = 'SELECT%DISTINCT% %FIELD% FROM %TABLE%%FORCE%%JOIN%%WHERE%%GROUP%%HAVING%%UNION%%ORDER%%LIMIT%%LOCK%%COMMENT%';

  protected $insertSql = '%INSERT% INTO %TABLE% (%FIELD%) VALUES (%DATA%) %COMMENT%';

  protected $insertAllSql = '%INSERT% INTO %TABLE% (%FIELDS%) %DATA% %COMMENT%';

  protected $updateSql = 'UPDATE %TABLE% SET %SET% %JOIN% %WHERE% %ORDER%%LIMIT% %LOCK%%COMMENT%';

  protected $deleteSql = 'DELETE FROM %TABLE% %USING% %JOIN% %WHERE% %ORDER%%LIMIT% %LOCK%%COMMENT';


  /*生成查询sql*/
  public function select($options = []){
    $sql = str_replace(
      [  
        '%DISTINCT%',
        '%FIELD%',
        '%TABLE%',
        '%FORCE%', 
        '%JOIN%',
        '%WHERE%',
        '%GROUP%',
        '%HAVING%',
        '%UNION%',
        '%ORDER%',
        '%LIMIT%',
        '%LOCK%',
        '%COMMENT%',
      ],
      [
        $this->parseDistinct($options['distinct']),
        $this->parseField($options['field']),
        $this->parseTable($options['table']),
        $this->parseForce($options['force']),
        $this->parseJoin($options['join']),
        $this->parseWhere($options['where']),
        $this->parseGroup($options['group']),
        $this->parseHaving($options['having']),
        $this->parseUnion($options['union']),
        $this->parseOrder($options['order']),
        $this->parseLimit($options['limit']),
        $this->parseLock($options['lock']),
        $this->parseComment($options['comment']),
      ],
      $this->selectSql
    );
    return $sql;
  }

  /*distinct*/
  protected function parseDistinct($distinct){
    return !empty($distinct) ? ' DISTINCT ' : '';
  }

  /*field*/
  protected function parseField($fields){
    if('*' == $fields || empty($fields)){
      $fieldsStr = '*';
    } elseif (is_array($fields)){
      $fieldsStr = implode(',',$fields);
    } else {
      $fieldsStr = $fields;
    }
    return $fieldsStr;
  }

  /*table*/
  protected function parseTable($tables){
    return $tables;
  }

  /*force 强制使用索引*/
  protected function parseForce($index){
    if(empty($index)){
      return '';
    } else {
      return sprintf(" FORCE INDEX ( %s ) ",is_array($index) ? implode(',',$index) : $index);
    }
  }

  /*join*/
  protected function parseJoin($join){
    return $join;
  }

  /*where*/
  protected function parseWhere($where){
    return $where;
  }

  /*group*/ 
  protected function parseGroup($group){
    return $group;
  }

  /*having*/
  protected function parseHaving($having){
    return $having;
  }

  /*union*/
  protected function parseUnoin($union){
    return $union;
  }

  /*order*/
  protected function parseOrder($order){
    return $order;
  }

  /*limit*/
  protected function parseLimit($limit){
    return $limit;
  }

  /*lock*/
  protected function parseLock($lock){
    return $lock;
  }

  /*comment*/
  protected function parseComment($comment){
    return $comment;
  }


  
}