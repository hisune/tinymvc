<?php
/**
 * Created by hisune.com.
 * User: hi@hisune.com
 * Date: 14-7-16
 * Time: 下午8:10
 *
 * 创建gif动态验证码，支持颜色识别验证
 *
 * 使用方法：
 * require_once('verify.php');
 * $verify = new verify();
 * $verify->font = './style/font/xx.ttf';
 * $verify->createVerify();
 * session('captcha_code', $verify->getVerify());
 * $verify->outputVerify();
 */
namespace Tiny;

class Verify
{
    var $len = 4; //验证码长度
    var $str = 'abcdefghjkmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'; //验证码字符列表
    var $height = 40; //验证码高度，将根据高度自适应宽度
    var $background = array(255, 255, 255); //背景颜色rbg
    var $findColor = false; //是否开启看颜色识别验证码
    var $font = ''; //字体文件，可以使用自创字体加强安全性
    var $autoLower = true; //是否自动转为全小写
    static $sessionKey = 'verify_code';

    private $frames = 32; //动画帧数
    private $tips = ''; //如果开启了看颜色识别，这里显示当前验证字符的颜色
    private $verifyCode = ''; //验证码字符串
    private $imageData = null; //图片信息
    private $delay = null; //延迟数组
    private $fontColor = array(); //每个字母的颜色
    private $baseColor = array(
        array(
            'tips' => '红色',
            'rbg' => array(255, 0, 0),
        ),
        array(
            'tips' => '蓝色',
            'rbg' => array(83, 104, 189),
        ),
        array(
            'tips' => '绿色',
            'rbg' => array(107, 193, 70),
        ),
        array(
            'tips' => '橙色',
            'rbg' => array(255, 140, 0),
        ),
        array(
            'tips' => '紫色',
            'rbg' => array(128, 0, 128),
        ),
    );

    /**
     * 创建一个gif验证码，但不输出
     */
    public function createVerify()
    {
        $width = $this->height * $this->len / 1.6;

        $this->verifyCode = $this->random($this->len, $this->str);
        $quarterHeight = $this->height / 4;
        $fontHeight = $this->height - $quarterHeight;
        $halfHeight = $this->height / 2;
        for ($num = 0; $num < $this->len; $num++) {
            $c_angel[$num] = $i_rand = rand(0, 30); // 每个字母的初始化角度
            $this->fontColor[$num] = rand(0, 4); // 每个字母的初始化颜色
            $c_y[$num] = $fontHeight + $this->fontColor[$num]; // 每个字母的初始化高度
            $r_1_2[$num] = rand(3, 20); // 该值决定每个字母的旋转方向和速度及最大角度
            $r_size[$num] = rand($halfHeight, $fontHeight); // 每个字母的初始化大小
        }

        $fra_2 = $this->frames / 2;
        $fra_4 = $this->frames / 4;
        $fra_8 = $this->frames - $fra_4;

        for ($i = 0; $i < $this->frames; $i++) {
            ob_start();
            $image = imagecreate($width, $this->height);
            $red = imagecolorallocate($image, $this->baseColor['0']['rbg']['0'], $this->baseColor['0']['rbg']['1'], $this->baseColor['0']['rbg']['2']);
            $blue = imagecolorallocate($image, $this->baseColor['1']['rbg']['0'], $this->baseColor['1']['rbg']['1'], $this->baseColor['1']['rbg']['2']);
            $green = imagecolorallocate($image, $this->baseColor['2']['rbg']['0'], $this->baseColor['2']['rbg']['1'], $this->baseColor['2']['rbg']['2']);
            $orange = imagecolorallocate($image, $this->baseColor['3']['rbg']['0'], $this->baseColor['3']['rbg']['1'], $this->baseColor['3']['rbg']['2']);
            $purple = imagecolorallocate($image, $this->baseColor['4']['rbg']['0'], $this->baseColor['4']['rbg']['1'], $this->baseColor['4']['rbg']['2']);
            $colors = array($red, $blue, $green, $orange, $purple);
            $gray = imagecolorallocate($image, $this->background['0'], $this->background['1'], $this->background['2']);
            imagefill($image, 0, 0, $gray);

            $j = $i < $fra_4 ? $i : $fra_2 - $i; // 实现gif旋转对称
            if ($i > $fra_8) {
                $j += $fra_2;
                $j = -$j;
            }

            for ($num = 0; $num < $this->len; $num++) {
                $angel = $r_1_2[$num] % 2 == 0 ? $c_angel[$num] + $j * $r_1_2[$num] : $c_angel[$num] - $j * $r_1_2[$num];
                imagettftext($image, $r_size[$num], $angel, $quarterHeight + $halfHeight * $num + 2, $c_y[$num],
                    $colors[$this->fontColor[$num]],
                    $this->font,
                    $this->verifyCode[$num]);
            }

            Imagegif($image);
            ImageDestroy($image);
            $delay[] = 8; // 每一帧的延迟
            $imageData[] = ob_get_contents();
            ob_clean();
        }

        $this->findColor && $this->findColor();
        $this->randImageData($imageData);
        $this->delay = $delay;
    }

