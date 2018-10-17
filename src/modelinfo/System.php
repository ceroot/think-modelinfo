<?php
// +----------------------------------------------------------------------
// | benweng [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2018 https://www.benweng.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: SpringYang  82550565@qq.com <www.benweng.com>
// +----------------------------------------------------------------------

namespace ceroot\modelinfo;

use think\Exception;
use think\facade\App;
use think\facade\Request;

/*
 * @title 系统(动态)模型处理类用与后台系统模型的处理 非静态模型
 * @Author: SpringYang <ceroot@163.com>
 */
class System extends Base
{
    /*
     * @title 获取当前模型信息初始化
     * @Author: SpringYang <ceroot@163.com>
     */
    public function info($model_id = '', $model_config_id = '')
    {
        debug('begin');
        if (is_array($model_id)) {
            $model_id        = $model_id['model_id'];
            $model_config_id = $model_id['model_config_id'];
        }

        if ($action == '') {
            $action = Request::action();
            $action = 'add';

        }

        //获取父级模型
        $model_list = $this->get_parent_model($model_id);
        // dump($model_list);

        $newArr       = [];
        $_field_sort  = [];
        $model_id_all = [];

        foreach ($model_list as &$value) {
            $model_id_all[] = $value['id'];
            $_temp          = json_decode($value['field_sort'], true);

            if (count($_temp) > count($_field_sort)) {
                $max = $_temp;
                $min = $_field_sort;
            } else {
                $max = $_field_sort;
                $min = $_temp;
            }

            foreach ($max as $k => &$val) {
                if (isset($min[$k])) {
                    $val = array_unique(array_merge($min[$k], $val));
                }
            }

            $_field_sort = $max;

            $value['field_sort'] = $_field_sort = $max;
            $newArr              = array_merge($newArr, $value);
        }

        $model_list                 = $newArr;
        $model_list['model_id_all'] = $model_id_all;

        // dump($model_list);

        // $model_config = model('model_config')->field('title', true)->find($model_config_id);
        // $replace_string_text = $model_config->replace_string_text;
        // if (is_object($model_config)) {
        //     $model_config = $model_config->toArray();
        // }
        // $model_config['replace_string'] = $replace_string_text;
        // dump($model_config);

        if ($model_config_id) {
            $map[] = ['id', 'eq', $model_config_id];
        } else {
            $map[] = ['model_id', 'eq', $model_id];
            $map[] = ['action', 'eq', $action];
        }

        $model_config = App::model('model_config')->where($map)->find();

        if ($model_config) {
            if (is_object($model_config)) {
                $model_config = $model_config->toArray();
            }

            $_field_sort_1 = $model_list['field_sort'];
            $_field_sort_2 = json_decode($model_config['field_sort'], true);

            if (count($_field_sort_1) > count($_field_sort_2)) {
                $max = $_field_sort_1;
                $min = $_field_sort_2;
            } else {
                $max = $_field_sort_2;
                $min = $_field_sort_1;
            }

            foreach ($max as $k => &$val) {
                if (isset($min[$k])) {
                    $val = array_unique(array_merge($min[$k], $val));
                }
            }

            // dump($max);die;
            $model_list                    = array_merge($model_list, $model_config);
            $model_list['field_sort']      = $max;
            $model_list['model_config_id'] = $model_config['id'];
        } else {
            if (!isset($model_list['model_id'])) {
                $model_list['model_id'] = $model_id;
            }
            if (!isset($model_list['model_config_id'])) {
                $model_list['model_config_id'] = '';
            }
        }

        // dump($model_list);
        // debug('end');
        // $debug = debug('begin', 'end') . 's';
        // dump($debug);die;

        $this->Original[0] = $model_list;
        // dump($model_list);
        // $model_list = Array_mapping($model_list, 'id'); // 反把 id 作为键值
        // dump($model_list);
        // $modelinfo = $model_list[$model_config_id];

        //系统模型默认参数配置
        // $modelinfo['url']  = request()->url();
        $model_list['url'] = request()->url();
        // dump($modelinfo);die;
        $this->info = $model_list;

        return $this;
    }
    /*
     * 获取模型参数的所有父级模型列表
     * @param int $cid 模型id
     * @return array 参数模型和父模型的信息集合
     * @Author: SpringYang <ceroot@163.com>
     */
    public function get_parent_model($cid)
    {
        if (empty($cid)) {
            return false;
        }
        $cates = \Db::name('Model')->where('status', 'eq', 0)->select();
        $child = \Db::name('Model')->getById($cid); //获取参数模型的信息
        if (!$child) {
            throw new Exception("模型id:{$cid}不存在");
        }
        $pid   = $child['extend'];
        $temp  = [];
        $res[] = $child;
        while (true) {
            foreach ($cates as $key => $cate) {
                if ($cate['id'] == $pid) {
                    $pid = $cate['extend'];
                    array_unshift($res, $cate); //将父模型插入到数组第一个元素前
                }
            }
            if ($pid == 0) {
                break;
            }
        }
        return $res;
    }
    /*
     * @title 列表定义解析
     * @param $list_grid 列表定义规则
     * @param $type 1:单线模型往上级查找列表定义 2:绑定多个模型获取基础模型的列表定义(即分支模型V形模型)
     * @param $model_id 模型ID
     * @Author: SpringYang <ceroot@163.com>
     */
    public function getListField($list_grid = false)
    {
        if (!$list_grid) {
            $list_grid = $this->Original[0]['list_grid'];
        }
        return parent::getListField($list_grid);
    }

