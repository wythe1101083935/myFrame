<?php
namespace application\index\controller;
use wythe\data\Db;
class Index
{

    public function index(){
        //echo 123;
       $db = new Db();
       $where = [
        ['b.cid','in','sd,3d,23,23','OR'],
        [
          ['a.cid','>',10,'OR'],
          ['d.id','<',20]
        ],
        ['c.uid',15]
       ];
       $db->table('awb as a,awbno as b')->field('a.sd,b.ds,a.ds')->where('a','<',12)->where('d','>',12)->where($where)->join('awb as a','a.id=b.id')->select();
       //$db->where('a','>',12)->where('b','<',13)->where('c',15)->testWhere();
    }

    public function index1()
    {
    	header("Content-Type:text/html;Charset=utf-8");
    	$mysql = Db::connect([
        // 数据库类型
        'type'            => 'mysql',
        // 服务器地址
        'hostname'        => 'localhost',
        // 数据库名
        'database'        => 'knowlege',
        // 用户名
        'username'        => 'root',
        // 密码
        'password'        => '123456',
        // 端口
        'hostport'        => '3306',
        // 数据库连接参数
        'params'          => [],
        // 数据库编码默认采用utf8
        'charset'         => 'utf8',
        // 数据库表前缀
        'prefix'          => '',
        // 数据库调试模式
        'debug'           => false,
        // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
        'deploy'          => 0,
        // 数据库读写是否分离 主从式有效
        'rw_separate'     => false,
        // 读写分离后 主服务器数量
        'master_num'      => 1,
        // 指定从服务器序号
        'slave_no'        => '',
        // 是否严格检查字段是否存在
        'fields_strict'   => true,
        // 数据集返回类型
        'resultset_type'  => 'array',
        // 自动写入时间戳字段
        'auto_timestamp'  => false,
        // 时间字段取出后的默认时间格式
        'datetime_format' => 'Y-m-d H:i:s',
        // 是否需要进行SQL性能分析
        'sql_explain'     => false,
        // 是否需要断线重连
        'break_reconnect' => false,
    	]);

        $sql = 'select * from wythe_kl';

       $res =  $mysql->query($sql);
       var_dump($res);

    }

    public function hello(){
    	echo 'hello';
    }
}
