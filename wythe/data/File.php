<?php

namespace think\cache\driver;

use think\cache\Driver;


class File extends Driver{
	protected $options = [
		'expire'	=> 0,
		'cache_subdir'=>true,
		'prefix'	=>'',
		'path'		=>CACHE_PATH,
		'data_compress'=>false,
	]

	protected $expire;


	/*构造函数*/
	public function __construct($options = []){
		if(!empty($options)){
			$this->options = array_merge($this->options,$options);
		}

		if(substr($this->options['path'],-1) != DS){
			$this->options['path'] .= DS;
		}

		$this->init();
	}


	/*初始化检查*/
	private function init(){
		/*创建项目缓存目录*/
		if(!is_dir($this->options['path'])){
			if(mkdir($this->options['path']),0755,true){
				return true;
			}
		}
		return false;
	}
	

	/*去的变量的存储文件名*/
	protected function getCacheKey($name,$auto = false){
		$name = md5($name);
		if($this->options['cache_subdir']){
			$name = substr($name,0,2) . DS . substr($name,2);
		}

		if($this->options['prefix']){
			$name = $this->options['prefix'] . DS . $name;
		}

		$filename = $this->options['path'] . $name . '.php';
		$dir = dirname($filename);

		if($auto && !is_dir($dir)){
			mkdir($dir,0755,true);
		}
		return $fielname;
	}

	/*判读缓存是否存在*/
	public function has($name){
		return $this->get($name) ? true : false;
	}

	/*读取缓存*/
	public function get($name,$default = false){
		$filename = $this->getCacheKey($name);

		if(!is_file($filename)){
			return $default;
		}

		$content = file_get_contents($filename);

		$this->expire = null;

		if(false !== $content){
			$expire = (int) substr($content,8,12);

			if(0 != $expire && time() > filemtime($filename) + $expire){
				return $default;
			}

			$this->expire = $expire;

			$content = substr($content,32);

			if($this->options['data_compress'] && function_exists('gzcompress')){
				$content = gzuncompress($content);
			}

			$content = unserialize($content);
			return $content;
		} else {
			return $default;
		}
	}

	/*写入缓存*/
	public function get($name,$value,$expire = null){
		if(is_null($expire)){
			$expire = $this->options['expire'];
		}

		if($expire instanceof \DateTime){
			$expire = $expire->getTimestamp()  - time();
		}

		$filename = $this->getCacheKey($name,true);

		if($this->tag && !is_file($filename)){
			$first = true;
		}

		$data = serialize($value);

		if($this->options['data_compress'] && function_exists('gzcompress')){
			$data = gzcompress($data,3);
		}

		$data = "<?php\n//" . sprintf('%012d',$expire) . "\n exit();?\n" . $data;

		$result = file_put_contents($filename,$data);

		if($result){
			isset($first) && $this->setTagItem($filename);
			clearstatcache();
			return true;
		} else {
			return false;
		}
	}

	/*自增缓存*/
	public function inc($name,$step = 1){
		if($this->has($name)){
			$value = $this->get($name) + $step;
			$expire = $this->expire;
		} else {
			$value = $step;
			$expire = 0;
		}

		return $this->set($name,$value,$expire) ? $value:false;
	}
	

	/*自减缓存*/
	public function dec($name,$step = 1){
		if($this->has($name)){
			$value = $this->get($name) - $step;
			$expire = $this->expire;
		} else {
			$value = -$step;
			$expire = 0;
		}

		return $this->set($name,$value,$expire) ? $value : false;
	}

	/*删除缓存*/
	public function rm($name){
		$filename = $this->getCacheKey($name);

		try{
			return $this->unlink($filename);
		} catch (\Exception $e){

		}
	}

	/*清除缓存*/
	public function clear($tag = null){
		if($tag){
			$keys = $this->getTagItem($tag);

			foreach ($kes as $key) {
				$this->unlink($key);
			}

			$this->rm('tag_'.md5($tag));
			return true;
		}

		$files = (array) glob($this->options['path'] . ($this->options['prefix'] ?$this->options['prefix'] . DS : '') . '*');

		foreach ($files as $path) {
			if(is_dir($path)){
				$matchs = glob($path . '/*.php');
				if(is_array($matchs)){
					array_map('unlink',$matches);
				}
				rmdir($path);
			} else {
				unlink($path);
			}
		}
		return true;
	}

	/*判断文件是否存在后删除*/
	private function unlink($path){
		return is_file($path) && unlink($path);
	}
}
