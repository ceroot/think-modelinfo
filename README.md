## ceroot-modelinfo
think模型解析模块  
> 以下类库都在`\\ceroot\\modelinfo`命名空间下

> Quiet   静态解析类

> Syserm  系统模型解析类

安装
> composer require ceroot/think-modelinfo

自定义模型信息参考
//自定义模型信息
```php
    $data = [
        //default 默认配置(action方法名称做为下标 action没有配置的取default, defaul定义了的在action会继承和可覆盖)
        'default'=>[
            //表单提交地址
            'url' => 'Config/updates',
            //操作方法(方法不存在的时候起作用)
            'action'=>'Config/index',
            //特殊字符串替换用于列表定义解析
            'replace_string' => [['[DELETE]','[EDIT]'],['delete?ids=[id]','edit?id=[id]']],
            //按钮组 用于模版的显示
            'button'     => [
                ['title'=>'新增','url'=>'add','icon'=>'iconfont icon-xinzeng','class'=>'list_add btn-success','ExtraHTML'=>''],
                ['title'=>'删除','url'=>'del','icon'=>'iconfont icon-shanchu','class'=>'btn-danger ajax-post confirm','ExtraHTML'=>'target-form="ids"'],
                ['title'=>'排序','url'=>'sort','icon'=>'iconfont icon-paixu','class'=>'btn-info list_sort','ExtraHTML'=>'']
            ],
            //表名
            'name' => 'config',
            //主键
            'pk' => 'id',
            //列表定义
            'list_grid'  => 'id:ID;name:名称:[EDIT];title:标题;update_time:最后更新;group|get_config_group:分组;type|get_config_type:类型;id:操作:[EDIT]|编辑,del?id=[id]|删除',
            //列表头即列表定义后解析的规则  由系统根据list_grid列表定义完成
            'list_field' => [],
            //验证字段属性信息 由系统完成 在fields设置
            'validate' => [],
            //自由组合的搜索字段  ['字段'=>'标题'] 为空取列表定义的
            'search_list'=> [
            	["name" =>"status","title" => "数据状态", "exp" => "eq","value" => "1" ,"type" => "select","extra" => "-1:假删除,0:禁用,1:启用,2:审核"]
                ], 
            //固定搜索条件
            'search_fixed' => [
            	["name" => "category_id", "exp" => "eq" ,"value" =>":[cate_id]"]
            ], 
            //表单显示分组
            'field_group'=>'1:基础',
            //表单显示排序
            "fields"=>[
                '1'=>[
                    ['name'=>'id','title'=>'UID','type'=>'string','remark'=>'','is_show'=>4],
                    ['name'=>'name','title'=>'配置标识','type'=>'string','remark'=>'用于C函数调用，只能使用英文且不能重复','is_show'=>1],
                    ['name'=>'title','title'=>'配置标题','type'=>'string','remark'=>'用于后台显示的配置标题','is_show'=>1],
                    ['name'=>'sort','title'=>'排序','type'=>'string','remark'=>'用于分组显示的顺序','is_show'=>1],
                    ['name'=>'type','title'=>'配置类型','type'=>'select','extra'=>':config_type_list()','value'=>'','remark'=>'系统会根据不同类型解析配置值','is_show'=>1],
                    ['name'=>'group','title'=>'配置分组','type'=>'select','extra'=>':config_group_list()','value'=>'','remark'=>'配置分组 用于批量设置 不分组则不会显示在系统设置中','is_show'=>1],
                    ['name'=>'value','title'=>'配置值','type'=>'textarea','remark'=>'配置值','is_show'=>1],
                    ['name'=>'extra','title'=>'配置项','type'=>'textarea','remark'=>'如果是枚举型 需要配置该项','is_show'=>1],
                    ['name'=>'remark','title'=>'说明','type'=>'textarea','remark'=>'配置详细说明','is_show'=>1],
                ]
            ],
            //列表模板
            'template_list'=>'mould/list',
            //新增模板
            'template_add'=>'mould/add',
            //编辑模板
            'template_edit'=>'mould/edit',
            //当前模版(使用以上3种模版配置请设置为false)
            'template'=>false,
            //列表数据大小
             'list_row'=>'10',
        ],
        'group'=>[
            'url' => 'Config/save',
        ],
        'add'=>[
            'meta_title' => '新增配置',
        ],
        'edit'=>[
            'meta_title' => '编辑配置',
        ]
    ];
```

使用
> 实例化
```php
modelinfo();
```

> 列表
```php
modelinfo()->getList($data);
```

> 添加
```php
modelinfo()->getAdd($data);
```

> 编辑
```php
modelinfo()->getEdit($data);
```

2018.08.24（1.0.8）
> 1.增加表单默认值，以显示样式判断

> 2.修复php5.1数组条件

> 3.修复视图查询里的获取表字段信息

2018.08.27（1.0.9）
> 1.完善注释

> 2.修复表单提交的 url

2018.08.29（1.1.0）
> 1.改进更新数据时返回操作数据id

2018.08.31（1.1.1）
> 1.改进列表操作显示

2018.09.03（1.1.2）
> 1.改进默认值的输入

> 2.其它

2018.09.03（1.1.3）
> 1.修正提示表名不存在，主要是大写与下划线之间

> 2.增加 toUnderline 方法用来驼峰命名转换下划线命名

> 3.其它

2018.09.06（1.1.4）
> 1.改进列表解析

> 2.系统特殊字符串定义改变位置

> 3.增加 layui 动态表格扩展信息默认值

> 4.其它

2018.09.06（1.1.5）
> 1.改进列表动作显示输出

> 2.增加 layui table 扩展输出，更容易控制表格样式

> 3.增加特殊字符串替换[UPDATEFIELD]

> 4.改进其它特殊字符串替换

> 5.其它改进

2018.09.07（1.1.6）
> 1.完善搜索功能

> 2.其它

2018.09.07（1.1.61）
> 1.修复 url 获取

2018.09.11（1.1.62）
> 1.改进表单 url 里的参数没用

> 2.其它 