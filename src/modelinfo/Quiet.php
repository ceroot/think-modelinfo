<?php
// +----------------------------------------------------------------------
// | benweng [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2018 https://www.benweng.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: SpringYang  82550565@qq.com <www.benweng.com>
// +----------------------------------------------------------------------

namespace ceroot\modelinfo;

use think\facade\Request;

/*
 * @title静态模型定义处理类
 * @Author: SpringYang <ceroot@163.com>
 */
class Quiet extends Base
{
    protected $ZOriginal; // 最初模型数据
    private $defaul_config;

    public function __construct()
    {
        parent::__construct();
        $this->defaul_config = [
            // default 默认配置(action方法名称做为下标 action没有配置的取default, defaul定义了的在action会继承和可覆盖)
            'default' => [
                // 表单提交地址
                'url'                 => Request::url(),
                // 操作方法(方法不存在的时候起作用)
                'action'              => '',
                // 特殊字符串替换用于列表定义解析
                'replace_string'      => $this->replace_string,
                // updatefield?field=status&value=0&id=[id]
                // 按钮组 用于模版的显示
                // ['title' => '新增', 'url' => 'add', 'icon' => 'iconfont icon-xinzeng', 'class' => 'list_add btn-success', 'ExtraHTML' => ''],
                // ['title' => '删除', 'url' => 'del', 'icon' => 'iconfont icon-shanchu', 'class' => 'btn-danger ajax-post confirm', 'ExtraHTML' => 'target-form="ids"'],
                // ['title' => '排序', 'url' => 'sort', 'icon' => 'iconfont icon-paixu', 'class' => 'btn-info list_sort', 'ExtraHTML' => ''],
                'button'              => [],
                // 表名
                'name'                => Request::controller(),
                //主键
                'pk'                  => 'id',
                // 主键值是否加密，默认加密
                'pk_value_encryption' => true,
                // 列表定义
                'list_grid'           => '', // id:ID;name:名称:[EDIT];title:标题;update_time:最后更新;group|get_config_group:分组;type|get_config_type:类型;id:操作:[EDIT]|编辑,del?id=[id]|删除
                // 列表头即列表定义后解析的规则  由系统根据 list_grid 列表自动生成
                // 'list_field'     => [],
                // 表格扩展信息 layui 动态表格
                'table_extend'        => [],
                //验证字段属性信息 由系统完成 在fields设置
                'validate'            => [],
                // 自由组合的搜索字段  ['字段'=>'标题'] 为空取列表定义的
                // ["name" => "status", "title" => "数据状态", "exp" => "eq", "value" => "1", "type" => "select", "extra" => "-1:假删除,0:禁用,1:启用,2:审核"],
                'search_list'         => [],
                // 固定搜索条件 // ["name" => "category_id", "exp" => "eq", "value" => ":[cate_id]"],
                'search_fixed'        => [],
                // 表单显示分组
                'field_group'         => '', // 1:基础,2:扩展
                // 表单显示排序
                // '1' => [
                //     ['name' => 'id', 'title' => 'UID', 'type' => 'string', 'remark' => '说明内容', 'isshow' => 0, 'ExtraHTML' => 'lay-verify=required|phone|number'],
                // ],
                // '2'  => [
                //     ['name' => 'id', 'title' => 'UID', 'type' => 'string', 'remark' => '说明内容', 'isshow' => 0],
                // ],
                "fields"              => [],
                // 列表模板
                'template_lists'      => 'mould/lists',
                // 新增模板
                'template_add'        => 'mould/add',
                // 编辑模板
                'template_edit'       => 'mould/edit',
                // 当前模版(使用以上3种模版配置请设置为false)
                'template'            => false,
                // 列表数据大小
                'list_row'            => '15',
            ],
        ];
    }

    // 初始化
    public function info($modelinfo)
    {
        // dump($this->defaul_config);
        $info  = $this->ZOriginal  = $modelinfo;
        $scene = $this->scene = $this->scene ?: Request::action();
        // dump($info);
        // die;
        // 合并默认
        $info['default'] = isset($info['default']) ? array_merge($this->defaul_config['default'], $info['default']) : $this->defaul_config['default'];
        $info            = (isset($info[$scene]) && isset($info['default'])) ? array_merge($info['default'], $info[$scene]) : $info['default'];

        // 当前操作模型信息
        // $info            = (isset($info[$scene]) && isset($info['default'])) ? array_merge($info['default'], $info[$scene]) : $info['default'];
        // die;
        $this->Original[0] = $info; // 原始模型
        // $pk
        if (isset($info['pk'])) {
            $this->pk = $info['pk'];
        }

        // 处理表单样式显示默认值
        if (isset($info['fields'])) {
            $fields_defult = [
                'is_show' => 1,
                'inline'  => 1,
            ];

            $fields_arr = [];
            foreach ($info['fields'] as $key => $v) {
                foreach ($v as $value) {
                    $value              = array_merge($fields_defult, $value);
                    $fields_arr[$key][] = $value;
                }
            }
            $info['fields'] = $fields_arr;
        }
        $this->info = $info;
        // Button
        if (!empty($info['button'])) {
            $this->getButton($info['button']);
        }
        return $this;
    }

    /*
     * @title 列表定义解析
     * @param $list_grid 列表定义规则
     * @Author: SpringYang <ceroot@163.com>
     */
    public function getListField($list_grid = false)
    {
        if (!$list_grid && isset($this->info['list_grid'])) {
            $list_grid = $this->info['list_grid'];
            //删除原规则
            unset($this->info['list_grid']);
        }
        return parent::getListField($list_grid);
    }

    /*
     * 获取模型字段排序列表
     * @return $this
     * @Author: SpringYang <ceroot@163.com>
     */
    public function getFields($fields = false)
    {
        if (!$fields) {
            $fields = isset($this->info['fields']) ? $this->info['fields'] : [];
        }

        $new_arr = [];
        foreach ($fields as $key => $value) {
            $data_name     = array_column($value, 'name');
            $new_arr[$key] = array_combine($data_name, $value);
        }
        $this->info['fields'] = $new_arr;
        return $this;
    }

}
