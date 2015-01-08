<?php
/**
 * Created by hisune.com.
 * User: hi@hisune.com
 * Date: 14-7-10
 * Time: 下午12:59
 */
namespace Tiny;

abstract class Controller
{

    public function __construct() {}

    /**
     * action builder，扩展builder需在类中有build统一执行方法, $setting成员属性
     * type: theme, action
     * name: theme:DataTable
     */
    public function __call($method, $args)
    {
        $action = 'action' . ucfirst($method);
        if(is_null($this->$action) || !isset($this->{$action}['type']) || !isset($this->{$action}['name']))
            Error::print404();
        else{
            $this->{$action}['name'] = ucfirst($this->{$action}['name']);
            switch($this->{$action}['type']){
                case 'theme':
                    $theme = '\\Tiny\\Theme\\' . $this->{$action}['name'];
                    $helper = str_replace('\\Controller\\', '\\Helper\\', \Tiny\Request::$controller);
                    $setting = 'get' . $this->{$action}['name'] . 'Setting';

                    $builder = new $theme();
                    $builder->setting = $helper::$setting();
                    $builder->build();
                    break;
                case 'action':
                    break;
                default:
                    $name = null;
                    Error::print404();
            }
        }
    }

    /**
     * view, request, validation用get自动实例化
     */
    public function __get($name)
    {
        switch($name) {
            case 'view':
            case 'request':
            case 'validation':
                static $vendor;

                if( empty($vendor[$name])){
                    $class = '\\Tiny\\' . ucfirst($name);
                    $vendor[$name] = new $class;
                }
                return $vendor[$name];

                break;
        }
    }

    /**
     * Called before the controller method is run
     */
    public function initialize() {}

}