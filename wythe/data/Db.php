<?php
/**
 +----------------------------------------------------------
 * 数据库 连接 处理sql 执行
 +----------------------------------------------------------
 * CODE:
 +----------------------------------------------------------
 * TIME:2018-06-26 11:20:44
 +----------------------------------------------------------
 * author:wythe(汪志虹)
 +----------------------------------------------------------
 */
namespace wythe\data;
class Db{
    /*db单例*/
    private static $dbInstance = null;

    /*数据库连接句柄*/
    private static $instance = [];

    /*SQL*/
    private static $SQL = [
        'select'=>[
            ['%DISTINCT%','%FIELD%','%TABLE%','%FORCE%','%JOIN%','%WHERE%','%GROUP%','%HAVING%','%UNION%','%ORDER%','%LIMIT%','%LOCK%','%COMMENT%'],
            'SELECT%DISTINCT% %FIELD% FROM %TABLE%%FORCE%%JOIN%%WHERE%%GROUP%%HAVING%%UNION%%ORDER%%LIMIT%%LOCK%%COMMENT%'
        ],
        'insert'=>[
            ['%INSERT%','%TABLE%','%FIELD%','%DATA%','%COMMENT%'],
            '%INSERT% INTO %TABLE% (%FIELD%) VALUES (%DATA%) %COMMENT%'
        ],
        'insertAll'=>[
            ['%INSERT%','%TABLE%','%FIELD%','%DATA%','%COMMENT%'],
            '%INSERT% INTO %TABLE% (%FIELD%) %DATA% %COMMENT%'
        ],
        'update'=>[
            ['%TABLE%','%SET%','%JOIN%','%WHERE%','%ORDER%','%LIMIT%','%LOCK%','%COMMENT%'],
            'UPDATE %TABLE% SET %SET% %JOIN% %WHERE% %ORDER%%LIMIT% %LOCK%%COMMENT%'
        ]
    ];

    /*sql选项*/
    protected $options = [];

    /*bind数据*/
    protected $bind = [];

    /*数据库配置*/
    protected $config = [];

    /*当前执行使用的数据库配置*/
    protected $currDatabase = 0;

    /*初始化*/
    protected function __construct($config){
       /*初始化initOptions*/
       $this->initOptions(); 
       $this->config[0] = $config; //设置默认数据库配置
    }

    /*本例单例*/
    public static function getDb($config){
        if(is_null(self::$dbInstance)){
            self::$dbInstance = new self($config);
        }
        return self::$dbInstance;
    }

    /*设置数据库*/
    public function setConfig($config,$configSign = 1){
        $this->config[$configSign] = $config;
        return $this;
    }

    /*单独设置此次表前缀*/
    public function pre($pre){
        $this->options['prefix'] = $pre;
    }
    
    /*选择执行时的数据库设置*/
    public function database($sign=0){
        $this->currDatabase = $sign;
        return $this;
    }

    /*初始化创建sql的$options*/

    protected function initOptions(){
        $this->options = array(
            'distinct'=>null,
            'field'=>null,
            'table'=>null,
            'force'=>null,
            'join'=>null,
            'where'=>[],
            'group'=>null,
            'having'=>[],
            'union'=>null,
            'order'=>null,
            'limit'=>null,
            'lock'=>null,
            'comment'=>null,
            'prefix'=>null,
        );
    }

    /*返回数据库连接句柄*/
    protected function connect($config = []){
        $name = md5(serialize($config));
        /*需要重新连接*/
        if(!isset(self::$instance[$name])){
            /*1.根据数据库类型选择驱动*/
            $class = __NAMESPACE__.'\\db\\'.ucwords($config['type']);
            /*2.验证驱动是否存在*/

            /*3.生成连接实例*/
            self::$instance[$name] = new $class($config);     
        }
        return self::$instance[$name];
    }

    /*创建SQL语句*/
    public function buildSql($type){
        /*判断生成sql语句类型*/
        $sql = self::$SQL[$type];

        /*设置表前缀*/
        if(is_null($this->options['prefix'])){
            $this->options['prefix'] = $this->config[$this->currDatabase]['prefix'];
        }

        /*获取每个部分的sql string*/
        foreach ($sql[0] as $val) {
           $getFunc = 'parse'.ucfirst(strtolower(trim($val,'%')));
           $sql[2][] = $this->$getFunc();
        }

        /*生成最终的 sql 语句*/
        $sql = str_replace($sql[0],$sql[2],$sql[1]);

        /*清空options为下次创建作准备*/
        $this->initOptions();
        return '('.$sql.')';
    } 

