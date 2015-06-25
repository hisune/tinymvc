<?php
/**
 * Created by Hisune.
 * User: hi@hisune.com
 * Date: 2015/6/18
 * Time: 18:17
 */
namespace Tiny\Theme;

use Tiny as tiny;

class Mod implements tiny\ThemeBuilder
{
    public $setting = array(); // 配置数组
    public $id = 'tabs'; // 唯一id
    public $html = ''; // html内容
    private $js = '';
    private $pkId = null; // model pk id
    private $helper = false; //helper
    private $model = false; // model
    private $post = array();
    private $data = array(); // model data
    private $defaultSingleSelect = array( // 默认的single select配置
        'enableFiltering' => false,
        'maxHeight' => 400,
        'checkboxName' => ''
    );
    private $defaultMultiSelect = array( // 默认的multi select配置
        'includeSelectAllOption' => true,
        'enableFiltering' => false,
        'numberDisplayed' => 5,
        'maxHeight' => 400,
        'checkboxName' => ''
    );
    private $defaultDate = array( // 默认的daterangepicker配置
        'showDropdowns' => true,
        'singleDatePicker' => true,
        'timePicker' => true,
        'format' => 'YYYY-MM-DD HH:mm'
    );
    public $action; // action名
    public $option; // option

    public function __construct($action, $option)
    {
        $this->action = $action;
        $this->option = $option;
        if(!isset($this->option['action']))
            exit('form action not set');
    }

    public function build()
    {
        if($this->setting && isset($this->setting['mod'])){
            if(isset($this->setting['js']) && $this->setting['js']){
                $this->js = $this->setting['js'];
            }
            foreach($this->setting['mod'] as $k => $v){
                $this->setting['mod'][$v['name']] = $v;
                unset($this->setting['mod'][$k]);
            }
            $this->_renderModel();
            if(tiny\Request::isPost()){
                $this->post = tiny\Request::post();
                $this->_renderPost();
            }else{
                // 组装html
                $this->_renderHtml();
                // 输出html
                $this->_show();
            }
        }else
            echo 'mod setting error';
    }

    private function _renderModel()
    {
        // model相关
        $model =
            isset($this->setting['model']) ?
                ucfirst(\Tiny\Config::$application) . '\\Model\\' . ucfirst($this->setting['model']) :
                str_replace('\\Controller\\', '\\Model\\', \Tiny\Request::$controller);
        if(class_exists($model)) {
            $this->model = new $model;
        }
        $this->helper = str_replace('\\Controller\\', '\\Helper\\', \Tiny\Request::$controller);
    }

    private function _renderHtml()
    {

        if($this->model){
            $pk = $this->model->key;
        }else
            $pk = 'id';

        $this->pkId = tiny\Request::get($pk);
        if($this->pkId) {
            $this->data = (object)$this->model->findOne($this->pkId);
            $helper = $this->helper;
            // 是否有前置处理函数
            $call = lcfirst($this->action) . 'ModDisplayBefore';
            if(method_exists($this->helper, $call)){
                $helper::$call($this->data);
            }
        }
        // id
        if(isset($this->setting['id']) && $this->setting['id'])
            $this->id = $this->setting['id'];
        else
            $this->id = 'tabs-' . mt_rand(1, 250);

        // mod头
        $this->html = tiny\Html::tag(
            'div',
            '<h3 class="box-title">' . ($this->pkId ? 'Modify a old record' : 'Add a new record') . '</h3>',
            array(
                'class' => 'box-header with-border',
            )
        );
        // mod内容
        $this->html .= tiny\Html::tag(
            'div',
            $this->_form(),
            array(
                'class' => 'box-body form-horizontal',
            )
        );
        // mod尾
        $reset = tiny\Html::tag('button', 'Reset', array('type' => 'reset', 'class' => 'btn btn-default', 'style' => 'float: right', 'id' => 'reset-' . $this->id));
        $submit = tiny\Html::tag('button', 'Submit', array('type' => 'submit', 'class' => 'btn btn-primary'));
        $this->html .= tiny\Html::tag(
            'div',
            $reset . $submit,
            array(
                'class' => 'box-footer clearfix',
            )
        );
        // mod包裹层
        $this->html = tiny\Html::tag(
            'div',
            $this->html,
            array(
                'class' => 'box box-success',
            )
        );
        // form
        $this->html = tiny\Html::tag(
            'form',
            $this->html,
            array(
                'method' => 'post',
                'action' => tiny\Url::get($this->option['action']),
                'id' => $this->id,
                'onsubmit' => isset($this->setting['onsubmit']) ? $this->setting['onsubmit'] : 'return modCheck();'
            )
        );
    }

