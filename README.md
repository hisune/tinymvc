Hisune Tiny MVC Framework
=========
* 简约为原则的高性能框架，包含：路由，ORM，cookie，session，view，validation，简单权限验证，cache等等。
* 自动生成增删改查代码(themeBuilder)
* 支持Mysql && Mongodb
* 示例程序：https://github.com/hisune/tinymvc-demo
* 示例网站：http://hisune.com

安装方法
=========
* 执行`composer create-project hisune/tinymvc-demo 2.2`
* composer帮助：(https://getcomposer.org)  
> linux：  
> `curl -sS https://getcomposer.org/installer | php`  
> `mv composer.phar /usr/local/bin/composer`  
> windows:  
> https://getcomposer.org/Composer-Setup.exe

系统环境
=========
* Composer
* PHP 5.3+
* PDO extension

必须的参数配置
========
* 可参考 https://github.com/hisune/tinymvc-demo 中的 `app/Demo/bootstrap/autoload.php`
```php
\Tiny\Config::$application = 'demo';
\Tiny\Config::$configDir = __DIR__ . '/../config/';
\Tiny\Config::$varDir = __DIR__ . '/../var/';
\Tiny\Config::$viewDir = __DIR__ . '/../view/';
\Tiny\Config::$controller = array('Controller', 'app/Controller');
register_shutdown_function(array('\Tiny\Exception', 'fatal'));
```

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
> 支持变量绑定的函数有：where，第一个参数为string，第二个参数为绑定变量数组  
> 不支持变量绑定的函数有：field,group,having,order,table,join,limit，只支持一个string参数(limit除外)。limit会对传入的值强行intval，防止后端分页没有处理用户输入的安全隐患  
> 对于链式操作，会在table和join中自动加入prefix，指定表的情况：__TABLE_NAME__会转成：pre_table_name  
> 对于query和execute不支持自动加入prefix，由于本身就是原生sql语句，直接写表名即可

* select使用方法(find)：
```php
 $orders = new model\Orders;
 $orders
     ->alias('o') // 或者用 ->table('__ORDERS__ o')
     ->field('o.order, o.id')
     ->order('? desc', array('o.id'))
     ->where('o.id = ? or o.order = "?"',array("5' and 1=2 union select * from user where id=1/*")) //注入测试
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
 increment(string $column, [int $value]);
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
> 安装完成后访问：/themeBuilder

* 示例(使用Hisune tinyMVC 开发的AdminLTE后台示例)
themeBuilder 之 dataTable
![admin panel](https://raw.githubusercontent.com/hisune/images/master/tinymvc_admin_1.jpg)
themeBuilder 之 mod
![admin panel](https://raw.githubusercontent.com/hisune/images/master/tinymvc_admin_2.jpg)

About
========
**Created by Hisune [lyx](http://hisune.com)**