    /**
     * 如果开启了看颜色识别，这里处理颜色识别
     */
    private function findColor()
    {
        $code = $this->verifyCode;
        $this->verifyCode = '';

        $color = array_values($this->fontColor);
        $rand = rand(0, count($color) - 1);
        foreach ($this->fontColor as $k => $v) {
            if ($v == $this->fontColor[$rand]) {
                $this->verifyCode .= $code[$k];
            }
        }
        $this->tips = $this->baseColor[$color[$rand]]['tips'];
    }

    /**
     * 偏移gif帧数
     */
    private function randImageData($imageData)
    {
        $rand = rand(0, $this->frames);
        if ($rand) {
            foreach ($imageData as $k => $v) {
                if ($k > $rand) break;
                array_shift($imageData);
                $imageData[] = $v;
            }
        }

        $this->imageData = $imageData;
    }

    /**
     * 输出验证码
     */
    public function outputVerify()
    {
        $this->createVerify();
        $gif = new gifEncoder($this->imageData, $this->delay);
        Session::set(static::$sessionKey, $this->autoLower ? strtolower($this->verifyCode) : $this->verifyCode);
        header('content-type:image/gif');
        echo $gif->GetAnimation();
    }

    private function random($length, $chars)
    {
        $hash = '';
        $max = strlen($chars) - 1;
        for ($i = 0; $i < $length; $i++) {
            $hash .= $chars[mt_rand(0, $max)];
        }
        return $hash;
    }

    /**
     * 获取验证码字符串，用于存session
     */
    public static function getVerify()
    {
        $verify = Session::get(self::$sessionKey);
        Session::set(self::$sessionKey, null);
        return $verify;
    }

    /**
     * 如果开启了看颜色识别，这里返回当前验证字符的颜色
     */
    public function getTips()
    {
        return $this->tips;
    }
}


/**
 * gifEncoder类
 **/
class gifEncoder
{
    var $GIF = "GIF89a"; /* GIF header 6 bytes        */
    var $VER = "GIFEncoder V2.06"; /* Encoder version             */

    var $BUF = array();
    var $LOP = 0;
    var $DIS = 2;
    var $COL = -1;
    var $IMG = -1;

    var $ERR = array(
        'ERR00' => "Does not supported function for only one image!",
        'ERR01' => "Source is not a GIF image!",
        'ERR02' => "Unintelligible flag ",
        'ERR03' => "Could not make animation from animated GIF source",
    );

    /**
     * $GIF_dly array 每一帧延迟多少秒
     */
    function __construct($GIF_src, $GIF_dly = 100, $GIF_lop = 0, $GIF_dis = 0, $GIF_red = 0, $GIF_grn = 0, $GIF_blu = 0, $GIF_mod = 'bin')
    {
        if (!is_array($GIF_src)) {
            printf("%s: %s", $this->VER, $this->ERR['ERR00']);
            exit(0);
        }
        $this->LOP = ($GIF_lop > -1) ? $GIF_lop : 0;
        $this->DIS = ($GIF_dis > -1) ? (($GIF_dis < 3) ? $GIF_dis : 3) : 2;
        $this->COL = ($GIF_red > -1 && $GIF_grn > -1 && $GIF_blu > -1) ? ($GIF_red | ($GIF_grn << 8) | ($GIF_blu << 16)) : -1;

        for ($i = 0, $src_count = count($GIF_src); $i < $src_count; $i++) {
            if (strToLower($GIF_mod) == "url") {
                $this->BUF[] = fread(fopen($GIF_src [$i], "rb"), filesize($GIF_src [$i]));
            } elseif (strToLower($GIF_mod) == "bin") {
                $this->BUF [] = $GIF_src [$i];
            } else {
                printf("%s: %s ( %s )!", $this->VER, $this->ERR ['ERR02'], $GIF_mod);
                exit(0);
            }
            if (substr($this->BUF[$i], 0, 6) != "GIF87a" && substr($this->BUF [$i], 0, 6) != "GIF89a") {
                printf("%s: %d %s", $this->VER, $i, $this->ERR ['ERR01']);
                exit(0);
            }
            for ($j = (13 + 3 * (2 << (ord($this->BUF[$i]{10}) & 0x07))), $k = TRUE; $k; $j++) {
                switch ($this->BUF [$i]{$j}) {
                    case "!":
                        if ((substr($this->BUF[$i], ($j + 3), 8)) == "NETSCAPE") {
                            printf("%s: %s ( %s source )!", $this->VER, $this->ERR ['ERR03'], ($i + 1));
                            exit(0);
                        }
                        break;
                    case ";":
                        $k = FALSE;
                        break;
                }
            }
        }
        GIFEncoder::GIFAddHeader();
        for ($i = 0, $count_buf = count($this->BUF); $i < $count_buf; $i++) {
            GIFEncoder::GIFAddFrames($i, $GIF_dly[$i]);
        }
        GIFEncoder::GIFAddFooter();
    }