    /*设置where*/
    public function where($field,$op = null,$condition = null,$next='AND'){
        /*数组格式批量设置*/
        if(is_array($field)){
            $whereArr = $this->setWhere($field);//如果是数组设置，第一层的数组作为where的最外层    
        /*当个赋值*/    
        } else {
            $whereArr = $this->setWhere([$field,$op,$condition,$next]);
        }
        $this->options['where'] = array_merge($this->options['where'],$whereArr);
        return $this;
    }

    /*设置where辅助*/
    protected function setWhere($where){
        $whereArr = [];
        if(is_array($where[0])){//如果是两层以上数组
            foreach ($where as $key => $val) {
               if(is_array($val[0])){
                    $whereArr[] = $this->setWhere($val);
                    $whereArr[] = is_array($val[count($val)-1]) ? 'AND':'OR';
               }else{
                    $whereArr = array_merge($whereArr,$this->setWhere($val));
               }
            }
        } else {//一层数组直接赋值
            $where = array_pad($where,4,null);
            $field = $where[0];
            /*设置默认等于号*/
            if(is_null($where[2]) && is_null($where[3])){
                $op = '=';
                $condition = $where[1];
                $next = 'AND';
            /*通常情况*/
            } else {
                $op = $where[1];
                $condition = $where[2];
                $next = $where[3] ? : 'AND';
            }
            $whereArr[0] = [$field,$op,$condition];  
            $whereArr[1] = $next;     
        }
        return $whereArr;
    }
    /*parseWhere*/
    protected function parseWhere(){
        if(!empty($this->options['where'])){
            return ' WHERE ' . $this->parseWhereCore($this->options['where']);
        }else{
            return '';
        }
    }
    protected function parseWhereCore($where = null){
        /*第一次获取options的where选项*/
        $whereStr = '';
        if(is_array($where[0])){//判断第一个元素是否是数组，如果是数组则是内层
            array_pop($where);
            foreach ($where as $key => $val) {
                if($key%2 == 0){//偶数的时候代表的是条件
                    $whereStr .= '('.$this->parseWhereCore($val).')';
                }else{//奇数的时候代表的是连接符
                    $whereStr .= ' '.$val.' ';
                }  
            }
        } else {
            /*1.处理字段*/
            $fieldStr = $this->parseFieldCore($where[0]);    

            /*2.处理逻辑符号与条件*/
            switch ($where[1]) {
                case 'in':     
                case 'IN':     
                case 'not in':     
                case 'NOT IN':
                    $whereStr = $fieldStr . ' ' . $where[1] . ' ' .'('.$this->bind($where[2]).')';
                    break;
                default:
                    $whereStr = $fieldStr . ' ' . $where[1] . ' ' . $this->bind($where[2]);
                    break;
            }
             ;
            
        }
        return $whereStr;
    }

    /*处理参数绑定*/
    protected function bind($value){
        $count = count($this->bind);
        $this->bind[':wythe'.($count+1)] = $value;
        return ':wythe'.($count+1);
    }

    /*设置distinct*/
    public function distinct($distinct){
        if($distinct){
            $this->options['distinct'] = 'DISTINCT';
        } else {
            $this->options['distinct'] = '';
        }
        return $this;
    }

    /*parseDistinct*/
    protected function parseDistinct(){
        if(is_null($this->options['distinct'])){
            return '';
        }else{
            return ' DISTINCT ';
        }
    }
    
    /*设置table*/
    public function table($table){
        $this->options['table'] = $table;
        return $this;
    }

    /*parseTable*/
    protected function parseTable(){
       if(strpos('(',$this->options['table']) !== false){
        return $this->options['table'];
       }else{
        return $this->parseMultiField($this->options['table'],$this->options['prefix']); //表名加前缀
       }
    }

    /*设置字段*/
    public function field($field){
        $this->options['field'] = $field;
        return $this;
    }

    /*parseField*/
    protected function parseField(){
        if(is_null($this->options['field'])){
            return '*';
        }else{
            return $this->parseMultiField($this->options['field']);
        }
    }

    /*设置force*/
    public function force($force){
        $this->options['force'] = $force;
        return $this;
    }
    /*parseForce*/
    protected function parseForce(){
        if(is_null($this->options['force'])){
            return '';
        }else{
            return $this->options['force'];
        }
    }

    /*设置join*/
    public function join($table,$condition = null,$type = 'INNER'){
        if(is_array($table)){
           $this->options['join']= array_merge($this->options['join'],$table);
        } else {
            $this->options['join'][] = [$table,$condition,$type];
        }
        return $this;
    }

    /*parseJoin*/
    protected function parseJoin(){
        $join = $this->options['join'];
        if(is_null($join)){
            return '';
        }else{
            $joinStr = '';
            foreach ($join as $key => $val) {
               $table = $this->parseFieldCore(trim($val[0]),$this->options['prefix']);//表名加前缀
               $arr = explode('=',$val[1]);
               $condition = $this->parseFieldCore(trim(array_shift($arr))) . ' = ' . $this->parseFieldCore(trim(array_pop($arr)));
               $type = $val[2];
               $joinStr .=  ' '.$type . ' JOIN '. $table . ' ON '. $condition . ',';
            }            
        }
        return rtrim($joinStr,',');
    }

