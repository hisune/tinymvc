<?php
/**
 * Created by hisune.com.
 * User: hi@hisune.com
 * Date: 14-7-11
 * Time: 上午11:14
 */
namespace Tiny;

class Error
{
    public static function logMessage($message)
    {
        $path = Config::$varDir;
        if(!file_exists($path)) mkdir($path);
        $path .= 'Log/';
        if(!file_exists($path)) mkdir($path);
        $path .= date('Y-m-d') . '.log';

        // Append date and IP to log message
        return error_log(date('H:i:s ') . getenv('REMOTE_ADDR') . " $message\n", 3, $path);
    }

    public static function printException($e, $addOn = null)
    {
        $detail = Html::tag('b', get_class($e), array('style' => 'color: #990000'));
        if(Config::config()->show_error){
            $detail .= Html::tag('p', $e->getMessage());
            $detail .= Html::tag('p', Html::tag('b', $e->getFile()) . '(line ' . $e->getLine() . ')');
            $addOn && $detail .= HTML::tag('p', $addOn);
            self::printError($detail);
        }
//        else
//            $detail .= Html::tag('p', 'Oops, there are some errors happened!');
//        self::printError($detail);
    }

    public static function printError($detail)
    {
        echo Html::tag(
            'div',
            $detail,
            array(
                'style' => 'color: #a94442;background-color: #f2dede;border-color: #ebccd1;margin:25px;padding: 25px;border: 1px solid #ebccd1;border-radius: 4px;'
            )
        );
        exit;
    }

    public static function print404()
    {
        echo 'Ooooops, page not found!';
    }

    public static function echoJson($ret, $msg, $exit = true)
    {
        echo json_encode(array('ret' => $ret, 'msg' => $msg));
        $exit && exit;
    }

}