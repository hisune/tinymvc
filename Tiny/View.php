<?php
/**
 * Created by hisune.com
 * User: 446127203@qq.com
 * Date: 14-7-10
 * Time: 下午5:35
 *
 * 只有主动调用才加载，节省内存
 */
namespace Tiny;

class View
{
    public $tplVar = array(); // 模板变量

    /**
     * 模板赋值
     */
    public function assign($name, $value = '')
    {
        $this->tplVar[$name] = $value;
    }

    /**
     * 模板显示
     * 无需指定目录，自动根据action名查找对应目录下的文件，如需指定目录，请传第二个参数
     */
    public function display($tpl)
    {
        foreach ($this->tplVar as $k => $v) $$k = $v;

        $file = PUBLIC_DIR . '/../app/view/' . $tpl . '.php';
        if (file_exists($file)) {
            ob_start();
            require $file;
            $contents = ob_get_contents();
            ob_end_clean();
            echo $contents;
        } else {
            die('模板文件' . ': ' . $tpl . '.php '. '不存在');
        }
    }

    /**
     * 模板文件中包含其他模板文件方法
     */
    public function includeTpl($tpl)
    {
        foreach ($this->tplVar as $k => $v) $$k = $v;

        require(PUBLIC_DIR . '/../app/view/' . $tpl . '.php');
    }
}