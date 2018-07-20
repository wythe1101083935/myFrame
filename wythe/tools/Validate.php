<?php
namespace wythe\tools;

class Validate{
	/*实例*/
	protected static $instance;

	/*自定义的验证类型*/
	protected static $type = [];

	/*验证类型别名*/
	protected $alias = [
		'>'=>'get',
		'>='=>'egt',
		'<'=>'lt',
		'<='=>'elt',
		'='=>'eq',
		'same'=>'eq'
	];	

	/*当前验证的规则*/
	protected $rule = [];

	/*验证提示信息*/
	protected $message = [];

	/*验证字段你描述*/
	protected $field = [];

	/*验证规则默认提示信息*/
	protected static $typeMsg = [
		'require'			=>	':attribute require',
		'number'			=>	':attribute must be numeric',
		'integer'			=>	':attribute must be integer',
		'float'				=>	':attribute must be flat',
        'boolean'     => ':attribute must be bool',
        'email'       => ':attribute not a valid email address',
        'array'       => ':attribute must be a array',
        'accepted'    => ':attribute must be yes,on or 1',
        'date'        => ':attribute not a valid datetime',
        'file'        => ':attribute not a valid file',
        'image'       => ':attribute not a valid image',
        'alpha'       => ':attribute must be alpha',
        'alphaNum'    => ':attribute must be alpha-numeric',
        'alphaDash'   => ':attribute must be alpha-numeric, dash, underscore',
        'activeUrl'   => ':attribute not a valid domain or ip',
        'chs'         => ':attribute must be chinese',
        'chsAlpha'    => ':attribute must be chinese or alpha',
        'chsAlphaNum' => ':attribute must be chinese,alpha-numeric',
        'chsDash'     => ':attribute must be chinese,alpha-numeric,underscore, dash',
        'url'         => ':attribute not a valid url',
        'ip'          => ':attribute not a valid ip',
        'dateFormat'  => ':attribute must be dateFormat of :rule',
        'in'          => ':attribute must be in :rule',
        'notIn'       => ':attribute be notin :rule',
        'between'     => ':attribute must between :1 - :2',
        'notBetween'  => ':attribute not between :1 - :2',
        'length'      => 'size of :attribute must be :rule',
        'max'         => 'max size of :attribute must be :rule',
        'min'         => 'min size of :attribute must be :rule',
        'after'       => ':attribute cannot be less than :rule',
        'before'      => ':attribute cannot exceed :rule',
        'expire'      => ':attribute not within :rule',
        'allowIp'     => 'access IP is not allowed',
        'denyIp'      => 'access IP denied',
        'confirm'     => ':attribute out of accord with :2',
        'different'   => ':attribute cannot be same with :2',
        'egt'         => ':attribute must greater than or equal :rule',
        'gt'          => ':attribute must greater than :rule',
        'elt'         => ':attribute must less than or equal :rule',
        'lt'          => ':attribute must less than :rule',
        'eq'          => ':attribute must equal :rule',
        'unique'      => ':attribute has exists',
        'regex'       => ':attribute not conform to the rules',
        'method'      => 'invalid Request method',
        'token'       => 'invalid token',
        'fileSize'    => 'filesize not match',
        'fileExt'     => 'extensions to upload is not allowed',
        'fileMime'    => 'mimetype to upload is not allowed',
	];

	/*当前验证场景*/
	protected $currentScene = null;

	/*正则表达式  regex = ['zip'=>'\d{6}',] */ 
	protected $regex = [];

	/*验证场景 scene = ['edit'=>'name1,name2,...',] */
	protected $scene = [];

	/*验证失败错误信息*/
	protected $error = [];

	/*批量验证*/
	protected $batch = false;

	/*构造函数*/
	public function __construct(array $rules = [],$message = [],$field = []){
		$this->rule = $rules + $this->rule;
		$this->message = $message + $this->message;
		$this->field = $field + $this->field;
	}

	/*实例化验证*/
	public static function make($rules = [],$message = [],$field = []){
		if(is_null(self::$instance)){
			self::$instance = new self($rules,$message,$field);
		}
		return self::$instance;
	}

	/*添加字段验证规则*/
	public function rule($name,$rule = ''){
		if(is_array($name)){
			$this->rule = $name + $this->rule;
		} else {
			$this->rule[$name] = $rule;
		}
	}

	/*注册验证规则*/
	public static function extend($type,$callback = null){
		if(is_array($type)){
			self::$type = array_merge($this->type,$type);
		} else {
			self::$type[$type] = $callback;
		}
	}

	/*设置验证规则的默认提示信息*/
	public static function setTypeMsg($type,$msg = null){
		if(is_array($type)){
			self::$typeMsg = array_merge(self::$typeMsg,$type);
		} else {
			self::$typeMsg = $msg;
		}
	}


    /*设置提示信息*/
    public function message($name, $message = '')
    {
        if (is_array($name)) {
            $this->message = array_merge($this->message, $name);
        } else {
            $this->message[$name] = $message;
        }
        return $this;
    }

    /*设置验证场景*/
    public function scene($name,$fields = null){
    	if(is_array($name)){
    		$this->scene = array_merge($this->scene,$name);
    	}
    	if(is_null($fields)){
    		/*设置当前场景*/
    		$this->currentScene = $name;
    	}else{
    		/*设置验证场景*/
    		$this->scene[$name] = $fields;
    	}
    	return $this;
    }

