<?php
/**
 * Created by hisune.com.
 * User: hi@hisune.com
 * Date: 14-7-11
 * Time: 下午6:01
 */
namespace Tiny;

class Auth
{
    public static function isLogin($session = 'user')
    {
        if(Session::get($session))
            return true;
        else
            return false;
    }

    public static function checkLogin($redirect = 'session/login')
    {
        if(!self::isLogin()){
            Url::redirect($redirect);
        }
    }

    public static function isAdmin($session = 'is_admin')
    {
        if(Session::get($session))
            return true;
        else
            return false;
    }

    public static function checkAdmin($redirect = 'session/login')
    {
        if(!self::isLogin() || !self::isAdmin()){
            Url::redirect($redirect);
        }
    }
}