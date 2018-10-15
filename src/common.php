<?php
// +----------------------------------------------------------------------
// | benweng [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2018 https://www.benweng.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: SpringYang  ceroot@163.com <www.benweng.com>
// +----------------------------------------------------------------------

/*
 * 实例化模型解析类
 */
function modelinfo()
{
    return new \ceroot\ModelInfo();
}

/* 解析列表定义规则(非文档模型解析)
 * $replace [['[DELETE]','[EDIT]',['[LIST]'],'DELETE','EDIT','LIST']]
 */
function intent_list_field($data, $grid, $replace = false)
{
    //获取请求参数
    $param = request()->param();
    $data  = array_merge($param, $data);

    // 获取当前字段数据
    foreach ($grid['field'] as $field) {
        $field_arr   = explode('|', $field); // 字段数组，0 为字段，1 为函数名
        $field_name  = isset($field_arr[0]) ? $field_arr[0] : ''; // 字段名
        $field_value = isset($data[$field_name]) ? $data[$field_name] : ''; // 取得数据值
        // 函数支持
        if (isset($field_arr[1]) && preg_match('/(.*?)\((.*)\)/', $field_arr[1], $matches)) {
            // 自定义参数模式
            $field_value = parseFunctionString($matches, $field_arr[1], $data);
        } elseif (isset($field_arr[1]) && preg_match('#\{(.*?)\}#', $field_arr[1], $matches)) {
            $switch_arr = explode(' ', $matches[1]);
            foreach ($switch_arr as $value) {
                $value_arr          = explode('.', $value);
                $arr[$value_arr[0]] = $value_arr;
            }

            $var_key = $data[$field_arr[0]];
            $show    = $arr[$var_key][1];

            // 替换数据变量
            $href = isset($arr[$var_key][2]) ? preg_replace_callback('/\[([a-z_]+)\]/', function ($match) use ($data) {return $data[$match[1]];}, $arr[$var_key][2]) : '';
            $field_value = isset($arr[$var_key][2]) ? '<a href="' . url($href) . '">' . $show . '</a>' : $show;
        } elseif (isset($field_arr[1])) {
            //默认参数模式
            $field_value = call_user_func($field_arr[1], $field_value);
        }
        $data2[$field_arr[0]] = $field_value;
    }

    if (!empty($grid['format'])) {
        $value = preg_replace_callback('/\[([a-z_]+)\]/', function ($match) use ($data2) {return $data2[$match[1]];}, $grid['format']);
    } else {
        $value = implode(' ', $data2);
    }

    if (!empty($grid['href'])) {
        $links = explode(',', $grid['href']);
        foreach ($links as $link) {
            $link_arr   = explode('|', $link);
            $link_value = $href = isset($link_arr[0]) ? $link_arr[0] : ''; // 取得左边的，比如[status]、[EDIT]
            $link_title = isset($link_arr[1]) ? $link_arr[1] : ''; // 取得右边的，比如{0.启用.updatefield?field=status&value=1&id=[id].dddd@ddd@ddd.快速设置状态 1.禁用.updatefield?field=status&value=0&id=[id]}
            $extra      = isset($link_arr[2]) ? $link_arr[2] : '';
            $href       = ''; // 链接 url
            if (preg_match('#\{(.*?)\}#', $link_title, $matches)) {
                preg_match('/^\[([a-z_]+)\]$/', $link_value, $mth);
                $field_name  = isset($mth[1]) ? $mth[1] : ''; // 字段名
                $field_value = isset($data[$field_name]) ? $data[$field_name] : ''; // 取得数据值

                // switch 格式解析 列:[status]|{1.启用 2.禁用} 即: [字段]|{值.标题.链接.class.title (多个用空格分割)} 多个 class 用 @ 分割
                $switch_arr = explode(' ', $matches[1]);
                $class      = ''; // html class 类
                $quickTitle = ''; // 链接鼠标移上去显示的文字
                $show       = ''; // 显示的文字

                foreach ($switch_arr as $value) {
                    $value_arr = explode('.', $value);
                    // 判断当前显示的数据
                    if ($value_arr[0] == $field_value) {
                        $show       = $value_arr[1];
                        $href       = $value_arr[2];
                        $class      = isset($value_arr[3]) ? str_replace('@', ' ', $value_arr[3]) : ''; // 取得类用于显示
                        $quickTitle = isset($value_arr[4]) ? $value_arr[4] : ''; // 取得提示标题
                    }
                }

                $href = str_replace($replace['0'], $replace['1'], $href); // 替换系统特殊字符串
                $href = str_replace('[field]', authcode($field_name), $href); // 替换字段名
                $href = preg_replace_callback('/\[([a-z_]+)\]/', function ($match) use ($data) {return isset($data[$match[1]]) ? $data[$match[1]] : '';}, $href); // 替换数据变量
                $val[] = $ddd = '<a title="' . $quickTitle . '" class="' . $class . '" href="' . url($href) . '">' . $show . '</a>'; // 组合 html
            } elseif (preg_match('#\<(.*?)\>#', $link_title, $matches)) {
                preg_match('/^\[([a-z_]+)\]$/', $link_value, $mth);
                $field_name  = isset($mth[1]) ? $mth[1] : ''; // 字段名
                $field_value = isset($data[$field_name]) ? $data[$field_name] : ''; // 取得数据值

                $checked    = ($field_value == 0) ? 'checked=""' : ''; // 默认开关
                $switch_arr = explode('.', $matches[1]);
                $show       = (isset($switch_arr[1]) && !empty($switch_arr[1])) ? str_replace('/', '|', $switch_arr[1]) : 'ON|OFF'; // 开关显示文字

                // 链接的处理
                if (isset($switch_arr[2]) && !empty($switch_arr[2])) {
                    $href = str_replace($replace['0'], $replace['1'], $switch_arr[2]); // 替换系统特殊字符串
                    $href = str_replace('[field]', authcode($field_name), $href); // 替换字段名
                }
                $href = preg_replace_callback('/\[([a-z_]+)\]/', function ($match) use ($data) {return isset($data[$match[1]]) ? $data[$match[1]] : '';}, $href); // 替换数据变量

                $val[] = '<input type="checkbox" name="close" ' . $checked . ' lay-skin="switch" lay-filter="setfield" lay-event="setfield" data-href="' . url($href) . '" lay-text="' . $show . '">'; // 组合 html
            } elseif (preg_match('/^\[([A-Z_]+)\]$/', $link_value, $matches)) {
                $show = $link_title;
                // 生成特定class名称 如:[EDIT] 解析: edit
                $class = '';
                $event = '';
                if (isset($matches[1])) {
                    $event = strtolower($matches[1]);
                    $class .= 'layui-btn ';
                    $class .= ($event == 'details') ? 'layui-btn-primary' : '';
                    $class .= ($event == 'edit') ? 'layui-btn-normal' : '';
                    $class .= ($event == 'views') ? '' : '';
                    $class .= ($event == 'delete') ? 'layui-btn-danger' : '';
                    $class .= ' layui-btn-xs';
                }
                $href = isset($matches[0]) ? $matches[0] : '';
                $href = $replace ? str_replace($replace['0'], $replace['1'], $href) : $href; // 替换系统特殊字符串
                $href = preg_replace_callback('/\[([a-z_]+)\]/', function ($match) use ($data) {return isset($data[$match[1]]) ? $data[$match[1]] : '';}, $href); // 替换数据变量
                // if (!empty($extra)) {
                //     $extra = 'data-event="' . $event . '"';
                // }

                $val[] = '<a title="' . $show . '" class="' . $class . '" lay-event="' . $event . '"' . $extra . ' url="' . url($href) . '">' . $show . '</a>';
            } elseif (preg_match('/^\[([a-z_]+)\]$/', $link_value, $matches)) {
                //直接显示内容
                $val[] = $data2[$matches[1]];
            } elseif (preg_match('/(.*?)\((.*)\)/', $link_value, $matches)) {
                //函数支持
                $val[] = parseFunctionString($matches, $link_value, $data);
            } else {
                $href = $link_value;
                $show = $link_title;

                $link_value = $replace ? str_replace($replace['0'], $replace['1'], $link_value) : $link_value; // 替换系统特殊字符串
                $link_value = preg_replace_callback('/\[([a-z_]+)\]/', function ($match) use ($data) {return isset($data[$match[1]]) ? $data[$match[1]] : '';}, $link_value); // 替换数据变量
                $val[] = '<a title="' . $link_title . '" href="' . url($link_value) . '">' . $link_title . '</a>';
            }
        }
        $value = implode(' ', $val);
    }
    return $value;
}

