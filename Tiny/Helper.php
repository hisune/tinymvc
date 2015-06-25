<?php
/**
 * Created by hisune.com
 * User: hi@hisune.com
 * Date: 14-7-11
 * Time: 上午10:20
 */
namespace Tiny;

class Helper
{
    public static function renderEnum(array $data, $key = 'id', $value = null)
    {
        $option = array();
        if ($data) {
            foreach ($data as $v) {
                if(is_object($v)) $v = (array)$v;
                if(is_object($v[$key])) $v[$key] = strval($v[$key]);
                if(is_string($value))
                    $option[$v[$key]] = $v[$value];
                else{
                    $option[$v[$key]] = new \ArrayObject($v, \ArrayObject::ARRAY_AS_PROPS);
                }
            }
        }
        return $option;
    }

    public static function roundRate($mol, $den, $percent = false, $precision = 2)
    {
        if($percent)
            return $den ? round($mol / $den * 100, $precision) . '%' : 0 . '%';
        else
            return $den ? round($mol / $den, $precision) : 0;
    }

    public static function backWithInputTips($input = null, $tips = null, $errors = null, $back = true)
    {
        $input && Session::set('input', $input);
        $tips && Session::set('tips', $tips);
        $errors && Session::set('errors', $errors);
        if($back){
            if(is_string($back))
                Url::back($back);
            else
                Url::back();
        }
    }

    public static function getTree($items, $parent = 'parent', $child = 'child')
    {
        if(!$items) return array();

        $newItems = array();
        foreach ($items as $item)
            $newItems[$item->id] = (array)$item;
        foreach ($newItems as $item)
            $newItems[$item[$parent]][$child][$item['id']] = & $newItems[$item['id']];

        return isset($newItems['0'][$child]) ? $newItems['0'][$child] : array();
    }

    public static function getHumanTree(array $tree = [], $level = 0, $child = 'child', $name = 'name', $string = '--|')
    {
        static $array;

        if(!$tree) return [];
        foreach($tree as $k => $v){
            $array[$k] = str_repeat($string, $level) . ' ' . $v[$name];
            if(isset($v[$child]) && $v[$child])
                self::getHumanTree($v[$child], $level + 1);
        }
        return $array;
    }

    public static function mongoType($type, $data)
    {
        switch($type){
            case 'boolean':
                $data = $data ? true : false;
                break;
            case 'int32':
                $data = intval($data);
                break;
            case 'int64':
                $data = new \MongoInt64($data);
                break;
            case 'time':
                $data = strtotime($data);
                break;
            case 'date':
                $data = new \MongoDate($data);
                break;
            default:
                $data = strval($data);
        }

        return $data;
    }

    // 驼峰与下划线的相互转化
    public static function parseName($name, $type = 0)
    {
        if ($type) {
            return ucfirst(preg_replace_callback('/_([a-zA-Z])/', function ($match) {
                return strtoupper($match[1]);
            }, $name));
        } else {
            return strtolower(trim(preg_replace('/[A-Z]/', '_\\0', $name), '_'));
        }
    }

}