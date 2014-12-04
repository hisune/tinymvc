Hisune Tiny MVC Framework
=========
* 简约为原则的高性能框架，包含：路由，简单权限验证，cookie，session，ORM，view，validation，cache等等。
* Author: Hisune(http://hisune.com)

安装方法
=========
* 下载`composer.json`到任意目录，cd到composer.json所在目录，执行`composer install`
* composer帮助：(https://getcomposer.org)

通用配置
========
* config.php配置举例：
```php
return array(
    'debug' => false, // 是否开启调试模式
    'flag' => 'xxoo', // session唯一标识
    'show_error' => false, // 是否显示错误
    'timezone' => 'PRC', // 时区
    'token' => false, // 自动加token
    'database' => array(
        'dns' => "mysql:host=127.0.0.1;port=3306;dbname=recharge;charset=UTF8", // 主从分离用逗号','隔开
        'username' => 'root',
        'password' => '',
        'prefix' => '',
        'separate' => false, // 主从分离
        'rand_read' => false, // 随机读取
        'log_queries' => false, // 是否记录所有请求
    ),
);
```

路由举例配置
=========
```php
return array(
    // 路由配置
    'routes' => array(
        'admin' => 'admin', // 方式1，子模块模式
        'page/{id}' => function($id){ // 方式2，直接处理数据
            echo md5($id);
        },
        '{num}' => function ($num, &$controller, &$method, &$pathInfo) { // 方式3：指定c,m,p
            $controller = 'Index';
            $method = 'index';
            $pathInfo = array($num);
        },
        'param/{param?}' => function ($param, &$controller, &$method, &$pathInfo) { // 例：最后一个参数可不传递, 用'?'
            $controller = 'Index';
            $method = 'test';
            $pathInfo = array($param);
        },
    ),
    // 路由匹配后的正则配置
    'pattern' => array(
        'num' => '[0-9]+',
        'param' => '[0-9]*',
    ),
);
```
ORM介绍
========
> 仿tp的orm，更简单，效率更优。注意使用时一定要绑定变量！  
> 支持主从读写分离，支持主从随机读取；  
> 对于直接执行原生sql语句，主库用execute，从库用query，原生sql也支持变量绑定  
> 支持prefix  
> 支持变量绑定的函数有：where,group,having,order，第一个参数为string，第二个参数为绑定变量数组  
> 不支持变量绑定的函数有：field,table,join,limit，只支持一个string参数(limit除外)。limit会对传入的值强行intval，防止后端分页没有处理用户输入的安全隐患  
> 对于链式操作，会在table和join中自动加入prefix，指定表的情况：__TABLE_NAME__会转成：pre_table_name  
> 对于query和execute不支持自动加入prefix，由于本身就是原生sql语句，直接写表名即可

* select使用方法(find)：
```php
 $orders = new \Model\Orders;
 $orders
     ->alias('o') // 或者用 ->table('__ORDERS__ o')
     ->field('o.order, o.id'))
     ->order('? desc', array('o.id'))
     ->where('o.id = ? or o.order = "?"',array("5' and 1=2 union select * from user where id=1/*")) //
```
* 注入测试
```php
     ->limit('1/*,1') // limit强制整型测试
     ->join('__TEST__ t on t.id = o.test_id', 'left')
     ->group('?', array('o.order'))
     ->having('count(*) > ?', array('1'))
     ->find() // find()无参数
```
* 其他CURD方法：
```php
 findOne([int $id]) // R，指定id为where条件
 save([array $data], [boolean $replace]) // C
 update([array $data], [boolean $all]) // U，慎用all
 delete([int $id], [boolean $all]) // D，慎用all
 query(string $sql, [array $param], [boolean $fetchAll])
 execute(string $sql, [array $param])
 ```
* 单列数据统计方法，多列或其他复杂情况用field：
```php
 count([string $column])
 max([string $column])
 avg([string $column])
 min([string $column])
 sum([string $column])
 distinct([string $column])
```
* 其他说明：
```php
field() //第一参数支持array，第一参数为array时，不支持别名，别名用string
where() //第一参数支持array，此时第二参数无效；第一参数为array时，不支持别名，别名请用string；只支持绑定条件变量。
limit() //支持string类似，limit('0, 10')，或双参数类似，limit(0, 10)
```

Theme Builder介绍
========
* 简单介绍
> 设计思想：不重复写模板和逻辑，通过简单配置实现某些通用功能。  
> 设计思路：在控制器(controller)中指定附加action，theme builder读取辅助类(helper)中的配置进行处理。  
> Tabs: bootstrap风格的tab，ajax显示content。  
> Datatables:bootstrap风格的数据列表。可用作普通分页、排序、过滤列表或单纯table列表

* 需以下js插件支持：
> bootstrap          http://getbootstrap.com/  
> bootbox           http://bootboxjs.com/   https://github.com/makeusabrew/bootbox  
> daterangepicker     https://github.com/dangrossman/bootstrap-daterangepicker  
> multiselect         https://github.com/davidstutz/bootstrap-multiselect

* 怎么使用？
Controller中加入action成员属性，例如：
```php
　　protected $actionTabs = array(
　　	'type' => 'theme',
　　	'name' => 'Tabs',
　　);
```
Helper中加入getSetting方法，例如：
```php
public static function getTabsSetting()
```

* Tabs参数配置：
> id: 唯一id，数字或字母，如未配置将随机生成，可选  
> js: 附加js代码，可选  
> tabs: 菜单数组，包括title（标题）,url（ajax内容url），必须

* Datatables参数配置：
```php
id: 唯一id，数字或字母，如未配置将随机生成，可选
js: 附加js代码，可选
model: 指定模型名称，可选
export: 是否可到处，可选，默认为false
page: 默认分页条数，可选，默认为10，false时表示不分页。可能数值：10, 25, 50, 100
title: panel的head附加html，可选，默认为空。通常用在无分页表格时的说明
before: 表格前附加html，可选，默认为空
after: 表格后附加html，可选，默认为空
default: 默认配置参数，可选
　　filter: 默认过滤条件，string类型，可选。例如：i.select > 10
　　group: 默认过滤条件，string类型，可选。
　　having: 默认过滤条件，string类型，可选。
join: 链表查询，可选
　　main: 主表别名，string类型，必须。
　　on: 链表数组，必须。例如：
　　array(
　　	'main' => 'i',
　　	'on' => array(
　　		array('type' => 'inner' , 'join' => '__JOIN__ j on i.id = j.index_id'),
　　		array('type' => 'left' , 'join' => '__JOIN_TEST__ t on j.id = t.join_id'),
　　	),
　　)
　　type: 链表方式，left,right,inner，必须
　　hoin: 链表语句，必须
column: 内容数组，包括显示字段配置及过滤配置，必须
　　title: 标题，必须
　　name: 数据库中的字段名，可选
　　alias: 字段的别名，可选
　　sort: 是否排序，可指定默认排序，例如'sort' => 'desc'，一般来说只允许一个字段配置默认排序，可选
　　tips: 字段说明，将显示在thead的th中，可选
　　call: 查询出来的数据执行什么函数，可选。内置函数有enum(键值对，需配置enum参数), date(标准时间), date_sort(日期)
　　display: 是否在前端显示该字段，可选，默认true。如果为false，call参数将无效
　　filter: 是否可过滤该字段，可选，详细配置：
　　所有type均可配置call参数(辅助过滤函数)，例如配置'call' => 'callTest'，需在helper中定义：public static function dataTablesFilterCallTest($post, &$whereStr, &$whereBind)。
　　'type' => 'hidden':
　　value: 默认过滤值，可选，可用来替换datatables的default filter
　　'type' => 'range':
　　width: 输入框长度，可选，单位为px，默认70
　　value: 默认过滤值，可选，如：1~100
　　'type' => 'input':
　　width: 输入框长度，可选，单位为px，默认110
　　value: 默认过滤值，可选
　　title: 输入框的placeholder，可选，默认为column的title
　　like: 是否可模糊查询，可选，默认否
　　'type' => 'date_range':
　　width: 输入框长度，可选，单位为px，默认230
　　value: 默认过滤值，可选，如：2014-10-02 ~ 2014-10-08
　　title: 输入框的placeholder，可选，默认为column的title
　　format: 日期格式，可选，默认YYYY-MM-DD HH:mm
'type' => 'date':
　　width: 输入框长度，可选，单位为px，默认230
　　value: 默认过滤值，可选，如：2014-10-02 ~ 2014-10-08
　　title: 输入框的placeholder，可选，默认为column的title
　　format: 日期格式，可选，默认YYYY-MM-DD HH:mm
'type' => 'select':
　　value: 默认过滤值，可选
　　option: option数组key-value键值对，必须
　　filter: option是否可前端过滤，可选，默认false
　　height: 前端select最大高度，可选，默认400
```

* helper中的辅助函数
> dataTablesPostBefore: post数据时的前置函数，通常用来处理用户输入数据，例如安全过滤，验证等，可选；  
> 函数原型：public static function dataTablesPostBefore(&$post)。  
>  
> dataTablesPostAfter: post数据时的后置函数，通常用来处理返回数据；  
> 函数原型：public static function dataTablesPostAfter(&$msg)。  
>  
> 所有字段都可配置call参数(辅助输出函数)，例如配置'call' => 'callTest'；  
> 需在helper中定义：public static function dataTablesShowRenderCallTest($row)，需返回显示内容。

* datatables完整配置示例：
```php
    public static function getDataTablesSetting()
    {
        return array(
            'id' => '',
            'js' => '',
　　'export' => true,
　　'page' => 25,
　　'title' => '',
            'default' => array(
                'filter' => 'i.select > 0', // 默认过滤参数
                'group' => '',
                'having' => '',
            ),
　　'before' => 'xx',
　　'after' => 'oo',
            'column' => array(
                array(
                    'title' => 'hidden测试',
                    'name' => 'i.hidden',
                    'alias' => 'hidden',
                    'filter' => array(
                        'type' => 'hidden',
                        'call' => 'callHidden', // 当前helper中定义call函数$post, &whereStr, &$whereBind
                    ),
　　'display' => false
                ),
                array(
                    'title' => 'range测试',
                    'name' => 't.range',
                    'filter' => array(
                        'type' => 'range',
                        'width' => '50',
                    )
                ),
                array(
                    'title' => 'input测试',
                    'name' => 'j.input',
                    'sort' => true,
                    'filter' => array(
                        'type' => 'input',
                        'like' => true,
                    ),
                    'call' => 'renderInput',
                ),
                array(
                    'title' => 'date-range',
                    'name' => 'i.date_range',
                    'filter' => array(
                        'type' => 'date_range',
                        'width' => '100',
                    ),
                    'call' => 'date',
                ),
                array(
                    'title' => 'date-time',
                    'name' => 'i.date',
                    'filter' => array(
                        'type' => 'date',
                    ),
                ),
                array(
                    'title' => '测试',
                    'name' => 'i.select',
                    'sort' => 'asc',
                    'filter' => array('type' => 'select', 'filter' => false, 'option' => array(1 => 'test1', 2 => 'test2')),
                    'call' => 'enum',
                    'enum' => array(1 => 'test1', 2 => 'test2'),
                ),
                array(
                    'title' => '操作',
                    'tips' => 'tips测试',
                    'call' => 'renderTest', // 不带name的call只有一个参数，即当前结果行
                ),
            ),
            'model' => 'Index',
            'join' => array(
                'main' => 'i',
                'on' => array(
                    array('type' => 'left' , 'join' => '__JOIN__ j on i.id = j.index_id'),
                    array('type' => 'left' , 'join' => '__JOIN_TEST__ t on j.id = t.join_id'),
                ),
            ),
        );
    }

    // 前置post函数，$post为查询数据
    public static function dataTablesPostBefore(&$post)
    {

    }

    // 后置post函数，$msg为返回数据
    public static function dataTablesPostAfter(&$msg)
    {

    }

    // 辅助过滤函数，$post为查询数据，$whereStr为查询语句string，$whereBind为查询语句绑定变量数组
    public static function dataTablesFilterCallHidden($post, &$whereStr, &$whereBind)
    {

    }

    // 辅助输出函数，$row为当前结果行
    public static function dataTablesShowRenderTest($row)
    {
        return $row->hidden;
    }
　　
```
About
========
**Created by Hisune [lyx](http://hisune.com)**