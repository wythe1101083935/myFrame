<?php
return array(
    'option' => array(
        'hello' => true,
        'hello1/:id' => true,
    ) ,
    /*通用数组*/
    '*' => array(
        'hello' => array(
            'rule' => array(
                0 => array(
                    'route' => 'index/hello',
                    'var' => array('id' => true,) ,
                    'option' => array('method' => 'get',) ,
                    'pattern' => array('id' => '\d+',) ,
                ) ,
                1 => array(
                    'route' => 'index/hello',
                    'var' => array('name' => false,) ,
                    'option' => array('method' => 'get',) ,
                    'pattern' => array() ,
                ) ,
            ) ,
            'route' => '',
            'var' => array() ,
            'option' => array() ,
            'pattern' => array() ,
        ) ,
        'hello1' => array(
            'rule'=>'hello1/:id',
            'route' => 'index/index',
            'var' => array(
                'id' => true,
            ) ,
            'option' => array(
                'method' => 'get',
            ) ,
            'pattern' => array(
                'id' => '\d+',
            ) ,
        ) ,
    ) ,
    'alias' => array(
        'user' => 'index/User',
    ) ,
    'domain' => array(
        'blog' => array(
            '[bind]' => array(
                0 => 'blog',
                1 => array() ,
                2 => array() ,
            ) ,
        ) ,
        '*.user' => array(
            '[bind]' => array(
                0 => 'user',
                1 => array() ,
                2 => array() ,
            ) ,
        ) ,
        '*' => array(
            '[bind]' => array(
                0 => 'book',
                1 => array() ,
                2 => array() ,
            ) ,
        ) ,
    ) ,
    'pattern' => array(
        'name' => '\w+',
    ) ,
    'name' => array(
        'index/hello' => array(
            0 => array(
                0 => 'hello/:id',
                1 => array(
                    'id' => 1,
                ) ,
                2 => NULL,
                3 => NULL,
            ) ,
            1 => array(
                0 => 'hello/:name',
                1 => array(
                    'name' => 1,
                ) ,
                2 => NULL,
                3 => NULL,
            ) ,
        ) ,
        'index/index' => array(
            0 => array(
                0 => 'hello1/:id',
                1 => array(
                    'id' => 1,
                ) ,
                2 => NULL,
                3 => NULL,
            ) ,
        ) ,
    ) ,
);
