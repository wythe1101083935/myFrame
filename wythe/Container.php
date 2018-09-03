<?php
/**
 +----------------------------------------------------------
 * Ioc容器
 +----------------------------------------------------------
 * CODE:
 +----------------------------------------------------------
 * TIME:2018-07-31 15:55:24
 +----------------------------------------------------------
 * author:wythe(汪志虹)
 +----------------------------------------------------------
 */
namespace Wythe;
use \Closure;
use \ReflectionParameter;
use \ReflectionClass;

class Container{

	/*本例的单态实例*/
	protected static $instance;

	/*所有的instances*/
	protected $instances = [];

	/*所有绑定的要和创建的类名*/
	protected $bindings = [];

	/*参数栈--递归创建对象的时候，每一个对象都需要先放进去参数*/
	protected $with = [];

	public function bind($abstract,$concrete = null,$shared = false){
		if(is_null($concrete)){
			$concrete = $abstract;
		}
		$this->bindings[$abstract] = compact('concrete', 'shared');
	}

	public function make($abstract,$parameters = []){
		/*如果已经存在实例*/
		if(isset($this->instances[$abstract])){
			return $this->instances[$abstract];
		/*创建实例*/
		}else{
			$this->with[] = $parameters;
			$concrete = $this->bindings[$abstract]['concrete'];
			/*绑定的时候传入了参数*/
			if($concrete instanceof Closure){
				$object = $concrete($this,$parameters);
			/*如果不是闭包，实例化这个类，自动注入依赖*/
			}else{
				$object = $this->build($concrete);
			}
			/*如果是单态，写入instances*/
			if($this->isShared($abstract)){
				$this->instances[$abstract] = $object;
			}
			array_pop($this->with);
			return $object;
		}
	}

	protected function build($concrete){
		$reflector = new ReflectionClass($concrete);
		$constructor = $reflector->getConstructor();
		if(is_null($constructor)){
			$object = new $concrete;
		/*查看这个类的依赖*/
		}else{
			$dependencies = $constructor->getParameters();
			$instances = $this->resolveDependencies($dependencies);
			$object = $reflector->newInstanceArgs($instances);
		}	
		return $object;	
	}

	protected function resolveDependencies(array $dependencies){
		$results = [];
		foreach ($dependencies as $dependency) {
			/*如果存在手动提交数据*/
			if($this->hasParameterOverride($dependency)){
				$results[] = $this->getLastParameterOverride()[$dependency->name];;
			/*如果参数不是一个对象*/
			}elseif(is_null($dependency->getClass())){
			   /*参数有默认值*/
		       if ($dependency->isDefaultValueAvailable()) {
		           $results[] =  $dependency->getDefaultValue();
		        }else{

		         //这里应该报个错误
		        }
		    /*参数是一个对象*/
			}else{
				/*如果是一个类，则需要绑定这个类，并且实例化这个类作为参数*/
				$results[] = $this->build($dependency->getClass()->name);
				
			}
		}
		return $results;
	}

	protected function hasParameterOverride($dependency){
		return array_key_exists($dependency->name,$this->getLastParameterOverride());
	}

	protected function getLastParameterOverride(){
		return count($this->with) ? end($this->with) : [];
	}
    public function isShared($abstract)
    {
        return isset($this->instances[$abstract]) ||
              (isset($this->bindings[$abstract]['shared']) &&
               $this->bindings[$abstract]['shared'] === true);
    }
}
