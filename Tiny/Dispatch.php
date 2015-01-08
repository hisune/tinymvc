<?php
/**
 * Created by hisune.com.
 * User: hi@hisune.com
 * Date: 14-7-9
 * Time: 下午5:54
 */
namespace Tiny;

class Dispatch
{
    public $route;
    private $_routeRewrite = false; // 当前请求是否有路由重写

    public function __construct($route)
    {
        $this->route = $route;
    }

    public function controller()
    {
        session_start();
        headers_sent() OR header('Content-Type: text/html; charset=utf-8');

        list(Request::$controller, Request::$method, Request::$params) = $this->route();

        if(!is_null(Request::$controller) && !is_null(Request::$method)){
            if(!class_exists(Request::$controller)){
                if(Config::$error404){
                    Request::$controller = Config::$controller['0'] . '\\' . Config::$error404['0'];
                    Request::$method = Config::$error404['1'];
                    Request::$params = array();
                }else
                    Error::print404();
            }else{
                $controllerInstance = new Request::$controller();
                $controllerInstance->initialize(Request::$method);

                if(Request::$params)
                    call_user_func_array(array($controllerInstance, Request::$method), Request::$params);
                else
                    $controllerInstance->{Request::$method}();
            }
        }

        if(Config::config()->debug)
            \Tiny\Debug::Detail(
                array(
                    array('name' => 'Controller', 'value' => Request::$controller),
                    array('name' => 'Method', 'value' => Request::$method),
                    array('name' => 'params', 'value' => Request::$params),
                )
            );
    }

    /**
     * 路由分发处理
     * @return array controller, method, params
     * todo: 路由中参数的类型支持
     *
     * route配置举例：
     *        'admin' => 'admin', // 方式1，子模块模式
     *        'admin/test/xx/{id1}/{id2}' => function($id1, $id2, &$controller, &$method){  // 方式2，指定c,m,p
     *            $controller = 'Index';
     *            $method = 'index';
     *        },
     *        'page/{id}' => function($id){  // 方式3，直接处理数据
     *            var_dump($id);
     *        },
     *        'category/{id}/{slug}/{page?}' => function($id, $slug, $page, &$controller, &$method, &$pathInfo){ // 例2：category分类重写(最后一个参数可不传递, 用'?')
     *            $controller = 'Index';
     *            $method = 'category';
     *            $pathInfo = array($id, $slug, $page ? $page : 1);
     *        },
     */
    public function route()
    {
        $path = Url::pathInfo();
        $pathInfo = explode('/', $path);

        if($pathInfo['0'] == '') // 访问的是根目录
            return array(ucfirst(Config::$application) . '\\' . Config::$controller['0'] . '\\Index', 'index', array());
        else{ // 需要路由分发处理
            if($this->route->routes) { // 有配置路由规则
                if(isset($this->route->routes[$pathInfo['0']])){ // 普通的子模块目录重写
                    $class = isset($pathInfo['1']) ? preg_replace("/[^0-9a-z_]/i", '', $pathInfo['1']) : 'index';
                    $method = isset($pathInfo['2']) ? preg_replace("/[^0-9a-z_]/i", '', $pathInfo['2']) : 'index';
                    $controller = ucfirst(Config::$application) . '\\' . Config::$controller['0'] . '\\' . ucwords($this->route->routes[$pathInfo['0']]) . '\\' . ucwords($class);
                    $pathInfo && array_shift($pathInfo);
                    $pathInfo && array_shift($pathInfo);
                    $pathInfo && array_shift($pathInfo);
                    $this->_routeRewrite = true;
                }else{ // 更高级的路由分发，一种直接在回调函数中处理，一种修改引用变量controller, method, pathInfo的值
                    foreach ($this->route->routes as $k => $v) {
                        if(is_callable($v)){
                            $pattern = preg_replace(array('@\{(\w+)\}@', '@/\{(\w+)\?\}@'), array('(?<\1>[^/]+)', '/?(?<\1>[^/]?)'), $k);
                            preg_match('@^' . $pattern . '$@', $path, $val);
                            if(!$val) continue;

                            $tok = array_filter(array_keys($val), 'is_string');
                            $val = array_map('urldecode', array_intersect_key(
                                $val,
                                array_flip($tok)
                            ));

                            // 正则匹配参数内容
                            foreach($val as $name => $value){
                                if(isset($this->route->pattern[$name]))
                                    if(!preg_match('/^' . $this->route->pattern[$name] . '$/', $value))
                                        continue 2;
                            }

                            $controller = $method = null;
                            $pathInfo = array();

                            $call = array_merge(
                                $val, // 前面的url参数替换
                                array(&$controller, &$method, &$pathInfo) // 后3个参数为cmp
                            );
                            call_user_func_array($v, $call);
                            is_string($controller) && $controller = ucfirst(Config::$application) . '\\' . Config::$controller['0'] . '\\' . $controller;

                            $this->_routeRewrite = true;
                            break; // 只匹配第一个规则
                        }
                    }
                }
            }

            if(!$this->_routeRewrite){ // 普通路由分发
                $controller = ucfirst(Config::$application) . '\\' . Config::$controller['0'] . '\\' . ucwords(preg_replace("/[^0-9a-z_]/i", '', $pathInfo['0']));
                $method = isset($pathInfo['1']) ? preg_replace("/[^0-9a-z_]/i", '', $pathInfo['1']) : 'index';
                $pathInfo && array_shift($pathInfo);
                $pathInfo && array_shift($pathInfo);
            }
            return array($controller, $method, $pathInfo);
        }
    }

}