    private function _form()
    {
        $html = '';

        foreach($this->setting['mod'] as $mod){
            $class = 'mod-' . $mod['name'];
            $fullClass = 'form-control ' . $class;
            $disabled = isset($mod['disabled']) && $mod['disabled'] ? 'disabled' : ''; // disabled
            $attribute = array('class' => $fullClass, 'name' => $mod['name'], 'placeholder' => $mod['title']);
            $disabled && $attribute['disabled'] = true;
            $display = isset($mod['display']) && !$mod['display'] ? 'hidden' : '';// display
            $required = isset($mod['required']) && $mod['required'] ? true : false;// required
            if($required) $attribute['class'] .= ' required';
            $tips = isset($mod['tips']) && $mod['tips'] ? $mod['tips'] : false;// tips
            $tipsHtml = $tips ? tiny\Html::tag('i', '', array('class' => 'glyphicon glyphicon-question-sign', 'data-toggle' => 'tooltip', 'data-placement' => 'top', 'data-original-title' => $tips)) : '';
            $line = tiny\Html::tag('label', $mod['title']  . ' ' . $tipsHtml, array('class' => 'col-sm-2 control-label'));
            if(isset($mod['attribute']))// attribute
                $attribute = array_merge($attribute, $mod['attribute']);
            switch($mod['type']){
                case 'input': // name, title, default
                    $default = $this->_renderDefault($mod);
                    if($default !== null) $attribute['value'] = $default;
                    $center = tiny\Html::tag(
                        'input',
                        '',
                        $attribute
                    );
                    break;
                case 'textarea': // name, title, rows, default
                    $default = $this->_renderDefault($mod);
                    $attribute['rows'] = isset($mod['rows']) ? $mod['rows'] : 3;
                    $center = tiny\Html::tag(
                        'textarea',
                        $default,
                        $attribute
                    );
                    break;
                case 'select': // name, title, option, default, multiOption
                    $default = $this->_renderDefault($mod);
                    $optionHtml = '';
                    if(isset($mod['option'])){
                        foreach($mod['option'] as $key => $option){
                            $optionAttribute = array('value' => $key);
                            if($default == $key) $optionAttribute['selected'] = true;
                            $optionHtml .= tiny\Html::tag('option', $option, $optionAttribute);
                        }
                    }
                    $center = tiny\Html::tag('select', $optionHtml, $attribute);

                    $multiOption = isset($mod['multiOption']) ? array_merge($this->defaultSingleSelect, $mod['multiOption']) : $this->defaultSingleSelect;
                    $this->js .= '$(".' . $class . '").multiselect(' . json_encode($multiOption) . ');';
                    break;
                case 'multiselect': // name, title, option, default, multiOption   default is a json string
                    $default = $this->_renderDefault($mod);
                    $default = $default ? json_decode($default, true) : array();
                    $attribute['multiple'] = 'multiple';
                    $attribute['name'] = $mod['name'] . '[]';
                    $optionHtml = '';
                    if(isset($mod['option'])) {
                        foreach ($mod['option'] as $key => $option) {
                            $optionAttribute = array('value' => $key);
                            if (in_array($key, $default)) $optionAttribute['selected'] = true;
                            $optionHtml .= tiny\Html::tag('option', $option, $optionAttribute);
                        }
                    }
                    $center = tiny\Html::tag('select', $optionHtml, $attribute);

                    $multiOption = isset($mod['multiOption']) ? array_merge($this->defaultMultiSelect, $mod['multiOption']) : $this->defaultMultiSelect;
                    $this->js .= '$(".' . $class . '").multiselect(' . json_encode($multiOption) . ');';
                    break;
                case 'date': // name, title, dateOption, default
                    $default = $this->_renderDefault($mod);
                    if($default !== null) $attribute['value'] = $default;
                    $attribute['style'] = 'max-width: 300px;';
                    $center = tiny\Html::tag(
                        'input',
                        '',
                        $attribute
                    );

                    $dateOption = isset($mod['dateOption']) ? array_merge($this->defaultDate, $mod['dateOption']) : $this->defaultDate;
                    $this->js .= '$(".' . $class . '").daterangepicker(' . json_encode($dateOption) . ')';
                    break;
                case 'password': // name, title, default
                    $default = $this->_renderDefault($mod);
                    if($default !== null) $attribute['value'] = $default;
                    $attribute['type'] = 'password';
                    $center = tiny\Html::tag(
                        'input',
                        '',
                        $attribute
                    );
                    break;
                case 'radio': // name, title, option, default
                    $default = $this->_renderDefault($mod);
                    $center = '';
                    $attribute['type'] = 'radio';
                    $attribute['class'] = 'mod-' . $mod['name'];
                    if($required) $attribute['class'] .= ' required';
                    $attribute['name'] = $mod['name'] . '[]';
                    if(isset($mod['option'])){
                        foreach($mod['option'] as $key => $option){
                            if($key == $default) $attribute['checked'] = true;
                            else unset($attribute['checked']);
                            $attribute['value'] = $key;
                            $inputHtml = tiny\Html::tag('input', $option, $attribute) . ' ' . $option;
                            $center .= tiny\Html::tag('label', $inputHtml, array('class' => 'radio-inline'));
                        }
                    }
                    break;
                case 'checkbox': // name, title, option, default    default is a json string
                    $default = $this->_renderDefault($mod);
                    $default = $default ? json_decode($default, true) : array();
                    $center = '';
                    $attribute['type'] = 'checkbox';
                    $attribute['class'] = 'mod-' . $mod['name'];
                    if($required) $attribute['class'] .= ' required';
                    $attribute['name'] = $mod['name'] . '[]';
                    if(isset($mod['option'])) {
                        foreach ($mod['option'] as $key => $option) {
                            if (in_array($key, $default)) $attribute['checked'] = true;
                            else unset($attribute['checked']);
                            $attribute['value'] = $key;
                            $inputHtml = tiny\Html::tag('input', $option, $attribute) . ' ' . $option;
                            $center .= tiny\Html::tag('label', $inputHtml, array('class' => 'checkbox-inline'));
                        }
                    }
                    break;
                case 'hidden': // name, title
                    $default = $this->_renderDefault($mod);
                    if($default !== null) $attribute['value'] = $default;
                    $attribute['type'] = 'hidden';
                    $center = tiny\Html::tag(
                        'input',
                        '',
                        $attribute
                    );
                    break;
                default:
                    $center = '';
            }
            $line .= tiny\Html::tag('div', $center, array('class' => 'col-sm-' . (isset($mod['col']) ? $mod['col'] : 8)));
            $requiredHtml = $required ? tiny\Html::tag('label', '*', array('class' => 'control-label', 'style' => 'color: #ff0000;')) : '';
            $line .= tiny\Html::tag('div', $requiredHtml, array('class' => 'col-sm-2')); // tips
            $html .= tiny\Html::tag('div', $line, array('class' => 'form-group ' . $display)); // form-group
        }

        return $html;
    }