    /*设置order*/
    public function order($order){
        $this->options['order'] = $order;
        return $this;
    }
    protected function parseOrder(){
        if(is_null($this->options['order'])){
            return '';
        }else{
            return $this->parseFieldCore($this->options['order']);
        }
    }

    /*设置group*/
    public function group($group){
        $this->options['group'] = $group;
        return $this;
    }
    protected function parseGroup(){
        if(is_null($this->options['group'])){
            return '';
        }else{
            return $this->parseMultiField($this->options['group']);
        }
    }

    /*设置having*/
    public function having($field,$op = null,$condition = null,$next='AND'){
        /*数组格式批量设置*/
        if(is_array($field)){
            $whereArr = $this->setWhere($field);//如果是数组设置，第一层的数组作为where的最外层    
        /*当个赋值*/    
        } else {
            $whereArr = $this->setWhere([$field,$op,$condition,$next]);
        }
        $this->options['having'] = array_merge($this->options['having'],$whereArr);
        return $this;
    }
    protected function parseHaving(){
        $having = $this->options['having'];
        if(empty($having)){
            return '';
        }else{
            return $this->parseWhereCore($having);
        }
    }

    /*设置limit*/
    public function limit($limit){
        $this->options['limit'] = $limit;
    }
    protected function parseLimit(){
        $limit = $this->options['limit'];
        if(is_null($limit)){
            return '';
        }else{
            return ' LIMIT ' . $limit . ' ';
        }
    }

    /*设置注释*/
    public function comment($comment){
        $this->options['comment'] = $comment;
        return $this;
    }
    protected function parseComment(){
        return is_null($this->options['comment']) ? '' : '/*'.$this->options['comment'] . '*/';
    }

    /*设置lock，锁机制*/
    public function lock($lock){
        $this->options['lock'] = $lock;
    }
    protected function parseLock(){
        $lock = $this->options['lock'];
        if(is_bool($lock)){
            return $lock ? ' FOR UPDATE ' : '';
        } elseif(is_string($lock)){
            return ' ' . trim($lock) . '';
        } else{
            return '';
        }
    }

    /*设置union*/
    public function union($union){
        $this->options['union'] = $union;
        return $this;
    }
    protected function parseUnion(){
        return '';
    }


    /*处理字段核心字符处理函数 tablename as alias,tablename.ab alias*/
    private function parseFieldCore($fieldStr=null,$pre=''){
        if(is_null($fieldStr)){
            return '';
        }
        $field = '';//字段
        $alias = '';//别名
        /*提取别名*/
        if(strpos($fieldStr,' ')){
            $arr = explode(' ',$fieldStr);
            $field = array_shift($arr);//头部是字段名
            $alias = array_pop($arr);//最后是别名
        }else{
            $field = $fieldStr;
            $alias = '';
        }
        /*字段加上反引号*/
        if(strpos($field,'.')){
            $fieldArr = explode('.',$field);
            $field = '';
            foreach ($fieldArr as $key=>$val) {
                if($key == 0){
                    $field .= '`'.$pre.$val.'`'.'.';//第一个是表名，加上前缀
                }elseif($val == '*'){
                    $field .= $val . '.';
                }else{
                    $field .= '`'.$val.'`'.'.';
                }
            }
            $field = rtrim($field,'.');
        }else{
            if($field != '*'){
               $field = '`'.$pre.$field.'`'; 
            }    
        }
        /*别名加上反引号,返回处理后的字符串*/
        if($alias != ''){
            $alias = '`'.$alias.'`';
            $str = $field .' AS '.$alias;
        }else{
            $str = $field;
        }
        return $str;
    }

    /*处理多个字段*/
    private function parseMultiField($field,$pre=''){ 
        $field = explode(',',$field);
        $fieldStr = '';
        foreach ($field as $val) {
            $fieldStr .= $this->parseFieldCore($val,$pre) . ',';
        }
        return rtrim($fieldStr,',');
    }

    /*select*/
    public function select(){
        $sql = $this->buildSql('select');
        return $this->query($sql);
    }

    /*执行sql*/
    public function query($sql){
        return $this->dbExecute($sql,'query');
    }

    /*执行sql*/
    public function execute($sql){
        return $this->dbExecute($sql,'execute');
    }

    /*真正执行*/
    protected function dbExecute($sql,$type){
        $connect = $this->connect($this->config[$this->currDatabase]);
        $result = $connect->query($sql,$this->bind,$type);
        $this->bind = [];//重置bind
        return $result;        
    }
}