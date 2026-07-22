<?php
namespace App\api\admin;

use Flight;
use App\middleware\AuthMiddleware;

/**
 * 字典数据管理控制器
 *
 * 每一条字典数据（label/value）都归属于一个字典类型（type），
 * 前台业务代码通过 app/helpers/dict.php 提供的 dict()/dictLabel() 读取。
 *
 * 权限说明：字典数据的管理入口已合并进「字典管理」（配置数据弹层），
 * 不再是独立菜单/权限模块，因此这里的权限检查统一复用 dictType
 * 权限维度（list/create/update/delete），不单独注册 dictData 权限。
 */
class DictDataController
{
    /**
     * 字典数据列表（按字典类型筛选）
     */
    public static function list()
    {
        AuthMiddleware::checkAdmin();
        checkPermission('dictType', 'list');

        $db = db();

        $page    = (int)getQuery('page', 1);
        $limit   = (int)getQuery('limit', 10);
        $keyword = getQuery('keyword', '');
        $type    = getQuery('type', '');

        $where = [];
        if ($type) {
            $where['type'] = $type;
        }
        if ($keyword) {
            $where['OR'] = [
                'label[~]' => $keyword,
                'value[~]' => $keyword,
            ];
        }

        $count = $db->count('dict_data', $where);

        $where['LIMIT'] = [($page - 1) * $limit, $limit];
        $where['ORDER'] = ['sort' => 'ASC', 'id' => 'ASC'];

        $list = $db->select('dict_data', '*', $where);

        layuiTable($list, $count);
    }

    /**
     * 不分页获取指定字典类型下全部启用的数据项
     * GET /api/admin/dict/data/options?type=xxx
     *
     * 供其他 CRUD 模块的 select/radio/checkbox 字段动态引用某个字典，
     * 例如：'url' => '/api/admin/dict/data/options?type=common_status', 'valueField' => 'value', 'labelField' => 'label'
     */
    public static function options()
    {
        AuthMiddleware::checkAdmin();
        checkPermission('dictType', 'list');

        $type = getQuery('type', '');
        if (empty($type)) { error('缺少字典类型参数 type'); return; }

        success(dict($type));
    }