    /*
     * @title 获取高级搜索配置
     * @Author: SpringYang <ceroot@163.com>
     */
    public function getSearchList()
    {
        $search_list = $this->Original[0]['search_list'];
        if (empty($search_list)) {
            return $this;
        }

        //value extra规则解析
        foreach ($search_list as $key => &$value) {
            if (0 === strpos($value['value'], ':') || 0 === strpos($value['value'], '[')) {
                $value['value'] = parse_field_attr($value['value']);
            }
            if (!empty($value['extra'])) {
                $value['extra'] = parse_field_attr($value['extra']);
            }
        }
        $this->info['search_list'] = $search_list;
        return $this;
    }
    /*
     * @title 获取固定搜索配置
     * @Author: SpringYang <ceroot@163.com>
     */
    public function getSearchFixed()
    {
        $search_list = $this->Original[0]['search_fixed'];
        $param       = request()->param();
        //value 规则解析
        foreach ($search_list as $key => &$value) {
            if (0 === strpos($value['value'], ':') || 0 === strpos($value['value'], '[')) {
                $string = $value['value'];
                $str    = substr($string, 1);
                if (0 === strpos($str, '[')) {
                    if (preg_match('/\[([a-z_]+)\]/', $str, $matches)) {
                        if (!isset($param[$matches['1']])) {
                            unset($search_list[$key]);
                            continue;
                        }
                    }
                }
                $value['value'] = parse_field_attr($string);
            }
        }
        $this->info['search_fixed'] = $search_list;
        return $this;
    }
    /*
     * 获取模型字段排序列表
     * @param  $model_id 模型id
     * @return $this
     * @Author: SpringYang <ceroot@163.com>
     */
    public function getFields($model_id = '', $model_config_id = '')
    {
        if (!$model_id) {
            $model_id = $this->Original[0]['model_id'];
        }
        // dump($this->Original);
        // die;
        if (!$model_config_id) {
            $model_config_id = $this->Original[0]['model_config_id'];
        }
        // $fields = get_model_attribute($model_id, $model_config_id);
        $fields = model('Model')->getModelAttribute($model_id, $model_config_id);

        foreach ($fields as $key => $value) {
            $data_name = array_column($value, 'name');
            if (count($data_name) == count(array_filter($data_name))) {
                $this->info['fields'][$key] = Array_mapping($fields[$key], 'name');
            }

        }
        // dump($this->info);
        // die;
        return $this;
    }
    /*
     * @title 获取button组
     * @param $button 按钮规则
     * @Author: SpringYang <ceroot@163.com>
     */
    public function getButton($button = '')
    {
        if (empty($button)) {
            $button = $this->Original[0]['button'];
        }
        if (!empty($button)) {
            $param = request()->param();
            foreach ($button as $key => &$value) {
                // 替换数据变量
                $url = preg_replace_callback('/\[([a-z_]+)\]/', function ($match) use ($param) {
                    return isset($param[$match[1]]) ? $param[$match[1]] : '';
                }, $value['url']);
                $value['url'] = url($url, '', false);
            }
            $this->info['button'] = $button;
        }
        return $this;
    }
}
