<?php
namespace application\index\model;
use wythe\data\db;

class Order{
	/*数据库操作*/
	protected $handler = null;

	/*当前操作订单号*/
	protected $awbnos = '';

	/*可管理用户*/
	protected $user = null;

	/*所属账单*/
	protected $bill = null;

	/*订单状态*/
	private static $status = [];

	/*新建订单*/
	protected function addOrder($type,$orderInfo){

	}

	/*批量创建订单*/
	protected function addMultiOrder($type,$orderinfo){

	}

	/*撤回订单*/
	protected function removeOrder(){

	}

	/*批量撤回订单*/
	protected function removeMultiOrder(){

	}

	/*订单轨迹变更*/
	protected function tracker(){

	}

	/*订单归档*/
	protected function orderArchive(){
		
	}

}