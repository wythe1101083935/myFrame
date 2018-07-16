<?php
/**
 +----------------------------------------------------------
 * 文件缓存驱动
 +----------------------------------------------------------
 * CODE:
 +----------------------------------------------------------
 * TIME:2018-07-12 10:32:21
 +----------------------------------------------------------
 * author:wythe(汪志虹)
 +----------------------------------------------------------
 */
namespace wythe\data\cache;
class File{
	protected $config = [
        'expire'        => 0,
        'cache_subdir'  => true, //是否分割目录
        'prefix'        => 'wythe',//缓存前缀
        'path'          => '../cache',
        'compress' => false, //是否压缩数据
        'ds'			=>'/',//路径分隔符
	];

	public function __construct($config=[]){
		$this->config = $config + $this->config;
		$this->config['path'] = rtrim($this->config['path'],'/\\');
		if(!is_dir($this->config['path'])){
			if(mkdir($this->config['path'],0755,true)){
				return true;
			}
		}
	}

	/*获取缓存标识*/
	protected function getCacheKey($name,$auto = false){
		$name = md5($name);
		/*是否分割目录*/
		if($this->config['cache_subdir']){
			$name = substr($name,0,2) . $this->config['ds'] . substr($name,2);
		}

		$name =  empty($this->config['prefix']) ? $name : $this->config['prefix'] . $this->config['ds'] . $name;

		$filename = $this->config['path'] . $this->config['ds'] . $name . '.php';
		/*自动创建目录*/
		$dir = dirname($filename);		
		if($auto && !is_dir($dir)){
			mkdir($dir,0755,true);
		}

		return $filename;
	}

	/*读取缓存*/
	public function get($name){
		/*获取变量所存在的文件名称*/
		$filename = $this->getCacheKey($name);
		if(!is_file($filename)){
			return false;
		}
		$content = file_get_contents($filename);	

		/*判断是否超时*/
		$expire = (int) substr($content,5,12);//获取超时时间
		if(0 != $expire && time() > filemtime($filename) + $expire){
			unlink($filename);
			return false;
		}
		//获取数据
		$content = substr($content,23);

		/*如果数据被压缩过，解压数据*/
		if($this->config['compress'] && function_exists('gzcompress')){
			$content = gzuncompress($content);
		}

		$content = unserialize($content);
		return $content;
	}

	/*写入缓存*/
	public function set($name,$value,$expire =null){
		/*设置缓存时间*/
		$expire = is_null($expire) ? $this->config['expire'] : $expire;

		/*获取文件路径，若没有创建文件*/
		$filename = $this->getCacheKey($name,true);

		/*序列化数据*/
		$data = serialize($value);
		if($this->config['compress'] && function_exists('gzcompress')){
			$data = gzcompress($data,3);
		}
		/*格式化数据*/
		$data = 'wythe'.sprintf('%012d',$expire) . 'wythe'."\n" . $data;

		/*数据写入文件*/
		$result = file_put_contents($filename,$data);

		/*清除php缓存的文件信息*/
		clearstatcache();

		/*返回*/
		return $result ? true : false;
	}

	/*删除缓存*/
	public function delete($name){
		$filename = $this->getCachekey($name);
		return is_file($filename) && unlink($filename);
	}

	/*清空缓存*/
	public function clear($prefix = null){
		if(is_null($prefix)){
			$files = $this->config['path'];
		}else{
			$files = $this->config['path'] . $this->config['ds'] . $prefix;
		}
		$this->deleteFolder($files);
	}

	/*删除文件夹*/
	public function deleteFolder($folder){
		$files = (array) glob($folder.$this->config['ds'].'*');
        foreach ($files as $path) {
            if (is_dir($path)) {
          		$this->deleteFolder($path);
                rmdir($path);
            } else {
                unlink($path);
            }
        }		
	}
}