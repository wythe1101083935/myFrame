<?php
namespace plugin;

abstract class Model implements \JsonSerializable, \ArrayAccess
{
	/*数据库查询对象pool*/
	protected static $links = [];

	/*数据库配置*/
	protected $connection = [];

	/*父关联模型对象*/
	protected $parent;

	/*数据库查询对象*/
	protected $query;

	/*当前模型名称*/
	protected $name;

	/*数据表名称*/
	protected $table;

	/*当前类名称*/
	protected $class;

	/*回调事件*/
	private static $event = [];

	/*错误信息*/
	protected $error;

	/*字段验证规则*/
	protected $validate;

	/*数据表主键 复合主键使用数组定义 不设置则自动获取*/
	protected $pk;

	/*数据表字段信息*/
	protected $field = [];

	/*数据排除字段*/
	protected $except = [];

	/*数据废弃字段*/
	protected $disuse = [];

	/*只读字段*/
	protected $readonly = [];

	/*显示属性*/
	protected $visible = [];

	/*隐藏舒心*/
	protected $hidden = [];

	/*追加属性*/
	protected $append = [];

	/*数据信息*/
	protected $data = [];

	/*原始数据*/
	protected $origin = [];

	/*关联模型*/
	protected $relation = [];

	/*保存自动完成列表*/
	protected $auto = [];

	/*新增自动完成列表*/
	protected $insert = [];

	/*更新自动完成列表*/
	protected $update = [];

	/*是否需要自动写入时间戳，如果设置为字符串，则表示时间字段的类型*/
	protected $autoWriteTimestamp;

	/*创建时间字段*/
	protected $createTime = 'create_time';

	/*更新时间字段*/
	protected $updateTime = 'update_time';

	/*时间字段取出后的默认时间格式*/
	protected $dateFormat;

	/*字段类型或者格式转换*/
	protected $type = [];

	/*是否为更新数据*/
	protected $isUpdate = false;

	/*更新条件*/
	protected $updateWhere;

	/*验证失败是否抛出异常*/
	protected $failException = false;

	/*全局查询范围*/
	protected $useGlobalScope = true;

	/*是否采用批量验证*/
	protected $batchValidate = false;

	/*查询数据集对象*/
	protected $resultSetType;

	/*关联自动写入*/
	protected $relationWrite;


	/*实例对象存储*/
	protected static $initialized = [];

	public function __construct($data = []){
		if(is_object($data)){
			
			$this->data = get_object_vars($data);
			//$this->data = (array)$data;
		}else{
			$this->data = $data;
		}
		if($this->disuse){
			foreach((array)$this->disuse as $key){
				if(array_key_exists($key,$this->data)){
					unset($this->data[$key]);
				}
			}
		}

		/*记录原始数据*/
		$this->origin = $this->data;

		/*当前类名*/
		$this->class = get_called_class();

		if(empty($this->name)){
			/*当前模型名*/
			$name = str_replace('\\','/',$this->class);

			$this->name = basename($name);

			if(Config::get('class_suffix')){
				$suffix = basename(dirname($name));
				$this->name = substr($this->name,0,-strlen($suffix));
			}
		}

		/*自动写入时间戳*/
		if(is_null($this->autoWriteTimestamp)){
			$this->autoWriteTimestamp = $this->getQuery()->getConfig('auto_timestamp');
		}
		/*设置时间戳格式*/
		if(is_null($this->dateFormat)){
			$this->dateFormat = $this->getQuery()->getConfig('datetime_format');
		}

		if(is_null($this->resultSetType)){
			$this->resultSetType = $this->getQuery()->getConfig('resultset_type');
		}

		/*初始化*/
		$this->initalize();
	}

	/*初始化模型*/
	protected function initialize(){
		$class = get_class($this);
		if(!isset(static::$initialized[$class])){
			static::$initialized[$class] = true;
			static::init();
		}
	}

	/*初始化处理*/
	protected static function init(){

	}

	/*设置父关联对象*/
	public function setParent($model){
		$this->parent = $model;
		return $this;
	}

	/*获取父关联对象*/
	public function getParent(){
		return $this->parent;
	}

	/*设置数据对象值*/
	public function data($data,$value = null){
		if(is_string($data)){
			$this->data[$data] = $value;
		}else{
			$this->data = [];
			if(is_object($data)){
				$data = get_object_vars($data);
			}
			if(true === $value){
				foreach ($data as $key => $value) {
					$this->setAttr($key,$value,$data);
				}
			}else{
				$this->data = $data;
			}
		}
		return $data;
	}

	/*获取对象原始数据，如果不存在指定字段返回false*/
	public function getData($name = null){
		if(is_null($name)){
			return $this->data;
		}elseif(array_key_exists($name,$this->data)){
			return $this->data[$name];
		}elseif(array_key_exists($name,$this->relation)){
			return $this->relation[$name];
		}else{
			echo 'no data';
		}
	}

	/*是否需要自动写入时间戳*/
	public function isAutoWriteTimestamp($auto){
		$this->autoWriteTimestamp = $auto;
		return $this;
	}

	/*更新是否强制写入数据 而不做比较*/
	public function force($force = true){
		$this->force = $force;
		return $this;
	}

	/*修改器 设置数据对象值*/
	public function setAttr($name,$value,$data=[]){
		if(is_null($value) && $this->autoWriteTimestamp && in_array($name,[$this->createTime,$this->updateTime])){
			$value =$this->autoWriteTimestamp($name);
		}else{
			$method = 'set' . Loader::parseName($name,1) . 'Attr';

			if(method_exists($this,$method)){
				$value = $this->$method($value,array_merge($this->data,$data),$this->relation);
			}elseif(isset($this->type[$name])){
				$value = $this->writeTransform($value,$this->type[$name]);
			}
		}
		$this->data[$name] = $value;
		return $this;
	}

	/*获取当前模型的关联模型数据*/
	public function getRelation($name = null){
		if(is_null($name)){
			return $this->relation;
		}elseif(array_key_exists($name,$this->realtion)){
			return $this->relation[$name];
		}else{
			return;
		}
	}


}