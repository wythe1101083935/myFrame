<?php
/**
 +----------------------------------------------------------
 * user作为全局信息
 +----------------------------------------------------------
 * CODE:
 +----------------------------------------------------------
 * TIME:2018-07-19 09:52:54
 +----------------------------------------------------------
 * author:wythe(汪志虹)
 +----------------------------------------------------------
 */
namespace application\index\model;
use wythe\data\db;
class User{
	/*当前用户id*/
	protected $userId = 0;

	/*当前用户角色id*/
	protected $roleId = 0;

	/*当前用户信息*/
	protected $userInfo = [];

	/*当前角色信息*/
	protected $roleInfo = [
		'roleDesciption'=>[],
		'roleAuth'=>[
			'adminUser'=>'8',//对那些用户有权限
			'user'=>'updateUser,userList',//对用户管理有那些权限
			'order'=>'',//对订单有那些权限
		],
	];

	/*当前操作订单号*/
	protected $awbnos = [];

	/*用户功能*/
	/*查看管理用户列表*/
	protected function userList(){

	}

	/*可查看订单列表*/
	protected function orderList(){

	}

	/*可查看账单列表*/
	protected function billList(){

	}

	/*增加用户*/
	protected function addUser(){

	}

	/*修改用户*/
	protected function updateUser(){

	}

	/*删除用户*/
	protected function deleteUser(){

	}



}