<?php
/**
 * Created by Hisune.
 * User: hi@hisune.com
 * Date: 2015/7/2
 * Time: 11:18
 */
namespace Tiny;

class Curl
{
    static public function request($url, $params = array(), $method = 'get', $protocol = 'http'){
        $query_string = '';
        if(is_array($params)){
            $query_string = http_build_query($params);
        }elseif(is_string($params)){
            $query_string = $params;
        }
        $ch = curl_init();
        if('get' == $method){
            curl_setopt($ch, CURLOPT_URL, $url.'?'.$query_string);
        }else{
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
        }

        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:')); // disable 100-continue

        if('https' == $protocol){
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        $ret = curl_exec($ch);
        $err = curl_error($ch);

        if(false === $ret || !empty($err)){
            $errno = curl_errno($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);

            return array(
                'result' => false,
                'errno' => $errno,
                'msg' => $err,
                'info' => $info,
            );
        }

        curl_close($ch);

        return array(
            'result' => true,
            'msg' => $ret,
        );
    }
}