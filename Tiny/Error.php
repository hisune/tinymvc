<?php
/**
 * Created by hisune.com
 * User: 446127203@qq.com
 * Date: 14-7-11
 * Time: 上午11:14
 */
namespace Tiny;

class Error
{
    public static function logMessage($message)
    {
        $path = PUBLIC_DIR . '/../var/';
        if(!file_exists($path)) mkdir($path);
        $path .= 'Log/';
        if(!file_exists($path)) mkdir($path);
        $path .= date('Y-m-d') . '.log';

        // Append date and IP to log message
        return error_log(date('H:i:s ') . getenv('REMOTE_ADDR') . " $message\n", 3, $path);
    }

    public static function printException($e)
    {
        $detail = Html::tag('b', get_class($e), array('style' => 'color: #990000'));
        if(Config::config()->show_error == 'on'){
            $detail .= Html::tag('p', $e->getMessage());
            $detail .= Html::tag('p', Html::tag('b', $e->getFile()) . '(' . $e->getLine() . ')');
        }else
            $detail .= Html::tag('p', 'Oops, there are some errors happened!');
        self::printError($detail);
    }

    public static function printError($detail)
    {
        echo Html::tag('div', $detail, array('style' => 'border:1px solid #990000;	padding:10px 20px;margin:10px;font: 13px/1.4em verdana;background: #fff;'));
        exit;
    }

}