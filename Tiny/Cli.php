<?php
/**
 * Created by Hisune.
 * User: hi@hisune.com
 * Date: 2015/6/24
 * Time: 11:09
 */
namespace Tiny;

abstract class Cli
{
    protected $dayStart;
    protected $dayEnd;

    public function __construct() {}

    public function initialize() {}

    protected function renderDay($date = null)
    {
        if(!$date){
            $this->dayEnd = mktime(0, 0, 0);
            $this->dayStart = $this->dayEnd - 86400;
        }else{
            $this->dayStart = strtotime($date);
            $this->dayEnd = $this->dayStart + 86400;
            if($this->dayStart < 1000000000 || $this->dayStart % 86400 != 0){
                Error::echoJson(-1, 'day format error');
            }
        }
    }

}