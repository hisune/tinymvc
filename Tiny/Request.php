<?php
/**
 * Created by hisune.com
 * User: hi@hisune.com
 * Date: 14-7-11
 * Time: 下午4:11
 */
namespace Tiny;

class Request
{
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';

    public static $controller;
    public static $method;
    public static $params;

    /**
     * Is this a GET request?
     * @return bool
     */
    public static function isGet()
    {
        return self::getMethod() === self::METHOD_GET;
    }

    /**
     * Is this a POST request?
     * @return bool
     */
    public static function isPost()
    {
        return self::getMethod() === self::METHOD_POST;
    }

    /**
     * Get HTTP method
     * @return string
     */
    public static function getMethod()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    public static function get($key = null)
    {
        if (is_null($key)) return $_GET;
        else return isset($_GET[$key]) ? (is_array($_GET[$key]) ? $_GET[$key] : trim($_GET[$key])) : NULL;
    }

    public static function post($key = null)
    {
        if (is_null($key)) return $_POST;
        else return isset($_POST[$key]) ? (is_array($_POST[$key]) ? $_POST[$key] : trim($_POST[$key])) : NULL;
    }

    // 当前请求的url
    public static function url()
    {
        $regex = ucfirst(\Tiny\Config::$application) . '\\\\' . 'Controller\\\\'; // 需要考虑到route的目录，所以需要用正则
        $ctrl = preg_replace('/^(' . $regex . ')/', '', self::$controller);
        $ctrl = implode('/', array_map('lcfirst', explode('\\', $ctrl))); // 首字母小写处理

        return \Tiny\Url::get($ctrl . '/' . self::$method);
    }

    public function __set($key, $value)
    {
        self::$key = $value;
    }

}