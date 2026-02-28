<?php
/**
 * CRUD 配置驱动生成器
 * 
 * 由 CRUD 可视化设计器自动生成
 * 生成时间: 2026-02-28 16:36:13
 */
class CrudConfig
{
    /**
     * 获取所有菜单配置
     */
    public static function getMenus()
    {
        return [
            '仪表盘' => [
                'items' => [
                    [
                        'name' => '仪表盘',
                        'page' => 'dashboard',
                        'icon' => 'layui-icon-home',
                    ],
                ],
            ],
            '内容管理' => [
                'icon' => 'layui-icon-template-1',
                'items' => [
                    [
                        'name' => '文章管理',
                        'page' => 'articles',
                        'icon' => 'layui-icon-file',
                    ],
                ],
            ],
            '用户管理' => [
                'icon' => 'layui-icon-cart-simple',
                'items' => [
                    [
                        'name' => '用户管理',
                        'page' => 'users',
                        'icon' => 'layui-icon-user',
                    ],
                ],
            ],
            '系统管理' => [
                'icon' => 'layui-icon-set',
                'items' => [
                    [
                        'name' => '管理员管理',
                        'page' => 'admins',
                        'icon' => 'layui-icon-username',
                    ],
                    [
                        'name' => '修改密码',
                        'page' => 'changePassword',
                        'icon' => 'layui-icon-password',
                    ],
                    [
                        'name' => 'CRUD设计器',
                        'page' => 'crud_designer',
                        'icon' => 'layui-icon-layouts',
                    ],
                ],
            ],
        ];
    }

    /**
     * 数据统计配置
     */
    public static function dashboard()
    {
        return [
            'page' => [
                'title' => '数据统计',
                'icon' => 'layui-icon-chart',
            ],
            'stats' => [
                [
                    'title' => '用户总数',
                    'value' => 0,
                    'icon' => 'layui-icon-user',
                    'color' => '#1E90FF',
                    'url' => '/api/admin/stats/users',
                ],
                [
                    'title' => '文章总数',
                    'value' => 0,
                    'icon' => 'layui-icon-file',
                    'color' => '#FF6B6B',
                    'url' => '/api/admin/stats/articles',
                ],
                [
                    'title' => '今日浏览',
                    'value' => 0,
                    'icon' => 'layui-icon-chart',
                    'color' => '#4ECDC4',
                    'url' => '/api/admin/stats/views',
                ],
                [
                    'title' => '系统运行',
                    'value' => '正常',
                    'icon' => 'layui-icon-ok-circle',
                    'color' => '#95E1D3',
                    'url' => '/api/admin/stats/system',
                ],
            ],
            'shortcuts' => [
                [
                    'name' => '新增文章',
                    'page' => 'articles',
                    'action' => 'add',
                    'icon' => 'layui-icon-add-1',
                ],
                [
                    'name' => '新增用户',
                    'page' => 'users',
                    'action' => 'add',
                    'icon' => 'layui-icon-username',
                ],
                [
                    'name' => '修改密码',
                    'page' => 'changePassword',
                    'icon' => 'layui-icon-password',
                ],
            ],
        ];
    }

