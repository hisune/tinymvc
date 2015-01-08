<?php
/**
 * Created by hisune.com.
 * User: hi@hisune.com
 * Date: 2014/10/9 0009
 * Time: 下午 7:37
 *
 * 公用辅助函数类
 */
namespace Tiny;

class Func
{
    public static function humanTime($time)
    {
        if(!is_numeric($time))
            $time = strtotime($time);

        $diff = time() - $time;
        if($diff < 60)
            return $diff . '秒前';
        elseif($diff < 3600)
            return floor($diff / 60) . '分钟前';
        elseif($diff < 86400)
            return floor($diff / 3600) . '小时前';
        elseif($diff < 432000)
            return floor($diff / 86400) . '天前';
        else
            return date('Y-m-d H:i:s', $time);
    }

    public static function echoJson($ret, $msg = null, $exit = true)
    {
        $array['ret'] = (int)$ret;
        $msg && $array['msg'] = $msg;
        echo json_encode($array);
        $exit && exit;
    }

    /**
     * @param $arr array 数据数组或带\n\r字符串
     * @param $fileName string 生成的文件名
     */
    public static function any2excel($mixed, $fileName = null)
    {
        is_null($fileName) && $fileName = 'export_excel_' . date('Y-m-d H_i_s');

        header("content-type:text/html; charset=utf-8");
        header("Content-type:application/vnd.ms-excel");
        header("Content-Disposition:attachment;filename={$fileName}.xls");
        if(is_array($mixed)){
            $str = '';
            foreach($mixed as $v1){
                $str .= '<tr>';
                foreach($v1 as $v2){
                    if((string)$v2 == (string)floatval($v2)){ // 数字
                        if($v2 > 4294967296)
                            $str .= '<td x:str class=xl2216681 nowrap>'.$v2.'</td>';
                        else
                            $str .= '<td x:num class=xl2216681 nowrap>'.$v2.'</td>';
                    }else
                        $str .= '<td x:str class=xl2216681 nowrap>'.$v2.'</td>';
                }
                $str .= '</tr>';
            }
            $opt='
		    <html xmlns:o="urn:schemas-microsoft-com:office:office"
		    xmlns:x="urn:schemas-microsoft-com:office:excel"
		    xmlns="http://www.w3.org/TR/REC-html40">
		    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		    <html>
		    <head>
		    <meta http-equiv="Content-type" content="text/html;charset=utf-8" />
		    <style id="Classeur1_16681_Styles"></style>
		    </head>
		    <body>
		    <div id="Classeur1_16681" align=center x:publishsource="Excel">
		    <table border=1 cellpadding=0 cellspacing=0 style="border-collapse: collapse">
		    '.$str.'
		    </table>
		    </div>
		    </body>
		    </html>';
        }else
            $opt = $mixed;

        die($opt);
    }
}
