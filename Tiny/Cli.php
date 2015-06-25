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

    public function __construct() {
        if(php_sapi_name() != 'cli')
            Error::printError('sapi forbidden');
    }

    public function initialize() {}

    protected function renderDay($date = null)
    {
        if(!$date){
            $this->dayEnd = mktime(0, 0, 0);
            $this->dayStart = $this->dayEnd - 86400;
        }else{
            $this->dayStart = strtotime($date);
            $this->dayEnd = $this->dayStart + 86400;
            if($this->dayStart < 1000000000 || date('His', $this->dayStart) != '000000'){
                Error::echoJson(-1, 'day format error');
            }
        }
    }

}