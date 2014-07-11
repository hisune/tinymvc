<?php
/**
 * Created by hisune.com
 * User: 446127203@qq.com
 * Date: 14-7-11
 * Time: 上午9:58
 */
namespace Tiny;

abstract class Config
{
    static $configs = array();

    public static function __callStatic($method, $args = array())
    {
        if(empty(self::$configs[$method]))
        {
            $file = PUBLIC_DIR . '/../app/config/' . $method . '.php';

            if(file_exists($file))
                $require = require($file);
            else
                $require = array();

            self::$configs[$file] = (object)$require;
        }

        return self::$configs[$file];
    }
}