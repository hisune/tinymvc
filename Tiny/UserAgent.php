<?php
/**
 * Created by hisune.com.
 * User: hi@hisune.com
 * Date: 14-7-21
 * Time: 下午5:52
 *
 * part of function come form https://github.com/iamcal/flamework-useragent
 */
namespace Tiny;

class UserAgent
{
    public static function get($ua = null){
        is_null($ua) && $ua = $_SERVER['HTTP_USER_AGENT'];
        #
        # a list of user agents, in order we'll match them.
        # e.g. we put chrome before safari because chrome also
        # claims it is safari (but the reverse is not true)
        #

        $agents = array(
            'chrome',
            'safari',
            'konqueror',
            'firefox',
            'netscape',
            'opera',
            'msie',
            'dalvik',
            'blackberry',
        );

        $engines = array(
            'webkit',
            'gecko',
            'trident',
            'presto',
        );

        $ua = strtolower($ua);
        $out = array();

        $temp = self::_userAgentMatch($ua, $agents);
        $out['agent']		= $temp['token'];
        $out['agent_version']	= $temp['version'];

        $temp = self::_userAgentMatch($ua, $engines);
        $out['engine']		= $temp['token'];
        $out['engine_version']	= $temp['version'];


        #
        # safari does something super annoying, putting the version in the
        # wrong place like: "Version/5.0.1 Safari/533.17.8"
        #
        # opera does the same thing:
        # http://dev.opera.com/articles/view/opera-ua-string-changes/
        #

        if ($out['agent'] == 'safari' || $out['agent'] == 'opera'){
            $temp = self::_userAgentMatch($ua, array('version'));
            if ($temp['token']) $out['agent_version'] = $temp['version'];
        }

        if ($out['agent'] == 'blackberry' && !$out['agent_version']){
            if (preg_match('!blackberry(\d+)/(\S+)!', $ua, $m)){
                $out['agent_version'] = $m[2];
            }
        }


        #
        # OS matching needs to do some regex transformations
        #

        $os = array(
            'windows nt 5.1'		=> array('windows', 'xp'),
            'windows nt 5.2'		=> array('windows', 'xp-x64'),
            'windows nt 6.0'		=> array('windows', 'vista'),
            'windows nt 6.1'		=> array('windows', '7'),
            'windows nt 6.2'		=> array('windows', '8'),
            'windows nt 6.3'		=> array('windows', '8.1'),
            'android'			=> array('android', ''),
            'linux i686'			=> array('linux', 'i686'),
            'linux x86_64'			=> array('linux', 'x86_64'),
            '(ipad; '			=> array('ipad', ''),
            '(ipod; '			=> array('ipod', ''),
            '(iphone; '			=> array('iphone', ''),
            'blackberry'			=> array('blackberry', ''),
        );

        $out['os']		= null;
        $out['os_version']	= null;

        foreach ($os as $k => $v){
            if (strpos($ua, $k) !== false){
                $out['os'] = $v[0];
                $out['os_version'] = $v[1];
                break;
            }
        }

        if (in_array($out['os'], array('iphone', 'ipad', 'ipod'))){

            if (preg_match('!os (\d+)[._](\d+)([._](\d+))? like mac os x!', $ua, $m)){
                $out['os_version'] = "$m[1].$m[2]";
                if ($m[4]) $out['os_version'] .= ".$m[4]";
            }
        }

        if ($out['os'] == 'android'){

            if (preg_match('!android (\d+)\.(\d+)(\.(\d+))?!', $ua, $m)){
                $out['os_version'] = "$m[1].$m[2]";
                if ($m[4]) $out['os_version'] .= ".$m[4]";
            }
        }

        if ($out['os'] == 'blackberry'){

            if (preg_match('!blackberry ?(\d+)!', $ua, $m)){
                $out['os_version'] = $m[1];
            }
        }

        if (is_null($out['os'])){
            if (preg_match('!mac os x (\d+)[._](\d+)([._](\d+))?!', $ua, $m)){
                $out['os'] = 'osx';
                $out['os_version'] = "$m[1].$m[2]";
                if ($m[4]) $out['os_version'] .= ".$m[4]";
            }
        }
        $out['ip'] = self::ip();

        return $out;
    }

    public static function header()
    {
        if (!function_exists('getallheaders')) {
            $headers = [];
            foreach ($_SERVER as $name => $value) {
                if (strtolower(substr($name, 0, 5)) == 'http_') {
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                }
            }
            return $headers;
        } else {
            return getallheaders();
        }
    }

    /**
     *  * 获取请求ip
     *  *
     *  * @return ip地址
     *  */
    public static function ip()
    {
        return preg_match('/[\d\.]{7,15}/', $_SERVER['REMOTE_ADDR'], $matches) ? $matches [0] : '';
    }

    public static function lang()
    {
        return strtolower(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2));
    }

    private static function _userAgentMatch($ua, $tokens){

        foreach ($tokens as $token){

            if (preg_match("!{$token}[/ ]([0-9.]+\+?)!", $ua, $m)){
                return array(
                    'token'		=> $token,
                    'version'	=> $m[1],
                );
            }

            if (preg_match("!$token!", $ua)){
                return array(
                    'token'		=> $token,
                    'version'	=> null,
                );
            }
        }

        return array(
            'token'		=> null,
            'version'	=> null,
        );
    }
}