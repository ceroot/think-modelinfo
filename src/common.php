<?php
// +----------------------------------------------------------------------
// | benweng [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2018 https://www.benweng.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: SpringYang  82550565@qq.com <www.benweng.com>
// +----------------------------------------------------------------------

/*
 * 实例化模型解析类
 */
function modelinfo(){
   return new \ceroot\ModelInfo();
}

/* 解析列表定义规则(非文档模型解析)
 * $replace [['[DELETE]','[EDIT]',['[LIST]'],'DELETE','EDIT','LIST']]
 */
function intent_list_field($data, $grid,$replace = false){

    //获取请求参数
    $param = request()->param();
    $data  = array_merge($param,$data);
    // 获取当前字段数据
    foreach($grid['field'] as $field){
        $array  = explode('|',$field);
        $temp   = isset($data[$array[0]])?$data[$array[0]]:'';
        // 函数支持
        if(isset($array[1]) && preg_match('/(.*?)\((.*)\)/',$array[1],$matches)){ //自定义参数模式
            $temp = parseFunctionString($matches,$array[1],$data);
        }elseif(isset($array[1]) && preg_match('#\{(.*?)\}#',$array[1],$matches)){
            $switch_arr = explode(' ',$matches[1]);
            foreach ($switch_arr as $value){
                $value_arr = explode('.',$value);
                $arr[$value_arr[0]] = $value_arr;
            }
            $var_key = $data[$array[0]];
            $show    = $arr[$var_key][1];
            // 替换数据变量
            $href   = isset($arr[$var_key][2]) ? preg_replace_callback('/\[([a-z_]+)\]/', function($match) use($data){return $data[$match[1]];}, $arr[$var_key][2]):'';
            $temp =   isset($arr[$var_key][2]) ?'<a href="'.url($href).'">'.$show.'</a>':$show;
        }elseif(isset($array[1])){ //默认参数模式
            $temp = call_user_func($array[1], $temp);
        }
        $data2[$array[0]]    =   $temp;
    }
    if(!empty($grid['format'])){
        $value  = preg_replace_callback('/\[([a-z_]+)\]/', function($match) use($data2){return $data2[$match[1]];}, $grid['format']);
    }else{
        $value  = implode(' ',$data2);
    }
    if(!empty($grid['href'])){
        $links  = explode(',',$grid['href']);
        foreach($links as $link){
            $array  = explode('|',$link);
            $href   = $array[0];
            $switch = isset($array[1])?$array[1]:'';
            if(preg_match('#\{(.*?)\}#',$switch,$matches)){// switch 格式解析 列:[status]|{1.启用 2.禁用} 即: [字段]|{值.标题.链接(多个用空格分割)}
                $switch_arr = explode(' ',$matches[1]);
                foreach ($switch_arr as $value){
                    $value_arr = explode('.',$value);
                    $arr[$value_arr[0]] = $value_arr;
                }
                preg_match('/^\[([a-z_]+)\]$/',$array[0],$matches);
                $data_val = $data[$matches[1]];
                $show     = $arr[$data_val][1];

                // 替换系统特殊字符串
                $href   = isset($arr[$data_val][2]) ? str_replace($replace['0'],$replace['1'],$arr[$data_val][2]):'';
                // 替换数据变量
                $href   = preg_replace_callback('/\[([a-z_]+)\]/', function($match) use($data){return isset($data[$match[1]])?$data[$match[1]]:'';},$href);
                $val[]  = '<a href="'.url($href).'">'.$show.'</a>';
            }elseif(preg_match('/^\[([a-z_]+)\]$/',$href,$matches)){ //直接显示内容
                $val[]  = $data2[$matches[1]];
            }elseif(preg_match('/(.*?)\((.*)\)/',$href,$matches)){ //函数支持
                $val[]  = parseFunctionString($matches,$href,$data);
            }else{
                $show   = isset($array[1])?$array[1]:$value;
                // 生成特定class名称 如:[EDIT] 解析: edit
                $class = '';
                preg_match('/^\[([A-Z_]+)\]$/',$href,$matches);
                if(isset($matches[1])){
                    $class = strtolower($matches[1]);
                }

                // 替换系统特殊字符串
                $href   = $replace?str_replace($replace['0'],$replace['1'],$href):$href;
                // 替换数据变量
                $href   = preg_replace_callback('/\[([a-z_]+)\]/', function($match) use($data){return isset($data[$match[1]])?$data[$match[1]]:'';}, $href);
                $val[]  = '<a class="'.$class.'" lay-event="'.$class.'" url="'.url($href).'">'.$show.'</a>';
            }
        }
        $value  =   implode(' ',$val);
    }
    return $value;
}

