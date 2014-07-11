<?php
/**
 * Created by hisune.com
 * User: 446127203@qq.com
 * Date: 14-7-11
 * Time: 下午4:11
 */
namespace Tiny;

class Request
{
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';

    /**
     * Is this a GET request?
     * @return bool
     */
    public function isGet()
    {
        return $this->getMethod() === self::METHOD_GET;
    }

    /**
     * Is this a POST request?
     * @return bool
     */
    public function isPost()
    {
        return $this->getMethod() === self::METHOD_POST;
    }

    /**
     * Get HTTP method
     * @return string
     */
    public function getMethod()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    public function get($key)
    {
        if (is_null($key)) return $_GET;
        else return isset($_GET[$key]) ? (is_array($_GET[$key]) ? $_GET[$key] : trim($_GET[$key])) : NULL;
    }

    public function post($key)
    {
        if (is_null($key)) return $_POST;
        else return isset($_POST[$key]) ? (is_array($_POST[$key]) ? $_POST[$key] : trim($_POST[$key])) : NULL;
    }
}