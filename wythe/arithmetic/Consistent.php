<?php
/**
 +----------------------------------------------------------
 * 一致性哈希算法，算法代码太少，直接写在memcache驱动里
 * 若是以后算法数量多了可以分离出来，总结一些算法之后再说
 +----------------------------------------------------------
 * CODE:
 +----------------------------------------------------------
 * TIME:2018-07-14 13:46:57
 +----------------------------------------------------------
 * author:十八哥(燕十八原名刘道成)
 +----------------------------------------------------------
 */
class Consisitent{
	protected $_nodes = array(); //节点
	protected $_position = array(); //虚拟节点
	protected $_mul = 64; //每个节点分化的虚拟节点的个数

	/*唯一字符定位*/
	public function _hash($str){
		return sprintf('%u',crc32($str));
	}

	/*功能*/
	public function lookup($key){
		$point = $this->_hash($key);

		$node = current($this->_position);

		foreach ($this->_position as $k=>$v) {
			if($point <= $k){
				$node = $v;
				break;
			}
		}
		reset($this->_position);
		return $node;
	}

	/*添加一个节点*/
	public function addNode($node){
		if(isset($this->nodes[$node])){
			return;
		}

		for ($i=0; $i < $this->_mul; $i++) { 
			$pos = $this->_hash($node . '-' . $i);
			$this->_position[$pos] = $node;
			$this->_nodes[$node][] = $pos;
		}

		$this->_sortPos();
	}
	/*删除节点*/
	public function delNode($node){
		if(!isset($this->_nodes[$node])){
			return;
		}

		foreach ($this->_nodes[$node] as $k) {
			unset($this->_position[$k]);
		}

		unset($this->_nodes[$node]);
	}
	/*节点排序*/
	protected function _sortPos(){
		ksort($this->_position,SORT_REGULAR)
	}
}