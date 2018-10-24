<?php
// +----------------------------------------------------------------------
// | benweng [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2018 https://www.benweng.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: SpringYang  82550565@qq.com <www.benweng.com>
// +----------------------------------------------------------------------

namespace ceroot\modelinfo;

use app\common\model\Model as ModelModel;
use app\console\model\ModelConfig;
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

        $not_field = ['create_ip', 'create_uid', 'create_uid', 'create_time', 'update_ip', 'update_uid', 'update_time', 'delete_ip', 'delete_uid', 'delete_time'];

        $model_info        = (new ModelModel())->field($not_field, true)->find($model_id);
        $this->Original[0] = $model_info;

        // 模型配置处理
        // 模型配置查询条件
        $map[] = ['model_id', 'eq', $model_id];
        if ($model_config_id === '') {
            $action = Request::action();
            $map[]  = ['action', 'eq', strtolower($action)];
        } else {
            if (is_numeric($model_config_id)) {
                $map[] = ['id', 'eq', $model_config_id];
            } else {
                $map[]           = ['action', 'eq', strtolower($model_config_id)];
                $model_config_id = '';
            }
        }

        // dump($map);

        // 取得模型配置数据
        $model_config = (new ModelConfig())->field(array_merge($not_field, ['not_field']), true)->where($map)->find();

        // dump($model_config);
        if ($model_config) {
            if (is_object($model_config)) {
                $model_config = $model_config->toArray();
            }
            $model_info = array_merge($model_info->toArray(), $model_config);

            $model_config_id = $model_config['id'];
        } else {
            $model_config_id = '';
        }

        $model_info['model_id']        = $model_id;
        $model_info['model_config_id'] = $model_config_id;

        // $debug = debug('begin', 'end') . 's';
        // dump($debug);die;

        // 系统模型默认参数配置
        $model_info['url'] = request()->url();
        $this->info        = $model_info;

        return $this;
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
            $model_id = $this->info['model_id'];
        }

        if (!$model_config_id) {
            $model_config_id = $this->info['model_config_id'];
        }
        // dump($model_config_id);
        // $fields = get_model_attribute($model_id, $model_config_id);
        $fields = (new ModelModel())->getModelAttribute($model_id);

        foreach ($fields as $key => $value) {
            $data_name = array_column($value, 'name');
            if (count($data_name) == count(array_filter($data_name))) {
                $this->info['fields'][$key] = Array_mapping($fields[$key], 'name');
            }

        }

        // die;
        return $this;
    }

}
