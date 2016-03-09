<?php
/**
 * Created by hisune.com.
 * User: hi@hisune.com
 * Date: 14-7-11
 * Time: 下午6:02
 */
namespace Tiny;

class Session
{
    public static function set($key, $value, $merge = false)
    {
        if (session_id() === '') session_start();

        $name = Config::config()->flag . '_' . $key;
        if($value == null)
            unset($_SESSION[$name]);
        else{
            if (is_array($value) && isset($_SESSION[$name]) && $merge) {
                $_SESSION[$name] = array_merge($_SESSION[$name], $value);
            } else {
                $_SESSION[$name] = $value;
            }
        }

    }

    public static function get($key = null)
    {
        $name = Config::config()->flag . '_' . $key;

        return isset($_SESSION[$name]) ? $_SESSION[$name] : null;
    }

}