    /**
     * 创建字典数据
     */
    public static function create()
    {
        AuthMiddleware::checkAdmin();
        checkPermission('dictType', 'create');

        $db = db();

        $type  = trim((string)getPost('type'));
        $label = cleanInput(getPost('label'));
        $value = trim((string)getPost('value'));

        if (empty($type))  { error('请选择所属字典类型'); return; }
        if (empty($label)) { error('字典标签不能为空'); return; }
        if ($value === '') { error('字典键值不能为空'); return; }

        if (!$db->has('dict_type', ['type' => $type])) {
            error('所属字典类型不存在');
            return;
        }

        $isDefault = (int)getPost('is_default', 0);
        if ($isDefault) {
            $db->update('dict_data', ['is_default' => 0], ['type' => $type]);
        }

        $result = $db->insert('dict_data', [
            'type'       => $type,
            'label'      => $label,
            'value'      => $value,
            'sort'       => (int)getPost('sort', 0),
            'status'     => (int)getPost('status', 1),
            'is_default' => $isDefault,
            'color'      => getPost('color', ''),
            'remark'     => cleanInput(getPost('remark', '')),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        if ($result->rowCount() > 0) {
            auditLog('创建字典数据：' . $type . ' - ' . $label);
            success(['id' => $db->id()], '创建成功');
        } else {
            error('创建失败');
        }
    }

    /**
     * 更新字典数据
     */
    public static function update($id)
    {
        AuthMiddleware::checkAdmin();
        checkPermission('dictType', 'update');

        $db = db();
        $row = $db->get('dict_data', '*', ['id' => $id]);
        if (!$row) { error('字典数据不存在', 404); return; }

        $data = [];

        $label = getPost('label');
        if ($label !== null && $label !== '') $data['label'] = cleanInput($label);

        $value = getPost('value');
        if ($value !== null && $value !== '') $data['value'] = trim($value);

        $sort = getPost('sort');
        if ($sort !== null) $data['sort'] = (int)$sort;

        $status = getPost('status');
        if ($status !== null) $data['status'] = (int)$status;

        $color = getPost('color');
        if ($color !== null) $data['color'] = $color;

        $remark = getPost('remark');
        if ($remark !== null) $data['remark'] = cleanInput($remark);

        $isDefault = getPost('is_default');
        if ($isDefault !== null) {
            $data['is_default'] = (int)$isDefault;
            if ((int)$isDefault === 1) {
                $db->update('dict_data', ['is_default' => 0], ['type' => $row['type']]);
            }
        }

        if (empty($data)) { error('没有需要更新的内容'); return; }

        $db->update('dict_data', $data, ['id' => $id]);
        auditLog('更新字典数据 ID: ' . $id);
        success([], '更新成功');
    }

    /**
     * 删除字典数据
     */
    public static function delete($id)
    {
        AuthMiddleware::checkAdmin();
        checkPermission('dictType', 'delete');

        $db = db();
        $row = $db->get('dict_data', ['id', 'type', 'label'], ['id' => $id]);
        if (!$row) { error('字典数据不存在', 404); return; }

        $db->delete('dict_data', ['id' => $id]);
        auditLog('删除字典数据：' . $row['type'] . ' - ' . $row['label']);
        success([], '删除成功');
    }

    /**
     * 「配置数据」弹层页面
     * GET /admin/dict-data-manage?type=xxx
     *
     * 由「字典管理」（dictType）列表的行操作以 iframe 方式打开，用于集中管理
     * 某一个字典类型下的所有 label/value 数据项，避免所有类型的数据混在一张表里。
     *
     * 认证走 Cookie（admin_token），页面内的增删改仍然调用标准的
     * /api/admin/dict/data/* 接口，由 checkPermission('dictType', ...) 逐项校验权限
     * （字典数据复用 dictType 权限维度，不再单独存在 dictData 权限）。
     */
    public static function managePage()
    {
        try {
            AuthMiddleware::checkAdmin();
        } catch (\Exception $e) {
            self::renderPageError(403, '无权访问，请先登录管理后台');
            return;
        }

        $adminId = Flight::get('admin_id');
        if ($adminId != 1 && !hasPermission($adminId, 'dictType', 'list')) {
            self::renderPageError(403, '您没有访问字典管理的权限，请联系超级管理员授权');
            return;
        }

        $type = getQuery('type', '');
        if (empty($type)) {
            self::renderPageError(400, '缺少字典类型参数 type');
            return;
        }

        $dictType = db()->get('dict_type', '*', ['type' => $type]);
        if (!$dictType) {
            self::renderPageError(404, "字典类型 [{$type}] 不存在");
            return;
        }

        // 供视图文件使用
        $viewPath = __DIR__ . '/../../views/admin/dict-data-manage.php';
        if (!file_exists($viewPath)) {
            self::renderPageError(500, '视图文件缺失：app/views/admin/dict-data-manage.php');
            return;
        }
        include $viewPath;
    }

    /**
     * 渲染错误页（内嵌 HTML，适合 iframe 场景），参考 AdminViewController::renderError()
     */
    private static function renderPageError(int $httpCode, string $message)
    {
        http_response_code($httpCode);
        header('Content-Type: text/html; charset=utf-8');
        echo <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>错误 {$httpCode}</title>
<link rel="stylesheet" href="/admin/assets/css/admin.css?v=3.3.0">
<style>
body { display:flex; align-items:center; justify-content:center; height:100vh; margin:0; background:#f0f2f7; }
.err-box { text-align:center; padding:40px; background:#fff; border-radius:12px; box-shadow:0 4px 20px rgba(0,0,0,.08); }
.err-code { font-size:64px; font-weight:700; color:#e74c3c; line-height:1; }
.err-msg  { margin-top:12px; color:#666; font-size:15px; }
</style>
</head>
<body>
  <div class="err-box">
    <div class="err-code">{$httpCode}</div>
    <div class="err-msg">{$message}</div>
  </div>
</body>
</html>
HTML;
        terminateRequest();
    }
}
