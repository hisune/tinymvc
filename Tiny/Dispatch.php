<?php
/**
 * Created by lyx.
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

    public function controller($path)
    {

    }


    /**
     * Parse the given URL path and return the correct controller and parameters.
     *
     * @param string $path segment of URL
     * @param array $routes to test against
     * @return array
     */
    public function route($path)
    {

    }
}