<?php
/**
 * Created by hisune.com
 * User: 446127203@qq.com
 * Date: 14-7-9
 * Time: 下午5:54
 */
namespace Tiny;

class Dispatch
{
    public $routes;

    public function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    public function controller()
    {
        list($controller, $method, $params) = $this->route();
        if(class_exists($controller) && method_exists($controller, $method)){
            $controller = new $controller();
            if($params)
                call_user_func_array(array($controller, $method), $params);
            else
                $controller->$method();

            // Return the controller instance
            return $controller;
        }else{
            echo '404';
        }
    }

    /**
     * @param $path
     * @return array controller, method, params
     */
    public function route()
    {
        $pathInfo = explode('/', $this->_getPathInfo());
        if($pathInfo['0'] == '')
            return array('Controller\\Index', 'index', array());
        else{
            if(isset($this->routes[$pathInfo['0']])) {
                $class = isset($pathInfo['1']) ? preg_replace("/[^0-9a-z_]/i", '', $pathInfo['1']) : 'index';
                $method = isset($pathInfo['2']) ? preg_replace("/[^0-9a-z_]/i", '', $pathInfo['2']) : 'index';
                $controller = 'Controller\\' . ucwords($this->routes[$pathInfo['0']]) . '\\' . ucwords($class);
                $pathInfo && array_shift($pathInfo);
                $pathInfo && array_shift($pathInfo);
                $pathInfo && array_shift($pathInfo);
            }else{
                $controller = 'Controller\\' . ucwords(preg_replace("/[^0-9a-z_]/i", '', $pathInfo['0']));
                $method = isset($pathInfo['1']) ? preg_replace("/[^0-9a-z_]/i", '', $pathInfo['1']) : 'index';
                $pathInfo && array_shift($pathInfo);
                $pathInfo && array_shift($pathInfo);
            }

            return array($controller, $method, $pathInfo);
        }
    }

    /**
     * nginx下无法获取pathinfo，手动构建path_info
     * 支持子目录，例如正常模式：/tinymvc/public/controller/action，隐藏public模式（需.htaccess支持）：/tinymvc/controller/action
     * 或根目录：/controller/action
     * 可额外加get参数，如：/controller/action?xx=oo
     */
    private function _getPathInfo()
    {
        $scriptName = str_replace('/', '\/', dirname($_SERVER['SCRIPT_NAME']));
        $scriptName = $scriptName == '\\' ? '' : preg_replace('/(\/public)$/', '/(public\/)?', $scriptName);
        if($scriptName){
            $url = parse_url(preg_replace('/^'.$scriptName.'/', '', $_SERVER['REQUEST_URI']));
            return $url['path'];
        }else
            return '';
    }
}