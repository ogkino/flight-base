<?php
/**
 * 语言包：简体中文（zh-CN）
 *
 * 约定：
 * - 按业务分组（common/crud/auth/menu/...），key 使用 snake_case
 * - 新增语言包时，必须保持与本文件完全相同的 key 结构（每个 section、每个 key 都要有）
 * - 本文件是"基准语言包"：lang() 找不到翻译时会回退到这里
 */
return [

    // 通用文案（跨模块复用）
    'common' => [
        'confirm_title'         => '提示',
        'op_success'            => '操作成功',
        'op_failed'             => '操作失败',
        'load_failed'           => '加载失败',
        'network_error'         => '网络错误',
        'unauthorized'          => '未授权，请先登录',
        'need_admin'            => '需要管理员权限',
        'no_permission'         => '无权限访问',
        'please_login'          => '请先登录',
        'admin_disabled'        => '管理员不存在或已被禁用',
        'admin_expired'         => '管理员账号已过期',
        'user_disabled'         => '用户不存在或已被禁用',
        'user_expired'          => '用户账号已过期',
        'token_invalid'         => 'Token无效或已过期',
        'copy_success'          => '复制成功',
        'logout_confirm'        => '确定要退出登录吗？',
        'logout'                => '退出',
        'admin_default_name'    => '管理员',
        'status_normal'         => '正常',
        'status_disabled'       => '禁用',
        'config_not_found'      => '配置不存在',
    ],

    // CRUD 通用渲染器文案（crud-renderer.js 使用，适用于所有配置驱动的模块）
    'crud' => [
        'search'                => '搜索',
        'reset'                 => '重置',
        'add'                   => '新增',
        'edit'                  => '编辑',
        'delete'                => '删除',
        'submit'                => '提交',
        'actions'               => '操作',
        'preview'               => '预览',
        'please_select'         => '请选择',
        'please_select_date'    => '请选择日期',
        'please_select_icon'    => '请选择图标',
        'search_icon'           => '搜索图标...',
        'input_then_enter'      => '输入后回车',
        'loading'               => '加载中...',
        'select_icon_title'     => '选择图标',
        'view_file'             => '查看文件',
        'upload_success'        => '上传成功',
        'upload_failed'         => '上传失败',
        'image_upload_failed'   => '图片上传失败',
        'editor_placeholder'    => '请输入内容...',
        'confirm_delete'        => '确定删除吗？',
        'delete_success'        => '删除成功',
        'status_update_success' => '状态更新成功',
        'update_failed'         => '更新失败',
        'password_too_short'    => '密码长度不能少于6位',
        'password_mismatch'     => '两次输入的密码不一致',
    ],

    // 说明：菜单名称 / 仪表盘 / 修改密码页面的文案不在这里维护，
    // 已改为直接在 app/config/CrudConfig.php 里用 "字段级多语言后缀"
    // （如 title_en）手写，避免同一类文案存在两套机制。详见 docs/I18N.md。

    // 登录 / 认证相关接口消息
    'auth' => [
        'captcha_required'            => '请输入验证码',
        'captcha_expired'              => '验证码已失效，请刷新',
        'captcha_error'                => '验证码错误',
        'username_password_required' => '用户名和密码不能为空',
        'admin_not_found'             => '管理员不存在',
        'password_error'              => '密码错误',
        'account_disabled'            => '账号已被禁用',
        'account_expired'              => '账号已过期',
        'login_success'                => '登录成功',
        'logout_success'               => '退出成功',
        'fill_complete_info'           => '请填写完整信息',
        'new_password_mismatch'        => '两次输入的新密码不一致',
        'new_password_too_short'       => '新密码长度不能少于6位',
        'old_password_error'           => '原密码错误',
        'change_password_success'      => '密码修改成功，请重新登录',
        'change_password_failed'       => '密码修改失败',
    ],

    // 权限操作名称（管理员权限配置界面使用）
    'permission' => [
        'list'   => '查看列表',
        'create' => '新增',
        'update' => '编辑',
        'delete' => '删除',
        'export' => '导出',
        'custom' => '自定义',
        'access' => '访问权限',
    ],

];