    /**
     * 用户管理配置
     */
    public static function users()
    {
        return [
            'page' => [
                'title' => '用户管理',
                'icon' => 'layui-icon-user',
                'page' => 'users',
            ],
            'table' => [
                'url' => '/api/admin/users',
                'actionsWidth' => 180,
                'page' => true,
                'limit' => 10,
                'cols' => [
                    [
                        'field' => 'id',
                        'title' => 'ID',
                        'width' => 120,
                        'sort' => true,
                    ],
                    [
                        'field' => 'username',
                        'title' => '用户名',
                        'width' => '',
                        'sort' => true,
                    ],
                    [
                        'field' => 'nickname',
                        'title' => '昵称',
                        'width' => '',
                    ],
                    [
                        'field' => 'email',
                        'title' => '邮箱',
                        'width' => 200,
                    ],
                    [
                        'field' => 'phone',
                        'title' => '手机号',
                        'width' => 150,
                        'type' => '',
                    ],
                    [
                        'field' => 'status',
                        'title' => '状态',
                        'width' => 100,
                        'templet' => '',
                        'type' => 'select',
                        'options' => [
                            [
                                'value' => '1',
                                'label' => '正常',
                                'color' => 'blue',
                            ],
                            [
                                'value' => '0',
                                'label' => '禁用',
                                'color' => '#ccc',
                            ],
                        ],
                        'sort' => true,
                    ],
                    [
                        'field' => 'created_at',
                        'title' => '创建时间',
                        'width' => 180,
                        'type' => 'datetime',
                    ],
                    [
                        'title' => '过期时间',
                        'field' => 'expired_at',
                        'type' => 'datetime',
                        'width' => 180,
                    ],
                ],
            ],
            'actions' => [
                [
                    'text' => '编辑',
                    'action' => 'edit',
                    'icon' => 'layui-icon-edit',
                    'class' => 'layui-btn-normal',
                    'permission' => 'update',
                ],
                [
                    'text' => '删除',
                    'action' => 'delete',
                    'icon' => 'layui-icon-delete',
                    'class' => 'layui-btn-danger',
                    'permission' => 'delete',
                ],
            ],
            'search' => [
                [
                    'type' => 'input',
                    'name' => 'keyword',
                    'placeholder' => '搜索用户名/邮箱/手机',
                    'width' => 250,
                ],
                [
                    'type' => 'select',
                    'name' => 'aaa',
                    'placeholder' => 'aaa',
                    'options' => [
                        [
                            'value' => '1',
                            'label' => '启用',
                        ],
                        [
                            'value' => '0',
                            'label' => '禁用',
                        ],
                    ],
                    'url' => '/api/admin/users',
                    'valueField' => 'id',
                    'labelField' => 'nickname',
                ],
            ],
            'toolbar' => [
                [
                    'text' => '新增用户',
                    'icon' => 'layui-icon-add-1',
                    'class' => 'layui-btn-normal',
                    'action' => 'add',
                    'permission' => 'create',
                ],
            ],
            'form' => [
                [
                    'type' => 'input',
                    'name' => 'username',
                    'label' => '用户名',
                    'verify' => '',
                    'disabled_on_edit' => true,
                    'required_on_add' => true,
                    'required_on_edit' => true,
                    'hidden_on_add' => false,
                    'hidden_on_edit' => false,
                    'disabled_on_add' => false,
                ],
                [
                    'type' => 'input',
                    'name' => 'nickname',
                    'label' => '昵称',
                    'required' => true,
                ],
                [
                    'type' => 'input',
                    'name' => 'email',
                    'label' => '邮箱',
                    'inputType' => 'email',
                ],
                [
                    'type' => 'input',
                    'name' => 'phone',
                    'label' => '手机号',
                ],
                [
                    'type' => 'password',
                    'name' => 'password',
                    'label' => '密码',
                    'placeholder' => '新增时必填，编辑时留空不修改',
                    'required' => 'add',
                ],
                [
                    'type' => 'switch',
                    'name' => 'status',
                    'label' => '状态',
                    'options' => [
                        [
                            'value' => 1,
                            'title' => '正常',
                            'checked' => true,
                        ],
                        [
                            'value' => 0,
                            'title' => '禁用',
                        ],
                    ],
                    'text' => '正常|禁用',
                    'checkedValue' => 2,
                    'uncheckedValue' => 0,
                    'switch_default' => 1,
                    'theme' => '#6f1919',
                ],
                [
                    'type' => 'timestamp',
                    'name' => 'expired_at',
                    'label' => '过期时间',
                    'disabled_on_edit' => false,
                ],
            ],
            'api' => [
                'list' => '/api/admin/users',
                'add' => '/api/admin/user',
                'edit' => '/api/admin/user/{id}',
                'delete' => '/api/admin/user/{id}',
            ],
        ];
    }