    /*判读是否存在某个验证场景*/
    public function hasScene($name){
    	return isset($this->scene[$name]);
    }

    /*设置批量验证*/
    public function batch($batch = true){
    	$this->batch = $batch;
    	return $this;
    }

    /*数据自动验证*/
    public function check($data,$rules = [],$scene = ''){
    	$this->error = [];

    	if(empty($rules)){
    		/*读取验证规则*/
    		$rules = $this->rule;
    	}

    	/*分析验证规则*/
    	$scene = $this->getScene($scene);

    	if(is_array($scene)){
    		$change = [];

    		$array = [];

    		foreach ($scene as $k => $val) {
    			if(is_numeric($k)){
    				$array[] = $val;
    			} else {
    				$array[] = $k;
    				$change[$k] = $val;
    			}
    		}
    	}

    	foreach ($rules as $key => $item) {
    		if(is_numeric($key)){
    			$key = $item [0];
    			$rule = $item[1];

    			if(isset($item[2])){
    				$msg = is_string($item[2] ? explode('|',$item[2]) :$item[2]);
    			} else {
    				$msg = [];
    			}
    		} else {
    			$rule = $item;
    			$msg = [];
    		}

    		if(strpos($key,'|')){
    			list($key,$title) = explode('|',$key);
    		} else {
    			$title = isset($this->field[$key]) ? $this->field[$key] : $key;
    		}

    		/*场景检测*/
    		if(!empty($scene)){
    			if($scene instanceof \Closure && !call_user_func_array($scene,[$key,$data]) ){
    				continue;
    			} elseif (is_array($scene)){
    				if(!in_array($key,$array)){
    					
    				}elseif(isset($change[$key])){
                        /*重载某个验证规则*/
                        $rule = $cahnge[$key];
                    }
    			}
    		}

            /*获取数据 支持二维数组*/
            $value = $this->getDataValue($data,$key);

            /*字段验证*/
            if($rule instanceof \Closure){
                $result = call_user_func_array($rule,[$value,$data]);
            } else {
                $result = $this->checkItem($key,$value,$rule,$data,$title,$msg);
            }

            if(true !== $result){
                /*没有返回true表示验证失败*/
                if(!empty($this->batch)){
                    /*批量验证*/
                    if(is_array($result)){
                        $this->error = array_merge($this->error,$result);
                    } else {
                        $this->error[$key] = $result;
                    }
                } else {
                    $this->error = $result;
                    return false;
                }
            }
    	}
        return !empty($this->error) ? false : true;
    }

    /*根据验证规则验证数据*/
    protected function checkRule($value,$rules){
        if($rules instanceof \Closure){
            return call_user_func_array($rules,[$value]);
        } elseif(is_string($rules)){
            $rules = explode('|',$rules);
        }

        foreach ($rules as $key => $rule) {
            if($rule instanceof \Closure){
                $result = call_user_func_array($rule,[$value]);
            } else {
                list($type,$rule) = $this->getValidateType($key,$rule);

                $callback = isset(self::$type[$type]) ? self::$type[$type] : [$this,$type];
                $result = call_user_func_array($callback,[$value,$rule]);
            }
        }

        if(true !== $result){
            return $result;
        }
        return true;
    }

    /*验证单个字段规则*/
    protected function checkItem($field,$value,$rules,$data,$title = '',$msg = []){
        if(is_string($rules)){
            $rules = explode('|',$rules);
        }

        $i = 0;

        foreach ($rules as $key => $rule) {
            if($rule instanceof \Closure){
                $result = call_user_func_array($rule,[$value,$data]);
                $info = is_numeric($key) ? '' : $key;
            } else {
                /*判断验证类型*/
                list($type,$rule,$info) = $this->getValidateType($key,$rule);

                if(0 === strpos($info,'require') || (!is_null($value) && '' !== $value)){
                    $callback = isset(self::$type[$type]) ? self::$type[$type] : [$this,$type];

                    /*验证数据*/
                    $result = call_user_func_array($callback,[$value,$rule,$data,$field,$title]);
                } else {
                    $result = true;
                }

            }

            if(false === $result){
                /*验证失败 返回错误信息*/
                if(isset($msg[$i])){
                    $message = $msg[$i];
                    if(is_string($message) && strpos($message,'{%') === 0){
                        $message = Lang::get(substr($message,2,-1));
                    }
                } else {
                    $message = $this->getRuleMsg($field,$title,$info,$rule);
                }
                return $message;
            } elseif (true !== $result){
                if(is_string($result) && false !== strpos($result,':')){
                    $result = str_replace([':attribute',':rule'],[$title,(string) $rule],$result);
                }
                return $result;
            }
            $i++;
        }
        return $result;
    }

    /*获取当前验证类型及规则*/
    protected function getValidateType($key,$rule){
        /*判断类型*/
        if(!is_numeric($key)){
            return [$key,$rule,$key];
        }

        if(strpos($rule,':')){
            list($type,$rule) = explode(':',$rule,2);
            if(isset($this->alias[$type])){
                $type = $this->alias[$type];
            }
            $info = $type;
        } elseif (method_exists($this, $rule)){
            $type = $rule;
            $info = $rule;
            $rule = '';
        } else {
            $type = 'is';
            $info = $rule;
        }
        return [$type,$rule,$info];
    }
}