    private function _renderPost()
    {
        // save前置函数，用来改变post值
        $before = lcfirst($this->action) . 'ModPostBefore';
        $helper = $this->helper;
        if(method_exists($this->helper, $before)){
            $helper::$before($this->post);
        }
        $model = $this->model;
        $attribute = array_keys($model::attributes());
        foreach($this->post as $k => $v){
            if(in_array($k, $attribute)){
                if(is_array($v)) $v = json_encode($v);
                if($this->model->type == 'mongodb'){
                    if(isset($this->setting['mod'][$k]['field_type'])){
                        $v = tiny\Helper::mongoType($this->setting['mod'][$k]['field_type'], $v);
                    }
                }
                $this->model->$k = $v;
            }
        }

        if(isset($this->model->_data[$this->model->key]) && $this->model->_data[$this->model->key]){
            $result = $this->model->update();
        }else{
            if(isset($this->model->_data[$this->model->key])){
                unset($this->model->_data[$this->model->key]);
            }
            $result = $this->model->save();
        }
        if($result)
            tiny\Error::echoJson('1', 'success');
        else
            tiny\Error::echoJson('1', 'save error');
    }

    /**
     * 获取默认值
     * value的优先级最高，其次是id的model值，最后是default
     */
    private function _renderDefault($mod)
    {
        if(isset($mod['value'])){
            return $mod['value'];
        }elseif($this->data && isset($this->data->$mod['name'])){
            return $this->data->$mod['name'];
        }elseif(isset($mod['default']))
            return $mod['default'];
        else
            return null;
    }

    private function _show()
    {
        echo $this->html;
        echo $this->_js();
    }

    private function _js()
    {
        $id = $this->id;
        $url = $this->option['action'];
        if($this->model){
            $end = '';
        }else{
            $end = '$("#reset-' . $id . '").click();';
        }
        $js = <<<JS
function modCheck()
{
    \$.ajax({
        beforeSend: function(){
            var go = true;
            $('#$id .required').each(function(){
                if($(this).is('input') || $(this).is('textarea')){
                    if($(this).attr('type') != 'checkbox' && $(this).val() == ''){
                        bootbox.alert(\$(this).attr('placeholder') + ' required');
                        go = false;
                    }
                }
            });
            return go;
        },
        url: '$url',
        type: 'post',
        data: \$('#$id').serialize(),
        dataType: 'json',
        success: function(response){
            if(response.ret == 1){
                $end
            }
            bootbox.alert(response.msg);
        }
    });
    return false;
}
JS;

        return '<script>' . $js . $this->js . '</script>';
    }

}