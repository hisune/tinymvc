<?php
/**
 * Created by hisune.com
 * User: hi@hisune.com
 * Date: 14-7-10
 * Time: 下午5:35
 *
 * 只有主动调用才加载，节省内存
 */
namespace Tiny;

/**
 * input 、 errors 和 tips 是内部变量
 */
class View
{
    public $layout = 'layout';
    public static $content;
    public $tplVar = array(); // 模板变量
    protected $tokenName = '__hash__';

    public function __construct()
    {
        if(Session::get('input'))
            $this->tplVar['input'] = Session::get('input');

        if(Session::get('errors'))
            $this->tplVar['errors'] = Session::get('errors');

        if(Session::get('tips'))
            $this->tplVar['tips'] = Session::get('tips');
    }

    /**
     * 模板赋值
     */
    public function assign($name, $value = '')
    {
        $this->tplVar[$name] = $value;
    }

    /**
     * 模板显示
     * 指定layout的情况下使用子模板模式，不指定直接显示当前模板
     */
    public function display($tpl)
    {
        if($this->layout){
            self::$content = $this->_getContent($tpl);
            $content = $this->_getContent($this->layout);
        }else
            $content = $this->_getContent($tpl);
        $this->addToken($content);
        echo $content;
    }

    private function _getContent($tpl)
    {
        foreach ($this->tplVar as $k => $v) $$k = $v;

        $file = Config::$viewDir . $tpl . '.php';
        if (file_exists($file)) {
            ob_start();
            require $file;
            $content = ob_get_contents();
            ob_end_clean();
            return $content;
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

        require(Config::$viewDir . $tpl . '.php');
    }

    public function addToken(&$content)
    {
        if (Config::config()->token) {
            if (strpos($content, '{__TOKEN__}'))
                $content = str_replace('{__TOKEN__}', $this->buildToken(), $content); // 指定表单令牌隐藏域位置
            elseif (preg_match('/<\/form(\s*)>/is', $content, $match))
                $content = str_replace($match[0], $this->buildToken() . $match[0], $content); // 智能生成表单令牌隐藏域
        }
    }

    // 创建表单令牌
    private function buildToken()
    {
        if (!Session::get($this->tokenName))
            Session::set($this->tokenName, array());

        $tokenKey = md5($_SERVER['REQUEST_URI']);
        $tName = Session::get($this->tokenName);
        if (isset($tName[$tokenKey])) // 相同页面不重复生成session
            $tokenValue = $tName[$tokenKey];
        else {
            $tokenValue = md5(microtime(TRUE));
            Session::set($this->tokenName, array($tokenKey => $tokenValue), true);
        }
        $token = '<input type="hidden" name="' . $this->tokenName . '" value="' . $tokenKey . '_' . $tokenValue . '" />';
        return $token;
    }

    public static function getContent()
    {
        return self::$content;
    }

    public static function publicDir()
    {
        return '//' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
    }

    public static function script($name, $cdn = false, $dir = 'asset')
    {
        return Html::tag(
            'script',
            '',
            array(
                'src' => self::srcUri($name, $cdn, $dir),
                'type' => 'text/javascript'
            )
        );
    }

    public static function style($name, $cdn = false, $dir = 'asset')
    {
        return Html::tag(
            'link',
            '',
            array(
                'rel' => 'stylesheet',
                'type' => 'text/css',
                'href' => self::srcUri($name, $cdn, $dir)
            )
        );
    }

    private static function srcUri($name, $cdn, $dir)
    {
        return $cdn ? $name : self::assetsUrl($name, $dir);
    }

    private static function assetsUrl($name, $dir)
    {
        return self::publicDir() . '/' . $dir . '/' . $name;
    }

    public function __destruct()
    {
        if(isset($this->tplVar['input'])){
            unset($this->tplVar['input']);
            Session::set('input', null);
        }
        if(isset($this->tplVar['errors'])){
            unset($this->tplVar['errors']);
            Session::set('errors', null);
        }
        if(isset($this->tplVar['tips'])){
            unset($this->tplVar['tips']);
            Session::set('tips', null);
        }
    }
}