<?php
namespace wythe\cache;

abstract class Driver{
	protected $handler = null;
	protected $options = [];
	protected $tag;


	/*判读缓存是否存在*/
	abstract public function has($name);


	/*读取缓存*/
	abstract public function get($name,$default = false);

	/*写入缓存*/
	abstract public function set($name,$value,$expire = null);

	/*自增缓存*/
	abstract public function dec($name,$step = 1);

	/*删除缓存*/
	abstract public function rm($name);


	/*清楚缓存*/
	abstract public function clear($tag = null);


	/*获取实际的缓存标识*/
	protected function getCacheKey($name){
		return $this->options['prefix'] . $name;
	}

	/*读取缓存并删除*/
	public function pull($name){
		$result = $this->get($name,false);

		if($result){
			$this->rm($name);
			return $result;
		} else {
			return ;
		}
	}


	/*如果不存在则写入缓存*/
	public function remember($name,$value,$expire = null){
		if(!$this->has($name)){
			$time = time();
			while($time + 5 > time() && $this->has($name.'_lock')){
				usleep(2000000);
			}

			try{
				$this->set($name . '_lock',true);
				if($value instanceof \Closure){
					$value = call_user_func($value);
				}

				$this->set($name,$value,$expire);

				/*解锁*/
				$this->rm($name . '_lock');
			} catch (\Exception $e){
				$this->rm($name . '_lock');
				echo 'error';
			}
		} else {
			$value = $this->get($name);
		}

		return $value;
	}


	/*缓存标签*/
	public function tag($name,$keys = null,$overlay = false){
		if(is_null($name)){

		} elseif (is_null($keys)){
			$this->tag = $name;
		} else {
			$key = 'tag_' . md5($name);
			if(is_string($keys)){
				$keys = explode(',',$keys);
			}

			$keys = array_map([$this,'getCacheKey'],$keys);

			if($overlay){
				$value = $keys;
			} else {
				$value = array_unique(array_merge($this->getItem($name)),$keys);
			}

			$this->set($key,implode(',',$value),0);
		}

		return $this;
	}

	/*更新标签*/
	protected function setTagItem($name){
		if($this->tag){
			$key = 'tag_' . md5($this->tag);
			$this->tag = null;
			if($this->has($key)){
				$value = explode(',',$this->get($key));
				$value[] = $name;
				$value = implode(',',array_unique($value));
			} else {
				$value = $name;
			}

			$this->set($key,$value,0);
		}
	}

	/*获取标签包含的缓存标识*/
	protected function getTagItem($tag){
		$key = 'tag_' . md5($tag);
		$value = $this->get($key);
		if($value){
			return array_filter(explode(',',$value));
		} else {
			return [];
		}
	}

	/*返回句柄对象，可执行其它高级方法*/
	public function handler(){
		return $this->handler;
	}
}