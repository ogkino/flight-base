<?php
/**
 * CRUD 配置驱动生成器
 * 
 * 由 CRUD 可视化设计器自动生成
 * 生成时间: 2026-07-21 18:23:45
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
                'title' => '仪表盘',
                'title_en' => 'Dashboard',
                'items' => [
                    [
                        'name' => '仪表盘',
                        'name_en' => 'Dashboard',
                        'page' => 'dashboard',
                        'icon' => 'layui-icon-home',
                    ],
                ],
            ],
            '内容管理' => [
                'title' => '内容管理',
                'title_en' => 'Content',
                'icon' => 'layui-icon-template-1',
                'items' => [
                    [
                        'name' => '文章管理',
                        'name_en' => 'Articles',
                        'page' => 'articles',
                        'icon' => 'layui-icon-file',
                    ],
                ],
            ],
            '用户管理' => [
                'title' => '用户管理',
                'title_en' => 'Users',
                'icon' => 'layui-icon-cart-simple',
                'items' => [
                    [
                        'name' => '用户管理',
                        'name_en' => 'Users',
                        'page' => 'users',
                        'icon' => 'layui-icon-user',
                    ],
                ],
            ],
            '系统管理' => [
                'title' => '系统管理',
                'title_en' => 'System',
                'icon' => 'layui-icon-set',
                'items' => [
                    [
                        'name' => '管理员管理',
                        'name_en' => 'Administrators',
                        'page' => 'admins',
                        'icon' => 'layui-icon-username',
                    ],
                    [
                        'name' => '字典管理',
                        'name_en' => 'Dictionaries',
                        'page' => 'dictType',
                        'icon' => 'layui-icon-template',
                    ],
                    [
                        'name' => '修改密码',
                        'name_en' => 'Change Password',
                        'page' => 'changePassword',
                        'icon' => 'layui-icon-password',
                    ],
                    [
                        'name' => 'CRUD设计器',
                        'name_en' => 'CRUD Designer',
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
                'title_en' => 'Overview',
                'icon' => 'layui-icon-chart',
            ],
            'stats' => [
                [
                    'title' => '用户总数',
                    'title_en' => 'Total Users',
                    'value' => 0,
                    'icon' => 'layui-icon-user',
                    'color' => '#1E90FF',
                    'url' => '/api/admin/stats/users',
                ],
                [
                    'title' => '文章总数',
                    'title_en' => 'Total Articles',
                    'value' => 0,
                    'icon' => 'layui-icon-file',
                    'color' => '#FF6B6B',
                    'url' => '/api/admin/stats/articles',
                ],
                [
                    'title' => '今日浏览',
                    'title_en' => 'Views Today',
                    'value' => 0,
                    'icon' => 'layui-icon-chart',
                    'color' => '#4ECDC4',
                    'url' => '/api/admin/stats/views',
                ],
                [
                    'title' => '系统运行',
                    'title_en' => 'System Status',
                    'value' => '正常',
                    'value_en' => 'Normal',
                    'icon' => 'layui-icon-ok-circle',
                    'color' => '#95E1D3',
                    'url' => '/api/admin/stats/system',
                ],
            ],
            'shortcuts' => [
                [
                    'name' => '新增文章',
                    'name_en' => 'New Article',
                    'page' => 'articles',
                    'action' => 'add',
                    'icon' => 'layui-icon-add-1',
                ],
                [
                    'name' => '新增用户',
                    'name_en' => 'New User',
                    'page' => 'users',
                    'action' => 'add',
                    'icon' => 'layui-icon-username',
                ],
                [
                    'name' => '修改密码',
                    'name_en' => 'Change Password',
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
                'title_en' => 'User Management',
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
                        'title_en' => 'Username',
                        'width' => '',
                        'sort' => true,
                    ],
                    [
                        'field' => 'nickname',
                        'title' => '昵称',
                        'title_en' => 'Nickname',
                        'width' => '',
                    ],
                    [
                        'field' => 'email',
                        'title' => '邮箱',
                        'title_en' => 'Email',
                        'width' => 200,
                    ],
                    [
                        'field' => 'phone',
                        'title' => '手机号',
                        'title_en' => 'Phone',
                        'width' => 150,
                        'type' => '',
                    ],
                    [
                        'field' => 'status',
                        'title' => '状态',
                        'title_en' => 'Status',
                        'width' => 100,
                        'templet' => '',
                        'type' => 'select',
                        'options' => [
                            [
                                'value' => '1',
                                'label' => '正常',
                                'label_en' => 'Enabled',
                                'color' => 'blue',
                            ],
                            [
                                'value' => '0',
                                'label' => '禁用',
                                'label_en' => 'Disabled',
                                'color' => '#ccc',
                            ],
                        ],
                        'sort' => true,
                    ],
                    [
                        'field' => 'created_at',
                        'title' => '创建时间',
                        'title_en' => 'Created At',
                        'width' => 180,
                        'type' => 'datetime',
                    ],
                    [
                        'title' => '过期时间',
                        'title_en' => 'Expires At',
                        'field' => 'expired_at',
                        'type' => 'datetime',
                        'width' => 180,
                    ],
                ],
            ],
            'actions' => [
                [
                    'text' => '编辑',
                    'text_en' => 'Edit',
                    'action' => 'edit',
                    'icon' => 'layui-icon-edit',
                    'class' => 'layui-btn-normal',
                    'permission' => 'update',
                ],
                [
                    'text' => '删除',
                    'text_en' => 'Delete',
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
                    'placeholder_en' => 'Search username/email/phone',
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
                            'label_en' => 'Enabled',
                        ],
                        [
                            'value' => '0',
                            'label' => '禁用',
                            'label_en' => 'Disabled',
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
                    'text_en' => 'Add User',
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
                    'label_en' => 'Username',
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
                    'label_en' => 'Nickname',
                    'required' => true,
                ],
                [
                    'type' => 'input',
                    'name' => 'email',
                    'label' => '邮箱',
                    'label_en' => 'Email',
                    'inputType' => 'email',
                ],
                [
                    'type' => 'input',
                    'name' => 'phone',
                    'label' => '手机号',
                    'label_en' => 'Phone',
                ],
                [
                    'type' => 'password',
                    'name' => 'password',
                    'label' => '密码',
                    'label_en' => 'Password',
                    'placeholder' => '新增时必填，编辑时留空不修改',
                    'placeholder_en' => 'Required when adding, leave blank to keep unchanged when editing',
                    'required' => 'add',
                ],
                [
                    'type' => 'switch',
                    'name' => 'status',
                    'label' => '状态',
                    'label_en' => 'Status',
                    'options' => [
                        [
                            'value' => 1,
                            'title' => '正常',
                            'title_en' => 'Enabled',
                            'checked' => true,
                        ],
                        [
                            'value' => 0,
                            'title' => '禁用',
                            'title_en' => 'Disabled',
                        ],
                    ],
                    'text' => '正常|禁用',
                    'text_en' => 'Enabled|Disabled',
                    'checkedValue' => 2,
                    'uncheckedValue' => 0,
                    'switch_default' => 1,
                    'theme' => '#6f1919',
                ],
                [
                    'type' => 'timestamp',
                    'name' => 'expired_at',
                    'label' => '过期时间',
                    'label_en' => 'Expires At',
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
                        'title_en' => 'Title',
                        'minWidth' => '',
                        'width' => '',
                        'sort' => true,
                        'type' => 'link',
                        'url' => '/article/{id}',
                    ],
                    [
                        'field' => 'author',
                        'title' => '作者',
                        'title_en' => 'Author',
                        'width' => 220,
                        'type' => 'auto',
                    ],
                    [
                        'field' => 'cover',
                        'title' => '封面图',
                        'title_en' => 'Cover',
                        'width' => 200,
                        'type' => 'auto',
                        'sort' => false,
                    ],
                    [
                        'field' => 'is_published',
                        'title' => '是否发布',
                        'title_en' => 'Is Published',
                        'width' => 100,
                        'type' => 'switch',
                        'text' => '发布|草稿',
                        'text_en' => 'Published|Draft',
                    ],
                    [
                        'field' => 'publish_date',
                        'title' => '发布日期',
                        'title_en' => 'Publish Date',
                        'width' => 180,
                        'type' => '',
                    ],
                    [
                        'field' => 'views',
                        'title' => '浏览量',
                        'title_en' => 'Views',
                        'width' => 100,
                    ],
                    [
                        'field' => 'created_at',
                        'title' => '创建时间',
                        'title_en' => 'Created At',
                        'width' => 180,
                    ],
                ],
            ],
            'actions' => [
                [
                    'text' => '编辑',
                    'text_en' => 'Edit',
                    'action' => 'edit',
                    'icon' => 'layui-icon-edit',
                    'class' => 'layui-btn-normal',
                    'permission' => 'update',
                ],
                [
                    'text' => '删除',
                    'text_en' => 'Delete',
                    'action' => 'delete',
                    'icon' => 'layui-icon-delete',
                    'class' => 'layui-btn-danger',
                    'permission' => 'delete',
                ],
                [
                    'text' => '预览',
                    'text_en' => 'Preview',
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
                    'placeholder_en' => 'Search Title or Author',
                    'width' => 250,
                ],
            ],
            'toolbar' => [
                [
                    'text' => '新增文章',
                    'text_en' => 'Add Article',
                    'icon' => 'layui-icon-add-1',
                    'class' => 'layui-btn-normal',
                    'action' => 'add',
                    'permission' => 'create',
                ],
                [
                    'text' => '导出数据',
                    'text_en' => 'Export Data',
                    'icon' => 'layui-icon-export',
                    'class' => 'layui-btn-warm',
                    'action' => 'export',
                    'permission' => 'export',
                ],
                [
                    'text' => '前端文章列表',
                    'text_en' => 'Frontend Article List',
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
                    'label_en' => 'Title',
                    'required' => true,
                    'verify' => 'required',
                    'placeholder' => '请输入文章标题',
                    'placeholder_en' => 'Please enter the article title',
                    'required_on_add' => true,
                    'required_on_edit' => true,
                    'disabled_on_edit' => false,
                ],
                [
                    'type' => 'select',
                    'name' => 'author',
                    'label' => '作者',
                    'label_en' => 'Author',
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
                    'label_en' => 'Content',
                    'height' => '400px',
                    'required' => true,
                    'tip' => '支持富文本编辑',
                    'tip_en' => 'Supports rich text editing',
                    'required_on_add' => true,
                    'required_on_edit' => true,
                ],
                [
                    'type' => 'image',
                    'name' => 'cover',
                    'label' => '封面图',
                    'label_en' => 'Cover',
                    'uploadUrl' => '/api/admin/upload',
                    'tip' => '建议尺寸：800x600，格式：JPG/PNG',
                    'tip_en' => 'Recommended size: 800x600, format: JPG/PNG',
                    'exts' => 'jpg|png',
                    'length' => 3,
                    'text' => '启用|无用',
                    'text_en' => 'Enabled|Disabled',
                    'theme' => '#d52222',
                    'min' => 0,
                    'max' => 50,
                    'step' => 2,
                ],
                [
                    'type' => 'date',
                    'name' => 'publish_date',
                    'label' => '发布日期',
                    'label_en' => 'Publish Date',
                    'placeholder' => '请选择发布日期',
                    'placeholder_en' => 'Please select the publish date',
                ],
                [
                    'type' => 'switch',
                    'name' => 'is_published',
                    'label' => '是否发布',
                    'label_en' => 'Is Published',
                    'text' => '发布|草稿',
                    'text_en' => 'Published|Draft',
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
                        'title_en' => 'Username',
                        'width' => 150,
                    ],
                    [
                        'field' => 'nickname',
                        'title' => '昵称',
                        'title_en' => 'Nickname',
                        'width' => 150,
                    ],
                    [
                        'field' => 'email',
                        'title' => '邮箱',
                        'title_en' => 'Email',
                        'width' => 200,
                    ],
                    [
                        'field' => 'phone',
                        'title' => '手机号',
                        'title_en' => 'Phone',
                        'width' => 150,
                    ],
                    [
                        'field' => 'permissions_text',
                        'title' => '权限',
                        'title_en' => 'Permissions',
                        'width' => 200,
                    ],
                    [
                        'field' => 'status',
                        'title' => '状态',
                        'title_en' => 'Status',
                        'width' => 100,
                        'type' => 'switch',
                    ],
                    [
                        'field' => 'last_login_time',
                        'title' => '最后登录',
                        'title_en' => 'Last Login',
                        'width' => 180,
                    ],
                    [
                        'field' => 'created_at',
                        'title' => '创建时间',
                        'title_en' => 'Created At',
                        'width' => 180,
                    ],
                    [
                        'field' => 'expired_at',
                        'title' => '过期时间',
                        'title_en' => 'Expired At',
                        'type' => 'datetime',
                        'width' => 180,
                    ],
                ],
            ],
            'actions' => [
                [
                    'text' => '编辑',
                    'text_en' => 'Edit',
                    'action' => 'edit',
                    'icon' => 'layui-icon-edit',
                    'class' => 'layui-btn-normal',
                    'permission' => 'update',
                ],
                [
                    'text' => '删除',
                    'text_en' => 'Delete',
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
                    'text_en' => 'Add Admin',
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
                    'label_en' => 'Username',
                    'required' => true,
                    'placeholder' => '4-20位字母、数字、下划线',
                    'disabled_on_edit' => true,
                ],
                [
                    'type' => 'password',
                    'name' => 'password',
                    'label' => '密码',
                    'label_en' => 'Password',
                    'required' => 'add',
                    'placeholder' => '6-20位，包含字母和数字',
                    'tip' => '编辑时留空表示不修改密码',
                ],
                [
                    'type' => 'input',
                    'name' => 'nickname',
                    'label' => '昵称',
                    'label_en' => 'Nickname',
                    'required' => true,
                ],
                [
                    'type' => 'input',
                    'name' => 'email',
                    'label' => '邮箱',
                    'label_en' => 'Email',
                    'required' => false,
                ],
                [
                    'type' => 'input',
                    'name' => 'phone',
                    'label' => '手机号',
                    'label_en' => 'Phone',
                    'required' => false,
                ],
                [
                    'type' => 'permissions',
                    'name' => 'permissions',
                    'label' => '权限配置',
                    'label_en' => 'Permissions',
                    'required' => false,
                    'hidden_on_super_admin' => true,
                ],
                [
                    'type' => 'switch',
                    'name' => 'status',
                    'label' => '状态',
                    'label_en' => 'Status',
                    'required' => false,
                    'text' => '启用|禁用',
                    'text_en' => 'True|False',
                    'hidden_on_super_admin' => true,
                ],
                [
                    'type' => 'timestamp',
                    'name' => 'expired_at',
                    'label' => '过期时间',
                    'label_en' => 'Expired At',
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
     * 字典管理配置
     */
    public static function dictType()
    {
        return [
            'page' => [
                'title' => '字典管理',
                'title_en' => 'Dictionaries',
                'icon' => 'layui-icon-template',
                'page' => 'dictType',
            ],
            'table' => [
                'url' => '/api/admin/dict/types',
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
                        'field' => 'name',
                        'title' => '字典名称',
                        'title_en' => 'Name',
                        'width' => 200,
                    ],
                    [
                        'field' => 'type',
                        'title' => '类型编码',
                        'title_en' => 'Type Code',
                        'width' => 200,
                    ],
                    [
                        'field' => 'status',
                        'title' => '状态',
                        'title_en' => 'Status',
                        'width' => 100,
                        'type' => 'select',
                        'options' => [
                            [
                                'value' => '1',
                                'label' => '启用',
                                'label_en' => 'Enabled',
                                'color' => 'blue',
                            ],
                            [
                                'value' => '0',
                                'label' => '禁用',
                                'label_en' => 'Disabled',
                                'color' => '#ccc',
                            ],
                        ],
                    ],
                    [
                        'field' => 'remark',
                        'title' => '备注',
                        'title_en' => 'Remark',
                        'width' => 500,
                    ],
                    [
                        'field' => 'created_at',
                        'title' => '创建时间',
                        'title_en' => 'Created At',
                        'width' => 180,
                    ],
                ],
                'actionsWidth' => '300',
            ],
            'actions' => [
                [
                    'text' => '配置数据',
                    'text_en' => 'Manage Data',
                    'action' => 'manageData',
                    'icon' => 'layui-icon-list',
                    'class' => 'layui-btn-normal',
                    'permission' => 'list',
                    'type' => 'iframe',
                    'url' => '/admin/dict-data-manage?type={type}',
                    'width' => '900px',
                    'height' => '640px',
                ],
                [
                    'text' => '编辑',
                    'text_en' => 'Edit',
                    'action' => 'edit',
                    'icon' => 'layui-icon-edit',
                    'class' => 'layui-btn-primary',
                    'permission' => 'update',
                ],
                [
                    'text' => '删除',
                    'text_en' => 'Delete',
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
                    'placeholder' => '搜索字典名称/编码',
                    'placeholder_en' => 'Search name/type code',
                    'width' => 250,
                ],
            ],
            'toolbar' => [
                [
                    'text' => '新增字典类型',
                    'text_en' => 'Add Dict Type',
                    'icon' => 'layui-icon-add-1',
                    'class' => 'layui-btn-normal',
                    'action' => 'add',
                    'permission' => 'create',
                ],
            ],
            'form' => [
                [
                    'type' => 'input',
                    'name' => 'name',
                    'label' => '字典名称',
                    'label_en' => 'Name',
                    'placeholder' => '如：用户状态',
                    'placeholder_en' => 'e.g. User Status',
                    'required' => true,
                ],
                [
                    'type' => 'input',
                    'name' => 'type',
                    'label' => '类型编码',
                    'label_en' => 'Type Code',
                    'placeholder' => '英文字母开头，字母/数字/下划线，如：user_status',
                    'placeholder_en' => 'Starts with a letter, letters/digits/underscore, e.g. user_status',
                    'required_on_add' => true,
                    'disabled_on_edit' => true,
                    'tip' => '创建后不可修改，代码中通过该编码调用 dict() 读取字典数据',
                    'tip_en' => 'Cannot be changed after creation. Referenced in code via dict($type).',
                ],
                [
                    'type' => 'switch',
                    'name' => 'status',
                    'label' => '状态',
                    'label_en' => 'Status',
                    'text' => '启用|禁用',
                    'text_en' => 'Enabled|Disabled',
                    'switch_default' => 1,
                ],
                [
                    'type' => 'textarea',
                    'name' => 'remark',
                    'label' => '备注',
                    'label_en' => 'Remark',
                    'required' => false,
                ],
            ],
            'api' => [
                'list' => '/api/admin/dict/types',
                'add' => '/api/admin/dict/type',
                'edit' => '/api/admin/dict/type/{id}',
                'delete' => '/api/admin/dict/type/{id}',
            ],
            'tips' => [
                'delete' => '删除字典类型会同时删除该类型下的所有字典数据，请谨慎操作',
                'delete_en' => 'Deleting a dict type also deletes all its dict data items. Proceed with caution.',
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
                'title_en' => 'Change Password',
                'icon' => 'layui-icon-password',
                'type' => 'form',
            ],
            'form' => [
                [
                    'type' => 'password',
                    'name' => 'old_password',
                    'label' => '原密码',
                    'label_en' => 'Current Password',
                    'required' => true,
                    'verify' => 'required',
                ],
                [
                    'type' => 'password',
                    'name' => 'new_password',
                    'label' => '新密码',
                    'label_en' => 'New Password',
                    'required' => true,
                    'verify' => 'required|password',
                    'placeholder' => '至少6位',
                    'placeholder_en' => 'At least 6 characters',
                ],
                [
                    'type' => 'password',
                    'name' => 'confirm_password',
                    'label' => '确认密码',
                    'label_en' => 'Confirm Password',
                    'required' => true,
                    'verify' => 'required|confirmPassword',
                ],
            ],
            'tips' => [
                'type' => 'info',
                'text' => '为了您的账号安全，建议定期更换密码，密码长度不少于6位',
                'text_en' => 'For account security, we recommend changing your password regularly. Minimum length is 6 characters.',
            ],
            'api' => [
                'submit' => '/api/admin/change-password',
            ],
        ];
    }

}
