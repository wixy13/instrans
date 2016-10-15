<?php


function my_dump($vars, $label = '', $return = false)
{
    if (ini_get('html_errors')) {
        $content = "<pre>\n";
        if ($label != '') {
            $content .= "<strong>{$label} :</strong>\n";
        }
    $content .= htmlspecialchars(print_r($vars, true));
        $content .= "\n</pre>\n";
    } else {
    $content = $label . " :\n" . print_r($vars, true);
    }
    if ($return) { return $content; }
    echo $content;
    return null;
}

function http_curl($url,$type='get',$res='json',$arr='', $header = NULL){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    if($header != NULL)
        curl_setopt($ch, CURLOPT_HTTPHEADER  , $header);
    if($type == 'post'){
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $arr);
    }
    $output = curl_exec($ch);
    if($res == 'json'){
        if(curl_errno($ch)){
            return curl_error($ch);
        }else{
            return json_decode($output, true);
        }
    }
}
