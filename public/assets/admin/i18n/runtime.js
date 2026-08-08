(function (window, document) {
    'use strict';

    var DEFAULT_LOCALE = 'vi-VN';
    var STORAGE_KEY = 'umi_locale';
    var SUPPORTED = Object.freeze({
        'vi-VN': { label: 'Tiếng Việt', htmlLang: 'vi-VN' },
        'ru-RU': { label: 'Русский', htmlLang: 'ru-RU' },
        'en-US': { label: 'English', htmlLang: 'en-US' },
        'zh-CN': { label: '简体中文', htmlLang: 'zh-CN' }
    });
    var ALIASES = Object.freeze({
        vi: 'vi-VN', 'vi-vn': 'vi-VN',
        ru: 'ru-RU', 'ru-ru': 'ru-RU',
        en: 'en-US', 'en-us': 'en-US',
        zh: 'zh-CN', 'zh-cn': 'zh-CN', 'zh-hans': 'zh-CN'
    });
    var TRANSLATABLE_ATTRIBUTES = [
        'placeholder', 'title', 'aria-label', 'aria-description',
        'data-original-title', 'data-content'
    ];
    var IGNORE_SELECTOR = [
        '[data-v2board-i18n-ignore]',
        '[contenteditable="true"]',
        'script', 'style', 'noscript', 'template',
        'textarea', 'pre', 'code', 'kbd', 'samp',
        '.ace_editor', '.CodeMirror', '.vditor', '.vditor-reset',
        '.markdown-body', '.ant-upload-list-item-name'
    ].join(',');
    var textState = typeof WeakMap === 'function' ? new WeakMap() : null;
    var attributeState = typeof WeakMap === 'function' ? new WeakMap() : null;
    var pendingRoots = [];
    var flushScheduled = false;
    var observer = null;

    function safeGetLocale() {
        try {
            return window.localStorage.getItem(STORAGE_KEY) || '';
        } catch (error) {
            return '';
        }
    }

    function safeStoreLocale(locale) {
        try {
            window.localStorage.setItem(STORAGE_KEY, locale);
        } catch (error) {
            // Storage can be unavailable in private/locked-down browser contexts.
        }
    }

    function normalizeLocale(value) {
        var candidate = String(value || '').trim();
        if (Object.prototype.hasOwnProperty.call(SUPPORTED, candidate)) {
            return candidate;
        }
        return ALIASES[candidate.replace(/_/g, '-').toLowerCase()] || DEFAULT_LOCALE;
    }

    var locale = normalizeLocale(safeGetLocale());
    safeStoreLocale(locale);
    window.g_lang = locale;
    window.g_langSeparator = '-';
    document.documentElement.lang = SUPPORTED[locale].htmlLang;
    document.documentElement.setAttribute('data-v2board-admin-locale', locale);

    function dictionaries() {
        return window.V2BoardAdminI18nDictionaries || {};
    }

    function dictionaryFor(targetLocale) {
        return dictionaries()[targetLocale] || dictionaries()[DEFAULT_LOCALE] || {};
    }

    function translate(source, targetLocale) {
        if (typeof source !== 'string' || source.length === 0) {
            return source;
        }
        var dictionary = dictionaryFor(targetLocale || locale);
        if (Object.prototype.hasOwnProperty.call(dictionary, source)) {
            return dictionary[source];
        }

        var compact = source.replace(/\s+/g, ' ').trim();
        if (compact !== source && Object.prototype.hasOwnProperty.call(dictionary, compact)) {
            return dictionary[compact];
        }

        // Ant Design inserts visual spacing between two Chinese characters in
        // buttons (for example "取 消"). Match the unspaced catalogue key.
        var unspacedCjk = compact.replace(/([\u3400-\u9fff])\s+(?=[\u3400-\u9fff])/g, '$1');
        if (unspacedCjk !== compact && Object.prototype.hasOwnProperty.call(dictionary, unspacedCjk)) {
            return dictionary[unspacedCjk];
        }

        // Ant Design generates dynamic accessibility labels such as
        // "图标: plus"; translate the stable prefix and preserve the icon name.
        var iconLabel = compact.match(/^图标:\s*(.+)$/);
        if (iconLabel && Object.prototype.hasOwnProperty.call(dictionary, '图标')) {
            return dictionary['图标'] + ': ' + iconLabel[1];
        }
        return source;
    }

    function ignored(element) {
        if (!element || element.nodeType !== 1) {
            return false;
        }
        if (element.id === 'v2board-admin-locale-switcher') {
            return true;
        }
        if (typeof element.closest === 'function') {
            return Boolean(element.closest(IGNORE_SELECTOR));
        }
        return false;
    }

    function splitWhitespace(value) {
        var leading = (value.match(/^\s*/) || [''])[0];
        var trailing = (value.match(/\s*$/) || [''])[0];
        return {
            leading: leading,
            trailing: trailing,
            core: value.slice(leading.length, value.length - trailing.length)
        };
    }

    function translateTextNode(node) {
        if (!node || node.nodeType !== 3 || !node.parentElement || ignored(node.parentElement)) {
            return;
        }
        var current = node.nodeValue || '';
        if (!current.trim()) {
            return;
        }

        var state = textState && textState.get(node);
        var source = current;
        if (state) {
            source = current === state.rendered ? state.source : current;
        }
        var parts = splitWhitespace(source);
        var translatedCore = translate(parts.core, locale);
        var rendered = parts.leading + translatedCore + parts.trailing;

        if (textState) {
            textState.set(node, { source: source, rendered: rendered });
        }
        if (rendered !== current) {
            node.nodeValue = rendered;
        }
    }

    function getAttributeRecord(element) {
        if (!attributeState) {
            return {};
        }
        var record = attributeState.get(element);
        if (!record) {
            record = {};
            attributeState.set(element, record);
        }
        return record;
    }

    function translateAttribute(element, name) {
        if (!element.hasAttribute(name) || ignored(element)) {
            return;
        }
        var current = element.getAttribute(name);
        if (!current || !current.trim()) {
            return;
        }
        var records = getAttributeRecord(element);
        var state = records[name];
        var source = state && current === state.rendered ? state.source : current;
        var rendered = translate(source, locale);
        records[name] = { source: source, rendered: rendered };
        if (rendered !== current) {
            element.setAttribute(name, rendered);
        }
    }

    function translateElement(element) {
        if (!element || element.nodeType !== 1 || ignored(element)) {
            return;
        }
        for (var i = 0; i < TRANSLATABLE_ATTRIBUTES.length; i += 1) {
            translateAttribute(element, TRANSLATABLE_ATTRIBUTES[i]);
        }
        for (var child = element.firstChild; child; child = child.nextSibling) {
            if (child.nodeType === 3) {
                translateTextNode(child);
            }
        }
    }

    function translateTree(root) {
        if (!root) {
            return;
        }
        if (root.nodeType === 3) {
            translateTextNode(root);
            return;
        }
        if (root.nodeType !== 1 || ignored(root)) {
            return;
        }
        translateElement(root);
        var elements = root.querySelectorAll('*');
        for (var i = 0; i < elements.length; i += 1) {
            translateElement(elements[i]);
        }
    }

    function flush() {
        flushScheduled = false;
        var roots = pendingRoots.splice(0, pendingRoots.length);
        if (!roots.length && document.body) {
            roots.push(document.body);
        }
        for (var i = 0; i < roots.length; i += 1) {
            translateTree(roots[i]);
        }
        ensureSwitcher();
    }

    function schedule(root) {
        if (root) {
            pendingRoots.push(root);
        }
        if (flushScheduled) {
            return;
        }
        flushScheduled = true;
        (window.requestAnimationFrame || window.setTimeout)(flush);
    }

    function localeLabelText(targetLocale) {
        var labels = {
            'vi-VN': 'Chọn ngôn ngữ',
            'ru-RU': 'Выбор языка',
            'en-US': 'Choose language',
            'zh-CN': '选择语言'
        };
        return labels[targetLocale] || labels[DEFAULT_LOCALE];
    }

    function isSameOriginRequest(input) {
        var value = typeof input === 'string'
            ? input
            : input && typeof input.url === 'string'
                ? input.url
                : '';
        if (!value || typeof window.URL !== 'function') {
            return true;
        }
        try {
            return new window.URL(value, document.baseURI || window.location.href).origin === window.location.origin;
        } catch (error) {
            return true;
        }
    }

    function setLocale(nextLocale, reload) {
        var normalized = normalizeLocale(nextLocale);
        locale = normalized;
        safeStoreLocale(normalized);
        window.g_lang = normalized;
        document.documentElement.lang = SUPPORTED[normalized].htmlLang;
        document.documentElement.setAttribute('data-v2board-admin-locale', normalized);
        if (reload !== false) {
            window.location.reload();
            return;
        }
        var select = document.getElementById('v2board-admin-locale-select');
        if (select) {
            select.value = normalized;
            select.setAttribute('aria-label', localeLabelText(normalized));
        }
        schedule(document.body);
    }

    function ensureSwitcher() {
        if (!document.body || document.getElementById('v2board-admin-locale-switcher')) {
            return;
        }
        var wrapper = document.createElement('div');
        wrapper.id = 'v2board-admin-locale-switcher';
        wrapper.setAttribute('data-v2board-i18n-ignore', 'true');

        var icon = document.createElement('span');
        icon.className = 'v2board-admin-locale-icon';
        icon.setAttribute('aria-hidden', 'true');
        icon.textContent = '\ud83c\udf10';

        var label = document.createElement('label');
        label.className = 'v2board-admin-i18n-sr-only';
        label.htmlFor = 'v2board-admin-locale-select';
        label.textContent = localeLabelText(locale);

        var select = document.createElement('select');
        select.id = 'v2board-admin-locale-select';
        select.setAttribute('aria-label', localeLabelText(locale));
        Object.keys(SUPPORTED).forEach(function (code) {
            var option = document.createElement('option');
            option.value = code;
            option.textContent = SUPPORTED[code].label;
            select.appendChild(option);
        });
        select.value = locale;
        select.addEventListener('change', function (event) {
            setLocale(event.target.value, true);
        });

        wrapper.appendChild(icon);
        wrapper.appendChild(label);
        wrapper.appendChild(select);
        document.body.appendChild(wrapper);
    }

    function installFetchLanguageHeader() {
        if (typeof window.fetch !== 'function' || window.fetch.__v2boardI18nWrapped) {
            return;
        }
        var originalFetch = window.fetch.bind(window);
        var wrappedFetch = function (input, init) {
            if (!isSameOriginRequest(input)) {
                return originalFetch(input, init);
            }
            var options = {};
            var key;
            if (init) {
                for (key in init) {
                    if (Object.prototype.hasOwnProperty.call(init, key)) {
                        options[key] = init[key];
                    }
                }
            }
            var requestHeaders = init && init.headers;
            if (!requestHeaders && typeof window.Request === 'function' && input instanceof window.Request) {
                requestHeaders = input.headers;
            }
            if (typeof window.Headers === 'function') {
                options.headers = new window.Headers(requestHeaders || {});
                options.headers.set('Content-Language', locale);
            } else {
                options.headers = requestHeaders || {};
                options.headers['Content-Language'] = locale;
            }
            return originalFetch(input, options);
        };
        wrappedFetch.__v2boardI18nWrapped = true;
        wrappedFetch.__v2boardI18nOriginal = originalFetch;
        window.fetch = wrappedFetch;
    }

    function installXhrLanguageHeader() {
        if (typeof window.XMLHttpRequest !== 'function') {
            return;
        }
        var prototype = window.XMLHttpRequest.prototype;
        if (!prototype || typeof prototype.open !== 'function' ||
            typeof prototype.send !== 'function' || prototype.send.__v2boardI18nWrapped) {
            return;
        }
        var originalOpen = prototype.open;
        var originalSend = prototype.send;
        var wrappedOpen = function (method, url) {
            this.__v2boardI18nSameOrigin = isSameOriginRequest(url);
            return originalOpen.apply(this, arguments);
        };
        var wrappedSend = function (body) {
            if (this.__v2boardI18nSameOrigin !== false) {
                try {
                    this.setRequestHeader('Content-Language', locale);
                } catch (error) {
                    // Ignore non-HTTP or already-sent requests where headers are locked.
                }
            }
            return originalSend.call(this, body);
        };
        wrappedOpen.__v2boardI18nWrapped = true;
        wrappedOpen.__v2boardI18nOriginal = originalOpen;
        wrappedSend.__v2boardI18nWrapped = true;
        wrappedSend.__v2boardI18nOriginal = originalSend;
        prototype.open = wrappedOpen;
        prototype.send = wrappedSend;
    }

    function start() {
        ensureSwitcher();
        schedule(document.body);
        if (typeof window.MutationObserver === 'function' && document.documentElement) {
            observer = new window.MutationObserver(function (mutations) {
                for (var i = 0; i < mutations.length; i += 1) {
                    var mutation = mutations[i];
                    if (mutation.type === 'characterData') {
                        schedule(mutation.target);
                    } else if (mutation.type === 'attributes') {
                        schedule(mutation.target);
                    } else {
                        for (var j = 0; j < mutation.addedNodes.length; j += 1) {
                            schedule(mutation.addedNodes[j]);
                        }
                    }
                }
            });
            observer.observe(document.documentElement, {
                attributes: true,
                attributeFilter: TRANSLATABLE_ATTRIBUTES,
                characterData: true,
                childList: true,
                subtree: true
            });
        }
    }

    installFetchLanguageHeader();
    installXhrLanguageHeader();
    window.V2BoardAdminI18n = Object.freeze({
        defaultLocale: DEFAULT_LOCALE,
        getLocale: function () { return locale; },
        normalizeLocale: normalizeLocale,
        refresh: function () { schedule(document.body); },
        setLocale: setLocale,
        supportedLocales: SUPPORTED,
        translate: translate
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start, { once: true });
    } else {
        start();
    }
})(window, document);
