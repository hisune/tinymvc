<?php
/**
 * Created by hisune.com
 * User: hi@hisune.com
 * Date: 14-7-11
 * Time: 下午6:01
 */
namespace Tiny;

class Auth
{
    public static $login = 'session/login';

    public static function isLogin($session = 'user')
    {
        if(Cookie::get($session))
            return true;
        else
            return false;
    }

    public static function checkLogin()
    {
        if(!self::isLogin()){
            Url::redirect(self::$login);
        }
    }

    public static function isAdmin($session = 'is_admin')
    {
        if(Cookie::get($session))
            return true;
        else
            return false;
    }

    public static function checkAdmin($controller)
    {
        if(!self::isLogin() || !self::isAdmin()){
            if(isset($controller::$authWhite['admin']) && !in_array(Request::$method, $controller::$authWhite['admin'])){
                Url::redirect(self::$login);
            }
        }
        if(Config::$authPurview){
            if(!self::hasPurview()){
                exit('Access Denied');
            }
        }
    }

    /**
     * @param $md5 $password是否是已md5过的字符串
     */
    public static function getPassword($password, $md5 = true)
    {
        if($md5)
            return md5($password . Config::$secret);
        else
            return md5(md5($password) . Config::$secret);
    }

    /**
     * 设置自动登录
     */
    public static function setLogin($user, $isAdmin = false, $expires = null)
    {
        if($expires === false)
            $expires = time() + 31536000;

        Cookie::set('user', $user, array('expires' => $expires));
        Cookie::set('is_admin', $isAdmin, array('expires' => $expires));
    }

    public static function setLogout()
    {
        Cookie::set('user', null, array('expires' => -1));
        Cookie::set('is_admin', null, array('expires' => -1));
    }

    // 设置权限cache
    public static function setPurviewCache($groupId, array $purview)
    {
        return Cache::set('_purview_' . $groupId, $purview);
    }

    // 获取权限cache
    public static function getPurviewCache()
    {
        $user = Cookie::get('user');
        return Cache::get('_purview_' . $user['group_id']);
    }

    // 判断是否有权限
    public static function hasPurview($controller = null, $method = null)
    {
        if($controller && $method){
            $ctr = ucfirst(Config::$application) . '\\' . Config::$controller['0'] . '\\' . ucfirst($controller);
        }else{
            $ctr = Request::$controller;
            $explode = explode('\\', $ctr);
            $controller = strtolower(end($explode));
            $method = Request::$method;
        }

        if(!class_exists($ctr)){
            return false;
        }

        $purview = self::getPurviewCache();

        $white = property_exists($ctr, 'authWhite') && isset($ctr::$authWhite['purview']) ? $ctr::$authWhite['purview'] : array();
        if(
            !in_array(strtolower($method), $white) &&
            !in_array(strtolower($controller . '@' . $method), $purview) &&
            !in_array(strtolower($controller . '@*'), $purview)
        ){
            return false;
        }
        return true;
    }
}