<?php
define('APP_PATH',__DIR__ . '/../application/');
//$startTime = microtime();
function dump($val){
	echo '<pre>';
	var_dump($val);
	echo '</pre>';
}
function msectime() {
   list($msec, $sec) = explode(' ', microtime());
   $msectime =  (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
   return $msectime;
} 
require __DIR__ . '/../wythe/App.php';
//$endTime = microtime()-$startTime;
//var_dump($endTime);