/*
 * 字符串 函数解析
 * $matches
 * $str //原始字符串
 * $data 数据集
 */
function parseFunctionString($matches,$str,$data){
    if(empty($matches[2]))
        return   eval('return '.$str.';');
    $matches[2] = str_replace("<D>",",",$matches[2]);
    $matches[2] = str_replace("<M>",":",$matches[2]);
    //参数解析
    $matches[2] = '['.$matches[2].']';
    eval("\$matches[2]  = ".$matches[2] .'; ');
    // 替换数据变量
    $param_arr = parse_field_attr_param($matches[2],$data);
    return call_user_func_array($matches[1], $param_arr);
}

/* 替换数据变量
 * $array 处理数组
 * $param $data
 */
function parse_field_attr_param($array,$data){
    foreach ($array as $key=>&$value){
        if(is_array($value)){
            $value  = parse_field_attr_param($value,$data);
        }elseif($value == '[DATA]'){
            $value  = $data;
        }else{
            $value  = preg_replace_callback('/\[([a-z_]+)\]/', function($match) use($data){return $data[$match[1]];}, $value);
        }
    }
    return $array;
}

// 分析枚举类型配置值 格式 a:名称1,b:名称2
function parse_config_attr($string) {
    if(is_array($string)){
        return $string;
    }
    $array = preg_split('/[,;\r\n]+/', trim($string, ",;\r\n"));
    if(strpos($string,':')){
        $value  = array();
        foreach ($array as $val) {
            list($k, $v) = explode(':', $val);
            $value[$k]   = $v;
        }
    }else{
        $value  = $array;
    }
    return $value;
}

/* 分析枚举类型字段值 格式 a:名称1,b:名称2
 * 但请不要互相使用，后期会调整
 * @$string 格式规则
 * @$data   数据集
 * @$value   当前字段内容或者默认值
 */
function parse_field_attr($string,$data=false,$value='') {
    if(!$data){
        $data = request()->param();
    }
    //支持数组 [$key=>$v,$key=>$v]
    if(is_array($string)){
        return $string;
    }
    if(0 === strpos($string,':')){// 采用函数定义
        $str = substr($string,1);
        // :[pid] 获取参值 $data不存在则获取request参
        if(0 === strpos($str,'[')){
            return $str = preg_replace_callback('/\[([a-z_]+)\]/', function($match) use($data){return isset($data[$match[1]])?$data[$match[1]]:'';}, $str);
        }
        //自定义函数
        if(preg_match('/(.*?)\((.*)\)/',$str,$matches)){
            return parseFunctionString($matches,$str,$data);
        }
    }elseif(0 === strpos($string,'[')){
        // 支持读取配置参数（必须是数组类型）
        return config(substr($string,1,-1));
    }

    $array = preg_split('/[,;\r\n]+/', trim($string, ",;\r\n"));
    if(strpos($string,':')){
        $value  =   array();
        foreach ($array as $val) {
            list($k, $v) = explode(':', $val);
            $value[$k]   = $v;
        }
    }else{
        $value  =   $array;
    }
    return $value;
}

/**
 * select返回的数组进行整数映射转换
 *
 * @param array $map  映射关系二维数组  array(
 *                                          '字段名1'=>array(映射关系数组),
 *                                          '字段名2'=>array(映射关系数组),
 *                                           ......
 *                                       ) * @author SpringYang <ceroot@163.com>
 * @return array
 *
 *  array(
 *      array('id'=>1,'title'=>'标题','status'=>'1','status_text'=>'正常')
 *      ....
 *  )
 *
 */
function int_to_string(&$data,$map=array('status'=>array(1=>'正常',-1=>'删除',0=>'禁用',2=>'未审核',3=>'草稿'))) {
    if($data === false || $data === null ){
        return $data;
    }
    $data = (array)$data;
    foreach ($data as $key => $row){
        foreach ($map as $col=>$pair){
            if(isset($row[$col]) && isset($pair[$row[$col]])){
                $data[$key][$col.'_text'] = $pair[$row[$col]];
            }
        }
    }
    return $data;
}