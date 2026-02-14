/**
 * 公共函数库
 */

/**
 * 加载管理员信息到顶部（后台管理）
 */
function loadAdminInfo() {
    request(ADMIN_API_PREFIX + '/info', {
        method: 'GET'
    }).then(res => {
        if (res.code === 0 && res.data) {
            const username = res.data.nickname || res.data.username || '管理员';
            document.getElementById('username').textContent = username;
        } else {
            console.error('loadAdmin error:', res.msg);
            clearToken();
            location.href = 'login.html';
        }
    }).catch(err => {
        console.error('loadAdmin error:', err);
        document.getElementById('username').textContent = '加载失败';
    });
}

/**
 * 加载用户信息到顶部
 */
function loadUserInfo() {
    request('/api/info', {
        method: 'GET'
    }).then(res => {
        if (res.code === 0 && res.data) {
            const username = res.data.nickname || res.data.username || '用户';
            document.getElementById('username').textContent = username;
        } else {
            console.error('loadUser error:', res.msg);
            document.getElementById('username').textContent = '未知用户';
        }
    }).catch(err => {
        console.error('loadUser error:', err);
        document.getElementById('username').textContent = '加载失败';
    });
}

/**
 * 退出登录
 */
function logout() {
    layer.confirm('确定要退出登录吗？', {
        icon: 3,
        title: '提示'
    }, function(index){
        request(ADMIN_API_PREFIX + '/logout', {
            method: 'POST'
        }).then(res => {
            clearToken();
            location.href = 'login.html';
        });
        layer.close(index);
    });
}

/**
 * 格式化日期时间
 */
function formatDateTime(datetime) {
    if (!datetime) return '-';
    return datetime.replace('T', ' ').substring(0, 19);
}

/**
 * 格式化日期
 */
function formatDate(date) {
    if (!date) return '-';
    return date.substring(0, 10);
}

/**
 * 状态标签
 */
function statusTag(status) {
    return status == 1 
        ? '<span class="layui-badge layui-bg-green">正常</span>' 
        : '<span class="layui-badge layui-bg-gray">禁用</span>';
}

/**
 * 确认对话框
 */
function confirm(msg, callback) {
    layer.confirm(msg, {
        icon: 3,
        title: '提示'
    }, function(index){
        callback();
        layer.close(index);
    });
}

/**
 * 成功提示
 */
function successMsg(msg, callback) {
    layer.msg(msg, { icon: 1 }, callback);
}

/**
 * 错误提示
 */
function errorMsg(msg) {
    layer.msg(msg, { icon: 2 });
}

/**
 * 加载中
 */
function loading() {
    return layer.load(1, { shade: 0.3 });
}

/**
 * 关闭加载
 */
function closeLoading(index) {
    layer.close(index);
}

/**
 * 复制到剪贴板
 */
function copyToClipboard(text) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    document.body.removeChild(textarea);
    successMsg('复制成功');
}
