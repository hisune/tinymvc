<?php
/**
 * Created by hisune.com
 * User: hi@hisune.com
 * Date: 14-7-11
 * Time: 上午11:08
 */
namespace Tiny;

class Exception
{

    public static function fatal()
    {
        if($e = error_get_last())
            Exception::exception(new \ErrorException($e['message'], $e['type'], 0, $e['file'], $e['line']));
    }

    public static function exception(\Exception $e, $addOn = null)
    {
        $message = "{$e->getMessage()} [{$e->getFile()}] (line {$e->getLine()})";
        $addOn && $message .= $addOn;

        try{
            Error::logMessage($message);

            Error::printException($e, $addOn);
        }catch(\Exception $e){
            if(Config::config()->show_error)
                print $message;
        }

        exit(1);
    }

}

// END