    /**
     * 文章管理配置
     */
    public static function articles()
    {
        return [
            'page' => [
                'title' => '文章管理',
                'icon' => 'layui-icon-template-1',
                'page' => 'articles',
            ],
            'table' => [
                'url' => '/api/admin/articles',
                'actionsWidth' => 250,
                'cols' => [
                    [
                        'field' => 'id',
                        'title' => 'ID',
                        'width' => 120,
                        'sort' => true,
                    ],
                    [
                        'field' => 'title',
                        'title' => '标题',
                        'minWidth' => '',
                        'width' => '',
                        'sort' => true,
                        'type' => 'link',
                        'url' => '/article/{id}',
                    ],
                    [
                        'field' => 'author',
                        'title' => '作者',
                        'width' => 220,
                        'type' => 'auto',
                    ],
                    [
                        'field' => 'cover',
                        'title' => '封面图',
                        'width' => 200,
                        'type' => 'auto',
                        'sort' => false,
                    ],
                    [
                        'field' => 'is_published',
                        'title' => '是否发布',
                        'width' => 100,
                        'type' => 'switch',
                        'text' => '发布|草稿',
                    ],
                    [
                        'field' => 'publish_date',
                        'title' => '发布日期',
                        'width' => 180,
                        'type' => '',
                    ],
                    [
                        'field' => 'views',
                        'title' => '浏览量',
                        'width' => 100,
                    ],
                    [
                        'field' => 'created_at',
                        'title' => '创建时间',
                        'width' => 180,
                    ],
                ],
            ],
            'actions' => [
                [
                    'text' => '编辑',
                    'action' => 'edit',
                    'icon' => 'layui-icon-edit',
                    'class' => 'layui-btn-normal',
                    'permission' => 'update',
                ],
                [
                    'text' => '删除',
                    'action' => 'delete',
                    'icon' => 'layui-icon-delete',
                    'class' => 'layui-btn-danger',
                    'permission' => 'delete',
                ],
                [
                    'text' => '预览',
                    'action' => 'preview',
                    'permission' => 'custom',
                    'icon' => 'layui-icon-file',
                    'class' => 'layui-btn-warm',
                    'type' => 'iframe',
                    'url' => '/article/{id}',
                    'width' => '1600px',
                    'height' => '80%',
                ],
            ],
            'search' => [
                [
                    'type' => 'input',
                    'name' => 'keyword',
                    'placeholder' => '搜索标题或作者',
                    'width' => 250,
                ],
            ],
            'toolbar' => [
                [
                    'text' => '新增文章',
                    'icon' => 'layui-icon-add-1',
                    'class' => 'layui-btn-normal',
                    'action' => 'add',
                    'permission' => 'create',
                ],
                [
                    'text' => '导出数据',
                    'icon' => 'layui-icon-export',
                    'class' => 'layui-btn-warm',
                    'action' => 'export',
                    'permission' => 'export',
                ],
                [
                    'text' => '前端文章列表',
                    'action' => 'preview',
                    'permission' => 'custom',
                    'icon' => 'layui-icon-list',
                    'class' => 'layui-btn-danger',
                    'type' => 'iframe',
                    'url' => '/articles',
                    'width' => '1600px',
                    'height' => '80%',
                ],
            ],
            'form' => [
                [
                    'type' => 'input',
                    'name' => 'title',
                    'label' => '标题',
                    'required' => true,
                    'verify' => 'required',
                    'placeholder' => '请输入文章标题',
                    'required_on_add' => true,
                    'required_on_edit' => true,
                    'disabled_on_edit' => false,
                ],
                [
                    'type' => 'select',
                    'name' => 'author',
                    'label' => '作者',
                    'required' => true,
                    'url' => '/api/admin/users',
                    'valueField' => 'id',
                    'labelField' => 'nickname',
                    'verify' => '',
                    'options' => '',
                    'required_on_add' => true,
                    'required_on_edit' => true,
                ],
                [
                    'type' => 'editor',
                    'name' => 'content',
                    'label' => '内容',
                    'height' => '400px',
                    'required' => true,
                    'tip' => '支持富文本编辑',
                    'required_on_add' => true,
                    'required_on_edit' => true,
                ],
                [
                    'type' => 'image',
                    'name' => 'cover',
                    'label' => '封面图',
                    'uploadUrl' => '/api/admin/upload',
                    'tip' => '建议尺寸：800x600，格式：JPG/PNG',
                    'exts' => 'jpg|png',
                    'length' => 3,
                    'text' => '启用|无用',
                    'theme' => '#d52222',
                    'min' => 0,
                    'max' => 50,
                    'step' => 2,
                ],
                [
                    'type' => 'date',
                    'name' => 'publish_date',
                    'label' => '发布日期',
                    'placeholder' => '请选择发布日期',
                ],
                [
                    'type' => 'switch',
                    'name' => 'is_published',
                    'label' => '是否发布',
                    'text' => '发布|草稿',
                    'checkedValue' => 1,
                ],
            ],
            'api' => [
                'list' => '/api/admin/articles',
                'add' => '/api/admin/article',
                'edit' => '/api/admin/article/{id}',
                'delete' => '/api/admin/article/{id}',
            ],
        ];
    }

