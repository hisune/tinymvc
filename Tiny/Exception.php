<?php
/**
 * Created by hisune.com
 * User: 446127203@qq.com
 * Date: 14-7-11
 * Time: 上午11:08
 */
namespace Tiny;

class Exception
{

    public static function header()
    {
        headers_sent() OR header('HTTP/1.0 500 Internal Server Error');
    }

    public static function fatal()
    {
        if($e = error_get_last())
        {
            Exception::exception(new \ErrorException($e['message'], $e['type'], 0, $e['file'], $e['line']));
        }
    }

    public static function exception(\Exception $e)
    {
        $message = "{$e->getMessage()} [{$e->getFile()}] ({$e->getLine()})";

        try
        {
            Error::logMessage($message);
            self::header();

            Error::printException($e);
        }
        catch(\Exception $e)
        {
            print $message;
        }

        exit(1);
    }
}

// END
