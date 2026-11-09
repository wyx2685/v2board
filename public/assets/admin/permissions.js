(function () {
    'use strict';
    var current = null;
    var pages = {
        '/dashboard': 'dashboard.view', '/config/system': 'config.view',
        '/config/payment': 'payment.view', '/config/theme': 'theme.view',
        '/server/manage': 'server.view', '/server/group': 'server.group.view', '/server/route': 'server.view',
        '/plan': 'plan.view', '/order': 'order.view', '/coupon': 'coupon.view', '/giftcard': 'giftcard.view',
        '/user': 'user.view', '/notice': 'notice.view', '/ticket': 'ticket.view',
        '/knowledge': 'knowledge.view', '/queue': 'system.view'
    };

    function has(permission) {
        return !!current && (current.is_super_admin || current.permissions.indexOf(permission) !== -1);
    }
    function canPage(path) {
        if (path === '/admin-access') return true;
        if (path.indexOf('/ticket/') === 0) path = '/ticket';
        return !current || !!pages[path] && has(pages[path]);
    }
    function landing(path) {
        if (path && canPage(path)) return path;
        return Object.keys(pages).find(canPage) || '/admin-access';
    }
    function setCurrent(value) {
        if (!value) return;
        current = value;
        window.dispatchEvent(new Event('admin-permissions-changed'));
        var path = window.location.hash.slice(1).split('?')[0];
        if (path && path !== '/' && path !== '/login' && !canPage(path) && window.g_history) {
            window.g_history.replace(landing());
        }
    }
    async function request(path, body) {
        var response = await fetch('/api/v1/' + window.settings.secure_path + '/permission/' + path, {
            method: body ? 'POST' : 'GET',
            headers: { authorization: localStorage.getItem('authorization') || '', 'Content-Type': 'application/json', Accept: 'application/json' },
            body: body ? JSON.stringify(body) : undefined
        });
        var result = await response.json();
        if (!response.ok) throw new Error(result.errors ? Object.values(result.errors)[0][0] : result.message || '请求失败');
        return result.data;
    }
    function element(tag, text, parent) {
        var node = document.createElement(tag);
        if (text) node.textContent = text;
        if (parent) parent.appendChild(node);
        return node;
    }

    async function open() {
        if (!current || !current.is_super_admin || document.getElementById('admin-permission-dialog')) return;
        var overlay = element('div');
        overlay.id = 'admin-permission-dialog';
        overlay.className = 'admin-permission-overlay';
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');
        overlay.setAttribute('aria-labelledby', 'admin-permission-title');
        var previousFocus = document.activeElement;
        function close() { overlay.remove(); document.removeEventListener('keydown', onKey); if (previousFocus) previousFocus.focus(); }
        function onKey(event) {
            if (event.key === 'Escape') close();
            if (event.key === 'Tab') {
                var focusable = Array.from(overlay.querySelectorAll('button,select,input')).filter(function (node) { return !node.disabled; });
                var first = focusable[0], last = focusable[focusable.length - 1];
                if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
                if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
            }
        }
        var panel = element('div', '', overlay);
        panel.className = 'admin-permission-panel';
        var header = element('div', '', panel);
        header.className = 'admin-permission-header';
        element('h3', '管理员权限', header).id = 'admin-permission-title';
        var closeButton = element('button', '关闭', header);
        closeButton.type = 'button'; closeButton.onclick = close;
        element('p', '先在用户管理中将账号设为管理员，再在这里分配权限。新管理员默认没有操作权限。', panel);
        var select = element('select', '', panel);
        select.setAttribute('aria-label', '选择管理员');
        var form = element('div', '', panel);
        var status = element('p', '正在加载…', panel);
        status.setAttribute('role', 'status');
        var save = element('button', '保存权限', panel);
        save.type = 'button'; save.className = 'admin-permission-save'; save.disabled = true;
        document.body.appendChild(overlay);
        document.addEventListener('keydown', onKey);
        closeButton.focus();
        try {
            var admins = await request('fetch');
            admins.forEach(function (admin) {
                var option = element('option', admin.email + (admin.banned ? '（已封禁）' : ''), select);
                option.value = admin.id;
            });
            function render() {
                form.replaceChildren(); status.textContent = '';
                var admin = admins.find(function (item) { return item.id === Number(select.value); });
                if (!admin) { status.textContent = '没有可管理的管理员账号'; return; }
                var fullLabel = element('label', '', form);
                var full = element('input', '', fullLabel);
                full.type = 'checkbox'; full.checked = admin.admin_permissions === null;
                fullLabel.appendChild(document.createTextNode(' 完整管理权限（可以管理其他管理员权限）'));
                if (admin.id === current.id) full.disabled = true;
                element('p', admin.id === current.id ? '不能撤销自己的完整管理权限。' : '保存时自动补齐查看权限和页面所需的套餐、节点权限组等查询权限。系统和支付配置可能包含密钥，请谨慎授权。', form);
                var grid = element('div', '', form); grid.className = 'admin-permission-grid';
                Object.keys(current.catalog).forEach(function (key) {
                    var label = element('label', '', grid);
                    var checkbox = element('input', '', label);
                    checkbox.type = 'checkbox'; checkbox.value = key;
                    checkbox.checked = Array.isArray(admin.admin_permissions) && admin.admin_permissions.indexOf(key) !== -1;
                    label.appendChild(document.createTextNode(' ' + current.catalog[key]));
                });
                function toggle() {
                    grid.querySelectorAll('input').forEach(function (checkbox) { checkbox.disabled = full.checked; });
                    grid.style.opacity = full.checked ? '0.5' : '1';
                }
                full.onchange = toggle; toggle(); save.disabled = false;
                save.onclick = async function () {
                    var selectedId = admin.id;
                    save.disabled = true; select.disabled = true; status.textContent = '正在保存…';
                    try {
                        await request('save', { id: selectedId, is_super_admin: full.checked,
                            permissions: Array.from(grid.querySelectorAll('input:checked')).map(function (item) { return item.value; }) });
                        admins = await request('fetch');
                        select.value = String(selectedId); render(); status.textContent = '权限已保存，下一次请求立即生效。';
                    } catch (error) { status.textContent = error.message; }
                    finally { save.disabled = false; select.disabled = false; }
                };
            }
            select.onchange = render; render();
        } catch (error) { status.textContent = error.message; }
    }

    window.AdminPermissions = { has: has, canPage: canPage, landing: landing, setCurrent: setCurrent, open: open,
        isSuper: function () { return !!current && current.is_super_admin; } };
}());