    /**
     * 管理员管理配置
     */
    public static function admins()
    {
        return [
            'page' => [
                'title' => '管理员管理',
                'icon' => 'layui-icon-username',
                'page' => 'admins',
            ],
            'table' => [
                'url' => '/api/admin/admins',
                'page' => true,
                'limit' => 10,
                'cols' => [
                    [
                        'field' => 'id',
                        'title' => 'ID',
                        'width' => 80,
                        'sort' => true,
                    ],
                    [
                        'field' => 'username',
                        'title' => '用户名',
                        'width' => 150,
                    ],
                    [
                        'field' => 'nickname',
                        'title' => '昵称',
                        'width' => 150,
                    ],
                    [
                        'field' => 'email',
                        'title' => '邮箱',
                        'width' => 200,
                    ],
                    [
                        'field' => 'phone',
                        'title' => '手机号',
                        'width' => 150,
                    ],
                    [
                        'field' => 'permissions_text',
                        'title' => '权限',
                        'width' => 200,
                    ],
                    [
                        'field' => 'status',
                        'title' => '状态',
                        'width' => 100,
                        'type' => 'switch',
                    ],
                    [
                        'field' => 'last_login_time',
                        'title' => '最后登录',
                        'width' => 180,
                    ],
                    [
                        'field' => 'created_at',
                        'title' => '创建时间',
                        'width' => 180,
                    ],
                    [
                        'field' => 'expired_at',
                        'title' => '过期时间',
                        'type' => 'datetime',
                        'width' => 180,
                    ],
                ],
            ],
            'actions' => [
                [
                    'text' => '编辑',
                    'action' => 'edit',
                    'icon' => 'layui-icon-edit',
                    'class' => 'layui-btn-normal',
                    'permission' => 'update',
                ],
                [
                    'text' => '删除',
                    'action' => 'delete',
                    'icon' => 'layui-icon-delete',
                    'class' => 'layui-btn-danger',
                    'permission' => 'delete',
                ],
            ],
            'search' => [
                [
                    'type' => 'input',
                    'name' => 'keyword',
                    'placeholder' => '搜索用户名/昵称/邮箱',
                    'width' => 250,
                ],
            ],
            'toolbar' => [
                [
                    'text' => '新增管理员',
                    'icon' => 'layui-icon-add-1',
                    'class' => 'layui-btn-normal',
                    'action' => 'add',
                    'permission' => 'create',
                ],
            ],
            'form' => [
                [
                    'type' => 'input',
                    'name' => 'username',
                    'label' => '用户名',
                    'required' => true,
                    'placeholder' => '4-20位字母、数字、下划线',
                    'disabled_on_edit' => true,
                ],
                [
                    'type' => 'password',
                    'name' => 'password',
                    'label' => '密码',
                    'required' => 'add',
                    'placeholder' => '6-20位，包含字母和数字',
                    'tip' => '编辑时留空表示不修改密码',
                ],
                [
                    'type' => 'input',
                    'name' => 'nickname',
                    'label' => '昵称',
                    'required' => true,
                ],
                [
                    'type' => 'input',
                    'name' => 'email',
                    'label' => '邮箱',
                    'required' => false,
                ],
                [
                    'type' => 'input',
                    'name' => 'phone',
                    'label' => '手机号',
                    'required' => false,
                ],
                [
                    'type' => 'permissions',
                    'name' => 'permissions',
                    'label' => '权限配置',
                    'required' => false,
                    'hidden_on_super_admin' => true,
                ],
                [
                    'type' => 'switch',
                    'name' => 'status',
                    'label' => '状态',
                    'required' => false,
                    'text' => '启用|禁用',
                    'hidden_on_super_admin' => true,
                ],
                [
                    'type' => 'timestamp',
                    'name' => 'expired_at',
                    'label' => '过期时间',
                    'required' => false,
                    'hidden_on_super_admin' => true,
                ],
            ],
            'api' => [
                'list' => '/api/admin/admins',
                'add' => '/api/admin/admin',
                'edit' => '/api/admin/admin/{id}',
                'delete' => '/api/admin/admin/{id}',
                'permissions' => '/api/admin/permissions',
            ],
            'tips' => [
                'delete' => 'ID=1 的超级管理员禁止删除',
                'edit_super_admin' => '超级管理员只能修改基本信息，无法修改权限和状态',
            ],
        ];
    }

    /**
     * 修改密码配置
     */
    public static function changePassword()
    {
        return [
            'page' => [
                'title' => '修改密码',
                'icon' => 'layui-icon-password',
                'type' => 'form',
            ],
            'form' => [
                [
                    'type' => 'password',
                    'name' => 'old_password',
                    'label' => '原密码',
                    'required' => true,
                    'verify' => 'required',
                ],
                [
                    'type' => 'password',
                    'name' => 'new_password',
                    'label' => '新密码',
                    'required' => true,
                    'verify' => 'required|password',
                    'placeholder' => '至少6位',
                ],
                [
                    'type' => 'password',
                    'name' => 'confirm_password',
                    'label' => '确认密码',
                    'required' => true,
                    'verify' => 'required|confirmPassword',
                ],
            ],
            'tips' => [
                'type' => 'info',
                'text' => '为了您的账号安全，建议定期更换密码，密码长度不少于6位',
            ],
            'api' => [
                'submit' => '/api/admin/change-password',
            ],
        ];
    }

}
