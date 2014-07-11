<?php
/**
 * Created by hisune.com
 * User: 446127203@qq.com
 * Date: 14-7-11
 * Time: 上午10:20
 */
namespace Tiny;

class Helper
{
    public function roundRate($mol, $den, $percent = false, $precision = 2)
    {
        if($percent)
            return $den ? round($mol / $den * 100, $precision) . '%' : 0 . '%';
        else
            return $den ? round($mol / $den, $precision) : 0;
    }
}