/*
 * 字符串 函数解析
 * $matches
 * $str //原始字符串
 * $data 数据集
 */
function parseFunctionString($matches, $str, $data)
{
    if (empty($matches[2])) {
        return eval('return ' . $str . ';');
    }

    $matches[2] = str_replace("<D>", ",", $matches[2]);
    $matches[2] = str_replace("<M>", ":", $matches[2]);
    //参数解析
    $matches[2] = '[' . $matches[2] . ']';
    eval("\$matches[2]  = " . $matches[2] . '; ');
    // 替换数据变量
    $param_arr = parse_field_attr_param($matches[2], $data);
    return call_user_func_array($matches[1], $param_arr);
}

/* 替换数据变量
 * $array 处理数组
 * $param $data
 */
function parse_field_attr_param($array, $data)
{

    foreach ($array as &$value) {
        if (is_array($value)) {
            $value = parse_field_attr_param($value, $data);
        } elseif ($value == '[DATA]') {
            $value = $data;
        } else {
            $value = preg_replace_callback('/\[([a-z_]+)\]/', function ($match) use ($data) {return $data[$match[1]];}, $value);
        }
    }
    return $array;
}

// 分析枚举类型配置值 格式 a:名称1,b:名称2
function parse_config_attr($string)
{
    if (is_array($string)) {
        return $string;
    }
    $array = preg_split('/[,;\r\n]+/', trim($string, ",;\r\n"));
    if (strpos($string, ':')) {
        $value = array();
        foreach ($array as $val) {
            list($k, $v) = explode(':', $val);
            $value[$k]   = $v;
        }
    } else {
        $value = $array;
    }
    return $value;
}

