<?php
/**
 * Created by hisune.com
 * User: hi@hisune.com
 * Date: 14-7-10
 * Time: 下午12:59
 */

namespace Tiny;

/**
 * @property \Tiny\View $view
 * @property \Tiny\Request $request
 * @property \Tiny\Validation $validation
 */
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
            $helper = str_replace('\\Controller\\', '\\Helper\\', \Tiny\Request::$controller);
            switch($this->{$action}['type']){
                case 'theme':
                    $theme = '\\Tiny\\Theme\\' . $this->{$action}['name'];
                    $setting = $this->_themeSetting($method, $action);
                    $option = isset($this->{$action}['option']) ? $this->{$action}['option'] : array();

                    $builder = new $theme(lcfirst($method), $option);
                    $builder->setting = $helper::$setting();
                    $builder->build();
                    break;
                case 'action':
                    $model =
                        isset($this->{$action}['model']) ?
                            ucfirst(\Tiny\Config::$application) . '\\Model\\' . ucfirst($this->{$action}['model']) :
                            str_replace('\\Controller\\', '\\Model\\', \Tiny\Request::$controller);
                    $model = new $model;

                    switch($this->{$action}['name']){
                        case 'Delete': // 删除
                            if(Request::get($model->key)){
                                if($model->delete(Request::get($model->key))){
                                    Error::echoJson(1);
                                }
                            }
                            Error::echoJson(-1);
                            break;
                    }
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
                    $vendor[$name] = new $class();
                }
                return $vendor[$name];

                break;
        }
    }

    private function _themeSetting($method, $action)
    {
        return lcfirst($method) . $this->{$action}['name'] . 'Setting';
    }

    /**
     * Called before the controller method is run
     */
    public function initialize() {}

}