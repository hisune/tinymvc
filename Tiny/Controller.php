<?php
/**
 * Created by hisune.com
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
            $helper = str_replace('\\Controller\\', '\\Helper\\', \Tiny\Request::$controller);
            switch($this->{$action}['type']){
                case 'theme':
                    $theme = '\\Tiny\\Theme\\' . $this->{$action}['name'];
                    $setting = lcfirst($method) . $this->{$action}['name'] . 'Setting';
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
                            if(Request::get('id')){
                                if($model->delete(Request::get('id'))){
                                    Error::echoJson(1);
                                }
                            }
                            Error::echoJson(-1);
                            break;
                        case 'Mod': // 添加修改
                            if(Request::isPost()){
                                $post = Request::post();
                                if($post){
                                    // save前置函数，用来改变post值
                                    $before = lcfirst($method) . 'ModBefore';
                                    if(method_exists($helper, $before)){
                                        $helper::$before($post);
                                    }
                                    $attribute = array_keys($model::attributes());
                                    foreach($post as $k => $v){
                                        if(in_array($k, $attribute)){
                                            if(is_array($v)) $v = json_encode($v);
                                            $model->$k = $v;
                                        }
                                    }

                                    if(isset($model->_data[$model->key]) && $model->_data[$model->key]){
                                        $result = $model->update();
                                    }else{
                                        $result = $model->save();
                                    }
                                    if($result)
                                        Error::echoJson('1', 'success');
                                    else
                                        Error::echoJson('1', 'save error');
                                }else
                                    Error::echoJson('-1', 'data error');
                            }else{
                                Error::echoJson('-1', 'method error');
                            }
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

    /**
     * Called before the controller method is run
     */
    public function initialize() {}

}