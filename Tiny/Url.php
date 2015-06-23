<?php
/**
 * Created by hisune.com.
 * User: hi@hisune.com
 * Date: 14-7-14
 * Time: 下午3:08
 */
namespace Tiny;

class Url
{
    private static function getScriptPreg()
    {
        $scriptName = str_replace('/', '\/', dirname($_SERVER['SCRIPT_NAME']));
        return $scriptName == '\\' ? '' : preg_replace('/(\/' . Config::$application . ')$/', '/(' . Config::$application . '\/)?', $scriptName);
    }

    public static function get($name, array $params = array())
    {
        $scriptName = self::getScriptPreg();
        preg_match('/^'.$scriptName.'/', $_SERVER['REQUEST_URI'], $match); // 获取请求地址的根目录，支持加public和不加public

        $uri = isset($match['0']) ? $match['0'] . $name : $name;
        $params && $uri = $uri . '?' . http_build_query($params);
        return self::getDomain() . $uri;
    }

    public static function isHttps()
    {
        return isset($_SERVER['HTTPS']);
    }

    public static function getDomain($protocol = true){
        if($protocol){
            if(self::isHttps())
                $protocol = 'https://';
            else
                $protocol = 'http://';
        }else
            $protocol = '';

        return $protocol . $_SERVER['HTTP_HOST'];
    }


    public static function redirect($name = '')
    {
        header('location:' . self::get($name));
        exit;
    }

    public static function back($addOn = '')
    {
        header('location:' . $_SERVER['HTTP_REFERER'] . $addOn);
        exit;
    }

    /**
     * nginx下无法获取pathinfo，手动构建path_info
     * 支持子目录，例如正常模式：/tinymvc/public/controller/action，隐藏public模式（需.htaccess支持）：/tinymvc/controller/action
     * 或根目录：/controller/action
     * 可额外加get参数，如：/controller/action?xx=oo
     */
    public static function pathInfo()
    {
        $scriptName = self::getScriptPreg();
        if($scriptName){
            $url = parse_url(preg_replace('/^'.$scriptName.'/', '', $_SERVER['REQUEST_URI'])); // 支持加public和不加public
            return isset($url['path']) ? $url['path'] : '';
        }else
            return '';
    }
}