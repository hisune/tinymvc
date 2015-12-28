<?php
/**
 * Created by hisune.com
 * User: hi@hisune.com
 * Date: 14-7-11
 * Time: 上午11:14
 */
namespace Tiny;

class Error
{
    const DEFAULT_LOG_DIR = 'Log';

    public static function logMessage($message, $logDir = true, $time = null)
    {
        $logDir = is_string($logDir) ? $logDir : self::DEFAULT_LOG_DIR;
        $path = Config::$varDir;
        if(!file_exists($path)) mkdir($path);
        $path .= $logDir . '/';
        if(!file_exists($path)) mkdir($path);
        $path .= date('Y-m-d') . '.log';

        if(is_array($message)){
            foreach($message as $k => $v)
                if(is_array($v))
                    $message[$k] = json_encode($v);

            $message = implode("\t", $message);
        }

        // Append date and IP to log message
        return error_log(date('Y-m-d H:i:s', $time) . "\t" . getenv('REMOTE_ADDR') . "\t{$message}\n", 3, $path);
    }

    public static function printException($e, $addOn = null)
    {
        $detail = Html::tag('b', get_class($e), array('style' => 'color: #990000'));
        if(Config::config()->show_error){
            headers_sent() OR header('HTTP/1.0 500 Internal Server Error');
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

    public static function echoJson($status, $data = '', $exit = true, $writeLog = false, $writeMessage = null)
    {
        headers_sent() || header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('status' => $status, 'data' => $data));
        if($writeLog){
            if(!$writeMessage)
                $writeMessage = ['GET' => Request::get(), 'POST' => Request::post()];
            static::logMessage($writeMessage, $writeLog);
        }
        $exit && exit;
    }

}