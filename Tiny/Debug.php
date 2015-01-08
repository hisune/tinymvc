<?php
/**
 * Created by hisune.com.
 * User: hi@hisune.com
 * Date: 14-7-11
 * Time: 上午10:12
 */
namespace Tiny;

abstract class Debug
{
    static $html;

    public static function detail(array $addOn = array())
    {
        self::$html = new Html;

        $detail = self::_memoryUsage();
        $detail .= self::_executionTime();
        $detail .= self::_addOn($addOn);
        $detail .= self::_serverInfo('post', '$_POST Data');
        $detail .= self::_serverInfo('get', '$_GET Data');
        $detail .= self::_serverInfo('session', '$_SESSION Data');
        $detail .= self::_includedFiles();
        $detail .= self::_serverInfo('server', 'Server Info');

        echo self::$html->tag('div', $detail, array('style' => 'margin: 60px 0; padding:2em; background:#ECF5FA; color:#000; clear:both;'));
    }


    private static function _memoryUsage()
    {
        $helper = new Helper;
        $helper->roundRate(memory_get_usage() - START_MEMORY_USAGE, 1024);
        $return = '';

        $return .= self::$html->tag('b', 'Memory Usage');
        $detail = self::$html->tag(
            'p',
            $helper->roundRate(START_MEMORY_USAGE, 1024) . ' Kb (start)',
            array('style' => 'margin:0;padding:0;line-height:14px;')
        );
        $detail .= self::$html->tag(
            'p',
            $helper->roundRate(memory_get_usage(), 1024) . ' Kb (end)',
            array('style' => 'margin:0;padding:0;line-height:14px;')
        );
        $detail .= self::$html->tag(
            'p',
            $helper->roundRate(memory_get_usage() - START_MEMORY_USAGE, 1024) . ' KB (usage)',
            array('style' => 'margin:0;padding:0;line-height:14px;')
        );
        $detail .= self::$html->tag(
            'p',
            $helper->roundRate(memory_get_peak_usage(TRUE), 1024) . ' Kb (process peak)',
            array('style' => 'margin:0;padding:0;line-height:14px;')
        );
        $return .= self::$html->tag('pre', $detail);

        return $return;
    }

    private static function _executionTime()
    {
        $return = self::$html->tag('b', 'Execution Time');
        $return .= self::$html->tag(
            'pre',
            round((microtime(true) - START_TIME), 5) * 1000 .' ms'
        );

        return $return;
    }

    private static function _addOn($addOn)
    {
        $return = '';

        foreach($addOn as $detail){
            $return .= self::$html->tag('b', $detail['name']);
            if(is_string($detail['value'])){
                $return .= self::$html->tag('p', $detail['value']);
            }elseif(is_array($detail['value'])){
                ob_start();
                echo '<pre>';
                var_dump($detail['value']);
                echo '</pre>';
                $dump = ob_get_clean();
                $return .= self::$html->tag('p', $dump);
            }
        }

        return $return;
    }

    private static function _serverInfo($type, $info)
    {
        $return = '';
        $name = array();

        switch($type){
            case 'post':
                $name = $_POST;
                break;
            case 'get':
                $name = $_GET;
                break;
            case 'session':
                $name = $_SESSION;
                break;
            case 'cookie':
                $name = $_COOKIE;
                break;
            case 'server':
                $name = $_SERVER;
                break;
        }

        if(!empty($name)) {
            $return .= self::$html->tag('b', $info);
            ob_start();
            echo '<pre>';
            var_dump($name);
            echo '</pre>';
            $return .= ob_get_clean();
        }

        return $return;
    }

    private static function _includedFiles()
    {
        $included_files = get_included_files();
        $return = '';

        $return .= self::$html->tag('b', count($included_files) . ' PHP Files Included:');
        $files = '';
        foreach($included_files as $file) $files .= self::$html->tag('p', $file, array('style' => 'margin:0;padding:0;line-height:14px;'));
        $return .= self::$html->tag('pre', $files);

        return $return;
    }
}