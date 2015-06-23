<?php
/**
 * Created by hisune.com
 * User: hi@hisune.com
 * Date: 14-7-11
 * Time: 下午4:06
 *
 * from micromvc
 */
namespace Tiny;

class Cookie
{

    public static $settings = array(
        'expires' => null,
        'path' => '/',
        'domain' => null,
        'secure' => null,
        'httponly' => null,
    );

    /**
     * Decrypt and fetch cookie data
     *
     * @param string $name of cookie
     * @param array $config settings
     * @return mixed
     */
    public static function get($name, $config = NULL)
    {
        if(isset($_COOKIE[$name]))
        {
            // Decrypt cookie using cookie key
            if($v = json_decode(Cipher::decrypt(base64_decode($_COOKIE[$name]), Config::config()->key), true))
            {
                return $v;
            }
        }

        return FALSE;
    }


    /**
     * Called before any output is sent to create an encrypted cookie with the given value.
     *
     * @param $name
     * @param mixed $value to save
     * @param array $config settings
     * return boolean
     * @internal param string $key cookie name
     */
    public static function set($name, $value, $config = array())
    {
        // Use default config settings if needed
        extract(array_merge(static::$settings, $config));

        // If the cookie is being removed we want it left blank
        $value = $value ? base64_encode(Cipher::encrypt(json_encode($value), Config::config()->key)) : null;

        // Save cookie to user agent
        setcookie($name, $value, $expires, $path, $domain, $secure, $httponly);
    }

}

// END
