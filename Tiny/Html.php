<?php
/**
 * Created by hisune.com.
 * User: hi@hisune.com
 * Date: 14-7-11
 * Time: 上午10:13
 */
namespace Tiny;

class Html
{

    /**
     * Create a gravatar <img> tag
     *
     * @param $email the users email address
     * @param $size    the size of the image
     * @param $alt the alt text
     * @param $type the default image type to show
     * @param $rating max image rating allowed
     * @return string
     */
    public static function gravatar($email = '', $class = '', $size = 80, $alt = 'Gravatar', $type = 'wavatar', $rating = 'g')
    {
        return '<img class="' . $class . '" src="http://www.gravatar.com/avatar/' . md5($email) . "?s=$size&d=$type&r=$rating\" alt=\"$alt\" />";
    }


    /**
     * Compiles an array of HTML attributes into an attribute string and
     * HTML escape it to prevent malformed (but not malicious) data.
     *
     * @param array $attributes the tag's attribute list
     * @return string
     */
    public static function attributes(array $attributes = NULL)
    {
        if (!$attributes) return;

        asort($attributes);
        $h = '';
        foreach ($attributes as $k => $v) {
            $h .= " $k=\"" . self::h($v) . '"';
        }
        return $h;
    }

    public static function h($string)
    {
        return htmlspecialchars($string, ENT_QUOTES, 'utf-8');
    }

    /**
     * Create an HTML tag
     *
     * @param string $tag the tag name
     * @param string $text the text to insert between the tags
     * @param array $attributes of additional tag settings
     * @return string
     */
    public static function tag($tag, $text = '', array $attributes = NULL)
    {
        $autoClose = array('input', 'br', 'hr', 'img', 'col', 'area', 'base', 'link', 'meta', 'frame', 'param');
        return "<$tag" . self::attributes($attributes) . (in_array($tag, $autoClose) ? ' />' : ">$text</$tag>");
    }

    /**
     * Auto creates a form select dropdown from the options given .
     *
     * @param string $name the select element name
     * @param array $options the select options
     * @param mixed $selected the selected options(s)
     * @param array $attributes of additional tag settings
     * @return string
     */
    public static function select($name, array $options, $selected = NULL, array $attributes = NULL)
    {
        $h = '';
        foreach ($options as $k => $v) {
            $a = array('value' => $k);

            // Is this element one of the selected options?
            if ($selected AND in_array($k, (array)$selected)) {
                $a['selected'] = 'selected';
            }

            $h .= self::tag('option', $v, $a);
        }

        if (!$attributes) {
            $attributes = array();
        }

        return self::tag('select', $h, $attributes + array('name' => $name));
    }

    public static function loading()
    {
        $html = self::tag(
            'div',
            '',
            array(
                'class'         => 'progress-bar',
                'role'          => 'progressbar',
                'aria-valuenow' => '100',
                'aria-valuemin' => '0',
                'aria-valuemax' => '100',
                'style'         => 'width: 100%',
            )
        );
        $html = self::tag(
            'div',
            $html,
            array(
                'class' => 'progress progress-striped active',
                'style' => 'margin:15px 50px',
            )
        );
        $html .= self::tag(
            'p',
            '正在努力加载页面 o(>﹏<)o',
            array(
                'style' => 'margin-top: 12px'
            )
        );
        $html = self::tag(
            'div',
            $html,
            array(
                'class' => 'alert alert-info width-350 text-center',
            )
        );

        return $html;
    }

    // success, info, warning, danger
    public static function alert($msg, $alert = 'danger')
    {
        return self::tag(
            'div',
            $msg,
            array(
                'class' => 'alert alert-' . $alert,
                'role'  => 'alert',
            )
        );
    }

}

// END
