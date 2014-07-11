<?php
/**
 * Created by hisune.com
 * User: 446127203@qq.com
 * Date: 14-7-10
 * Time: 下午12:59
 */
namespace Tiny;

abstract class Controller
{

    public function __construct() {}

    public function __get($name)
    {
        switch($name) {
            case 'view':
                static $view;
                empty($view) && $view = new \Tiny\View ;
                return $view;

                break;
        }
    }

    /**
     * Called before the controller method is run
     */
    public function initialize() {}

    /**
     * Called after the controller method is run to send the response
     */
    public function send() {}

}