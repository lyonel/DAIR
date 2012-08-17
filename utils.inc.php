<?php

function form_select($name,$options,$selected = '',$params = '')
{
    $return = '<select name="'.$name.'" id="'.$name.'"';
    if(is_array($params))
    {
        foreach($params as $key=>$value)
        {
            $return.= ' '.$key.'="'.$value.'"';
        }
    }
    else
    {
        $return.= $params;
    }
    $return.= '>';
    foreach($options as $key=>$value)
    {
        $return.='<option value="'.$value.'"'.($selected != $value ? '' : ' selected="selected"').'>'.$key.'</option>';
    }
    return $return.'</select>';
}

function reformat($str) {
    $pattern = array(
        '/\*\*(.*?)\*\*/is',
        '/__(.*?)__/is',
        '/(http:\/\/\S*)/is',
        '/(https:\/\/\S*)/is',
        '/(ftp:\/\/\S*)/is',
        '/(\S*?@\S*)/is',
    ); 

    $replace = array(
        '<strong>$1</strong>',
        '<u>$1</u>',
        '<a href="$1">$1</a>',
        '<a href="$1">$1</a>',
        '<a href="$1">$1</a>',
        '<a href="mailto:$1">$1</a>',
    ); 

    $str = preg_replace ($pattern, $replace, $str); 
    $str = nl2br($str);
    return $str;
} 

?>
