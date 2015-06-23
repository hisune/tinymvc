<?php
/**
 * Created by hisune.com.
 * User: hi@hisune.com
 * Date: 2014/9/30 0030
 * Time: 下午 3:12
 */
namespace Tiny\Theme;

use Tiny as tiny;

class Tabs implements tiny\ThemeBuilder
{
    public $setting = array(); // 配置数组
    public $id = 'tabs'; // 唯一id
    public $html = ''; // html内容
    public $action; // action名
    public $option; // option

    public function __construct($action, $option)
    {
        $this->action = $action;
        $this->option = $option;
    }

    public function build()
    {
        if($this->setting && isset($this->setting['tabs'])){
            // 组装html
            $this->_renderHtml();
            // 输出html
            $this->_show();
        }else
            echo 'tabs 配置有误';
    }

    private function _renderHtml()
    {
        // id
        if(isset($this->setting['id']) && $this->setting['id'])
            $this->id = $this->setting['id'];
        else
            $this->id = 'tabs-' . mt_rand(1, 250);
        // tabs内容
        foreach($this->setting['tabs'] as $k => $v){
            $active = $k == 0 ? 'active' : '';
            $this->html .= tiny\Html::tag(
                'li',
                "<a data-toggle='tab'>{$v['title']}</a>",
                array(
                    'class'    => "tabs-li {$active}",
                    'url'      => tiny\Url::get($v['url']),
                    'original' => tiny\Url::get($v['url']),
                )
            );
        }
        // tabs外层ul
        $this->html = tiny\Html::tag(
            'ul',
            $this->html,
            array(
                'class' => 'nav nav-tabs',
                'id'    => $this->id,
            )
        );
        // ajax内容
        $this->html .= tiny\Html::tag(
            'div',
            "<div class='tab-pane active' id='{$this->id}-home'></div>",
            array(
                'class' => 'tab-content',
            )
        );
        // js
        $this->html .= tiny\Html::tag(
            'script',
            $this->_js()
        );
    }

    private function _show()
    {
//        // 临时引入
//        echo '<script src="http://cdn.bootcss.com/jquery/2.1.1/jquery.min.js"></script>';
//        echo '<link href="http://cdn.bootcss.com/bootstrap/3.2.0/css/bootstrap.min.css" rel="stylesheet">';
//        echo '<link href="http://cdn.bootcss.com/bootstrap/3.2.0/css/bootstrap-theme.min.css" rel="stylesheet">';
//        echo '<script src="http://cdn.bootcss.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>';


        echo $this->html;
    }

    private function _js()
    {
        if(!isset($this->setting['js']) || !$this->setting['js']) $this->setting['js'] = '';
        $loading = tiny\Html::loading();

        return <<<JS
\$(document).ready(function () {
    var tabs_home = $('#{$this->id}-home');
    \$('#{$this->id} > .tabs-li').click(function () {
        \$.ajax({
            beforeSend: function () {
                tabs_home.html('{$loading}');
            },
            url: $(this).attr('url'),
            timeout: 20000,
            error: function () {
                tabs_home.html("<div class='alert alert-danger width-350'>" +
                    "o(╯□╰)o<br /><?php echo t('加载超时或出错了'); ?>...</div>");
            },
            success: function (ret) {
                tabs_home.html(ret);
            }
        });
    });
    \$('#{$this->id} > .active').click();
    {$this->setting['js']}
});
JS;

    }

}