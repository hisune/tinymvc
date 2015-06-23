<?php
/**
 * Created by hisune.com
 * User: hi@hisune.com
 * Date: 14-7-11
 * Time: 上午9:58
 */
namespace Tiny;

abstract class Config
{
    static $configs = array();

    static $application = 'public'; // 默认应用名
    static $configDir = ''; // __DIR__ . '/../app/config/'
    static $varDir = ''; // __DIR__ . '/../var/'
    static $viewDir = ''; // __DIR__ . '/../app/view/'
    static $controller = array();  // array('Namespace', 'app/Controller')
    static $error404 = array();  // array('Controller', 'method')
    static $secret = '8RtX*K#%Gw=5VEQ=VT';
    static $authPurview = false; // 是否验证权限

    public static function __callStatic($method, $args = array())
    {
        if (empty(self::$configs[$method])) {
            $file = self::$configDir . $method . '.php';

            if (file_exists($file))
                $require = require($file);
            else
                $require = array();

            self::$configs[$file] = (object)$require;
        }

        return self::$configs[$file];
    }

    public function __set($key, $value)
    {
         self::$key = $value;
    }

}