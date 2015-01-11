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
}