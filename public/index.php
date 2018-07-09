<?php
define('APP_PATH',__DIR__ . '/../application/');
//$startTime = microtime();
function dump($val){
	echo '<pre>';
	var_dump($val);
	echo '</pre>';
}
require __DIR__ . '/../wythe/App.php';
//$endTime = microtime()-$startTime;
//var_dump($endTime);
