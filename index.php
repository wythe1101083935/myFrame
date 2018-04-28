<?php
namespace wythe;
require __DIR__ . '/wythephp/library/wythe/Route.php';

        /*
        $url : "hello|id|12"
        $rule: "hello/:id"
        $pattern:   ["name"] => string(3) "\w+"
                    ["id"] => string(3) "\d+

        */
        /*
        string(11) "hello|id|12"

        string(11) "hello/:name"

        array(1) {
          ["name"] => string(3) "\w+"
        }
        */
/*$url = 'hello|id|12|name|abc';
$rule = 'hello/[:id]/:name';
$pattern = array(
	'name'=>'\w+',
	'id'=>'\d+'
);*/

/*$url = 'hello|id|12';
$rule ='hello/:name';
$pattern = array('name'=>'\w+');*/


Route::match($url,$rule,$pattern);










function dump($var){
	echo '<pre>';
	var_dump($var);
	echo '</pre>';
}