    function GIFAddHeader()
    {
        if (ord($this->BUF[0]{10}) & 0x80) {
            $cmap = 3 * (2 << (ord($this->BUF [0]{10}) & 0x07));

            $this->GIF .= substr($this->BUF [0], 6, 7);
            $this->GIF .= substr($this->BUF [0], 13, $cmap);
            $this->GIF .= "!\377\13NETSCAPE2.0\3\1" . GIFEncoder::GIFWord($this->LOP) . "\0";
        }
    }

    function GIFAddFrames($i, $d)
    {

        $Locals_str = 13 + 3 * (2 << (ord($this->BUF[$i]{10}) & 0x07));

        $Locals_end = strlen($this->BUF[$i]) - $Locals_str - 1;
        $Locals_tmp = substr($this->BUF[$i], $Locals_str, $Locals_end);

        $Global_len = 2 << (ord($this->BUF [0]{10}) & 0x07);
        $Locals_len = 2 << (ord($this->BUF[$i]{10}) & 0x07);

        $Global_rgb = substr($this->BUF[0], 13, 3 * (2 << (ord($this->BUF[0]{10}) & 0x07)));
        $Locals_rgb = substr($this->BUF[$i], 13, 3 * (2 << (ord($this->BUF[$i]{10}) & 0x07)));

        $Locals_ext = "!\xF9\x04" . chr(($this->DIS << 2) + 0) . chr(($d >> 0) & 0xFF) . chr(($d >> 8) & 0xFF) . "\x0\x0";

        if ($this->COL > -1 && ord($this->BUF[$i]{10}) & 0x80) {
            for ($j = 0; $j < (2 << (ord($this->BUF[$i]{10}) & 0x07)); $j++) {
                if (ord($Locals_rgb{3 * $j + 0}) == ($this->COL >> 0) & 0xFF && ord($Locals_rgb{3 * $j + 1}) == ($this->COL >> 8) & 0xFF && ord($Locals_rgb{3 * $j + 2}) == ($this->COL >> 16) & 0xFF) {
                    $Locals_ext = "!\xF9\x04" . chr(($this->DIS << 2) + 1) . chr(($d >> 0) & 0xFF) . chr(($d >> 8) & 0xFF) . chr($j) . "\x0";
                    break;
                }
            }
        }
        switch ($Locals_tmp{0}) {
            case "!":
                $Locals_img = substr($Locals_tmp, 8, 10);
                $Locals_tmp = substr($Locals_tmp, 18, strlen($Locals_tmp) - 18);
                break;
            case ",":
                $Locals_img = substr($Locals_tmp, 0, 10);
                $Locals_tmp = substr($Locals_tmp, 10, strlen($Locals_tmp) - 10);
                break;
        }
        if (ord($this->BUF[$i]{10}) & 0x80 && $this->IMG > -1) {
            if ($Global_len == $Locals_len) {
                if (GIFEncoder::GIFBlockCompare($Global_rgb, $Locals_rgb, $Global_len)) {
                    $this->GIF .= ($Locals_ext . $Locals_img . $Locals_tmp);
                } else {
                    $byte = ord($Locals_img{9});
                    $byte |= 0x80;
                    $byte &= 0xF8;
                    $byte |= (ord($this->BUF [0]{10}) & 0x07);
                    $Locals_img{9} = chr($byte);
                    $this->GIF .= ($Locals_ext . $Locals_img . $Locals_rgb . $Locals_tmp);
                }
            } else {
                $byte = ord($Locals_img{9});
                $byte |= 0x80;
                $byte &= 0xF8;
                $byte |= (ord($this->BUF[$i]{10}) & 0x07);
                $Locals_img{9} = chr($byte);
                $this->GIF .= ($Locals_ext . $Locals_img . $Locals_rgb . $Locals_tmp);
            }
        } else {
            $this->GIF .= ($Locals_ext . $Locals_img . $Locals_tmp);
        }
        $this->IMG = 1;
    }

    function GIFAddFooter()
    {
        $this->GIF .= ";";
    }

    function GIFBlockCompare($GlobalBlock, $LocalBlock, $Len)
    {
        for ($i = 0; $i < $Len; $i++) {
            if ($GlobalBlock{3 * $i + 0} != $LocalBlock{3 * $i + 0} || $GlobalBlock{3 * $i + 1} != $LocalBlock{3 * $i + 1} || $GlobalBlock{3 * $i + 2} != $LocalBlock{3 * $i + 2}) {
                return (0);
            }
        }
        return (1);
    }

    function GIFWord($int)
    {
        return (chr($int & 0xFF) . chr(($int >> 8) & 0xFF));
    }

    function GetAnimation()
    {
        return ($this->GIF);
    }
}
