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

        //获取父级模型
        $model_list = $this->get_parent_model($model_id);

        // 模型数据合并
        $newArr       = [];
        $model_id_all = [];
        foreach ($model_list as &$value) {
            $model_id_all[] = $value['id'];
            $newArr         = array_merge($newArr, $value);
        }

        $model_list                 = $newArr;
        $model_list['model_id_all'] = $model_id_all;

        $model_list['model_id']        = $model_id;
        $model_list['model_config_id'] = $model_config_id;

        // $debug = debug('begin', 'end') . 's';
        // dump($debug);die;

        $this->Original[0] = $model_list;

        // 系统模型默认参数配置
        $model_list['url'] = request()->url();
        $this->info        = $model_list;

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
        return $this;
    }

}