/* 分析枚举类型字段值 格式 a:名称1,b:名称2
 * 但请不要互相使用，后期会调整
 * @$string 格式规则
 * @$data   数据集
 * @$value   当前字段内容或者默认值
 */
function parse_field_attr($string, $data = false, $value = '')
{
    if (!$data) {
        $data = request()->param();
    }
    // 支持数组 [$key=>$v,$key=>$v]
    if (is_array($string)) {
        return $string;
    }
    if (0 === strpos($string, ':')) {
        // 采用函数定义
        $str = substr($string, 1);
        // :[pid] 获取参值 $data不存在则获取request参
        if (0 === strpos($str, '[')) {
            return $str = preg_replace_callback('/\[([a-z_]+)\]/', function ($match) use ($data) {return isset($data[$match[1]]) ? $data[$match[1]] : '';}, $str);
        }
        //自定义函数
        if (preg_match('/(.*?)\((.*)\)/', $str, $matches)) {
            return parseFunctionString($matches, $str, $data);
        }
    } elseif (0 === strpos($string, '[')) {
        // 支持读取配置参数（必须是数组类型）
        return config(substr($string, 1, -1));
    }

    $array = preg_split('/[,;\r\n]+/', trim($string, ",;\r\n"));
    if (strpos($string, ':')) {
        $value = array();
        foreach ($array as $val) {
            list($k, $v) = explode(':', $val);
            $value[$k]   = $v;
        }
    } else {
        $value = $array;
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
function int_to_string(&$data, $map = array('status' => array(1 => '正常', -1 => '删除', 0 => '禁用', 2 => '未审核', 3 => '草稿')))
{
    if ($data === false || $data === null) {
        return $data;
    }
    $data = (array) $data;
    foreach ($data as $key => $row) {
        foreach ($map as $col => $pair) {
            if (isset($row[$col]) && isset($pair[$row[$col]])) {
                $data[$key][$col . '_text'] = $pair[$row[$col]];
            }
        }
    }
    return $data;
}
