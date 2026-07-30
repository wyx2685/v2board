(function (root, document) {
  "use strict";

  var STORAGE_KEY = "v2board_admin_locale";
  var LEGACY_STORAGE_KEY = "umi_locale";
  var DEFAULT_LOCALE = "en-US";
  var ATTRIBUTE_NAMES = ["placeholder", "title", "aria-label", "data-placeholder", "alt"];
  var SKIPPED_ELEMENTS = {
    CODE: true,
    INPUT: true,
    SCRIPT: true,
    STYLE: true,
    TEXTAREA: true,
  };
  var LOCALES = {
    "en-US": { label: "English", direction: "ltr" },
    "fa-IR": { label: "فارسی", direction: "ltr" },
    "ja-JP": { label: "日本語", direction: "ltr" },
    "ko-KR": { label: "한국어", direction: "ltr" },
    "vi-VN": { label: "Tiếng Việt", direction: "ltr" },
    "zh-CN": { label: "简体中文", direction: "ltr" },
    "zh-TW": { label: "繁體中文", direction: "ltr" },
  };

  var currentLocale = resolveInitialLocale();
  var observer = null;
  var switcherHost = null;
  var switcherShadow = null;
  var menuHost = null;
  var menuShadow = null;
  var textRecords = new WeakMap();
  var attributeRecords = new WeakMap();

  function hasLocale(locale) {
    return Object.prototype.hasOwnProperty.call(LOCALES, locale);
  }

  function normalizeLocale(locale) {
    if (!locale || typeof locale !== "string") return null;
    if (hasLocale(locale)) return locale;

    var normalized = locale.replace("_", "-");
    if (hasLocale(normalized)) return normalized;

    var language = normalized.split("-")[0].toLowerCase();
    var match = Object.keys(LOCALES).find(function (candidate) {
      return candidate.split("-")[0].toLowerCase() === language;
    });
    return match || null;
  }

  function resolveInitialLocale() {
    var stored = null;
    try {
      stored =
        root.localStorage.getItem(STORAGE_KEY) ||
        root.localStorage.getItem(LEGACY_STORAGE_KEY);
    } catch (error) {
      stored = null;
    }

    var browserLocale =
      root.navigator && Array.isArray(root.navigator.languages)
        ? root.navigator.languages[0]
        : root.navigator && root.navigator.language;

    return (
      normalizeLocale(stored) ||
      normalizeLocale(browserLocale) ||
      DEFAULT_LOCALE
    );
  }

  function getDictionary(locale) {
    var dictionaries = root.V2BOARD_ADMIN_I18N || {};
    return dictionaries[locale] || dictionaries[DEFAULT_LOCALE] || {};
  }

  function translate(source) {
    if (currentLocale === "zh-CN") return source;

    var dictionary = getDictionary(currentLocale);
    if (Object.prototype.hasOwnProperty.call(dictionary, source)) {
      return dictionary[source];
    }

    var leading = source.match(/^\s*/u);
    var trailing = source.match(/\s*$/u);
    var trimmed = source.trim();
    if (
      trimmed &&
      Object.prototype.hasOwnProperty.call(dictionary, trimmed)
    ) {
      return (
        (leading ? leading[0] : "") +
        dictionary[trimmed].trim() +
        (trailing ? trailing[0] : "")
      );
    }

    return source;
  }

  function shouldSkipText(node) {
    var parent = node.nodeType === Node.ELEMENT_NODE ? node : node.parentElement;
    while (parent) {
      if (SKIPPED_ELEMENTS[parent.tagName]) return true;
      parent = parent.parentElement;
    }
    return false;
  }

  function translateTextNode(node) {
    if (!node || node.nodeType !== Node.TEXT_NODE || shouldSkipText(node)) return;

    var currentValue = node.nodeValue || "";
    if (!/[\u3400-\u9fff\uf900-\ufaff]/u.test(currentValue)) {
      var existing = textRecords.get(node);
      if (!existing || currentValue === existing.rendered) {
        if (!existing) return;
      }
    }

    var record = textRecords.get(node);
    if (!record || currentValue !== record.rendered) {
      record = { source: currentValue, rendered: currentValue };
    }

    var translated = translate(record.source);
    record.rendered = translated;
    textRecords.set(node, record);
    if (currentValue !== translated) node.nodeValue = translated;
  }

  function translateAttribute(element, attributeName) {
    if (
      !element.hasAttribute(attributeName) ||
      element.tagName === "SCRIPT" ||
      element.tagName === "STYLE"
    ) {
      return;
    }

    var currentValue = element.getAttribute(attributeName) || "";
    var records = attributeRecords.get(element);
    if (!records) {
      records = {};
      attributeRecords.set(element, records);
    }

    var record = records[attributeName];
    if (!record || currentValue !== record.rendered) {
      record = { source: currentValue, rendered: currentValue };
    }

    var translated = translate(record.source);
    record.rendered = translated;
    records[attributeName] = record;
    if (currentValue !== translated) {
      element.setAttribute(attributeName, translated);
    }
  }

  function translateElement(element) {
    if (
      !element ||
      element.nodeType !== Node.ELEMENT_NODE ||
      element.tagName === "SCRIPT" ||
      element.tagName === "STYLE"
    ) {
      return;
    }

    ATTRIBUTE_NAMES.forEach(function (attributeName) {
      translateAttribute(element, attributeName);
    });
  }

  function translateTree(rootNode) {
    if (!rootNode) return;

    if (rootNode.nodeType === Node.TEXT_NODE) {
      translateTextNode(rootNode);
      return;
    }
    if (
      rootNode.nodeType !== Node.ELEMENT_NODE ||
      rootNode.tagName === "SCRIPT" ||
      rootNode.tagName === "STYLE"
    ) {
      return;
    }

    translateElement(rootNode);
    var walker = document.createTreeWalker(
      rootNode,
      NodeFilter.SHOW_ELEMENT | NodeFilter.SHOW_TEXT,
    );
    var node = walker.nextNode();
    while (node) {
      if (node.nodeType === Node.TEXT_NODE) {
        translateTextNode(node);
      } else {
        translateElement(node);
      }
      node = walker.nextNode();
    }
  }

  function updateDocumentLanguage() {
    document.documentElement.lang = currentLocale;
    document.documentElement.dir = "ltr";

    if (switcherHost) {
      switcherHost.setAttribute("dir", "ltr");
    }
  }

  function persistLocale(locale) {
    try {
      root.localStorage.setItem(STORAGE_KEY, locale);
      root.localStorage.setItem(LEGACY_STORAGE_KEY, locale);
    } catch (error) {
      // The selected language still works when storage is unavailable.
    }
  }

  function updateSwitcherSelection() {
    if (!switcherShadow || !menuShadow) return;

    menuShadow.querySelectorAll(".option").forEach(function (option) {
      var selected = option.getAttribute("data-locale") === currentLocale;
      option.classList.toggle("selected", selected);
      option.setAttribute("aria-selected", selected ? "true" : "false");
      option.setAttribute("tabindex", selected ? "0" : "-1");
    });

    var trigger = switcherShadow.querySelector(".trigger");
    if (trigger) {
      trigger.setAttribute(
        "aria-label",
        "Admin language: " + LOCALES[currentLocale].label,
      );
      trigger.title = "Admin language: " + LOCALES[currentLocale].label;
    }
  }

  function setLocale(locale) {
    var normalized = normalizeLocale(locale);
    if (!normalized || normalized === currentLocale) return;

    currentLocale = normalized;
    persistLocale(normalized);
    updateDocumentLanguage();
    updateSwitcherSelection();
    translateTree(document.body);

    document.dispatchEvent(
      new CustomEvent("v2board:admin-locale-changed", {
        detail: { locale: normalized },
      }),
    );
  }

  function isVisible(element) {
    if (!element || !element.getBoundingClientRect) return false;
    var rect = element.getBoundingClientRect();
    if (!rect.width || !rect.height) return false;
    var style = root.getComputedStyle(element);
    return style.display !== "none" && style.visibility !== "hidden";
  }

  function isInTopBar(element) {
    if (!isVisible(element)) return false;
    var rect = element.getBoundingClientRect();
    return rect.top >= -2 && rect.top < 80 && rect.height <= 72;
  }

  function findHeader() {
    var adminHeader = document.getElementById("page-header");
    if (adminHeader && isInTopBar(adminHeader)) return adminHeader;

    var selectors = [
      "header",
      '[role="banner"]',
      '[class*="global-header"]',
      '[class*="GlobalHeader"]',
      '[class*="layout-header"]',
      '[class*="LayoutHeader"]',
    ];
    var candidates = Array.prototype.slice.call(
      document.querySelectorAll(selectors.join(",")),
    );

    candidates = candidates.filter(function (element) {
      if (!isInTopBar(element)) return false;
      return element.getBoundingClientRect().width >= root.innerWidth * 0.5;
    });

    candidates.sort(function (left, right) {
      return (
        left.getBoundingClientRect().height -
        right.getBoundingClientRect().height
      );
    });
    return candidates[0] || null;
  }

  function findThemeAction() {
    var selectors = [
      "#page-header .fa-sun",
      "#page-header .fa-moon",
      '#page-header [data-icon="bg-colors"]',
      '#page-header [data-icon="skin"]',
      '#page-header [data-v2board-theme-toggle="true"]',
      '#page-header [title="主题"]',
      '#page-header [title="Theme"]',
      '#page-header [aria-label="主题"]',
      '#page-header [aria-label="Theme"]',
    ];
    var candidates = Array.prototype.slice.call(
      document.querySelectorAll(selectors.join(",")),
    );

    candidates = candidates.filter(function (element) {
      return (
        element !== switcherHost &&
        !element.closest("#v2board-admin-language") &&
        isInTopBar(element)
      );
    });

    for (var index = 0; index < candidates.length; index += 1) {
      var element = candidates[index];
      var action =
        element.closest('button,a,[role="button"],[tabindex]') ||
        element.parentElement ||
        element;
      if (isInTopBar(action)) return action;
    }
    return null;
  }

  function findHeaderFromAction(action) {
    var current = action;
    while (current && current !== document.body) {
      var rect = current.getBoundingClientRect();
      if (
        isInTopBar(current) &&
        rect.width >= root.innerWidth * 0.5 &&
        rect.height <= 72
      ) {
        return current;
      }
      current = current.parentElement;
    }
    return null;
  }

  function findAccountAction() {
    var adminAccount = document.getElementById("page-header-user-dropdown");
    if (adminAccount && isInTopBar(adminAccount)) return adminAccount;

    var candidates = Array.prototype.slice.call(
      document.querySelectorAll("a,button,span,div"),
    );

    candidates = candidates.filter(function (element) {
      if (element === switcherHost || element.closest("#v2board-admin-language")) {
        return false;
      }
      if (!isInTopBar(element)) return false;
      var text = (element.textContent || "").trim();
      if (text.indexOf("@") === -1 || text.length > 160) return false;
      return element.getBoundingClientRect().width < 420;
    });

    candidates.sort(function (left, right) {
      var leftRect = left.getBoundingClientRect();
      var rightRect = right.getBoundingClientRect();
      return leftRect.width * leftRect.height - rightRect.width * rightRect.height;
    });

    if (!candidates.length) return null;
    var element = candidates[0];
    return (
      element.closest('button,a,[role="button"],[tabindex]') ||
      element.parentElement ||
      element
    );
  }

  function lowestCommonAncestor(left, right) {
    if (!left || !right) return null;
    var ancestors = [];
    var current = left;
    while (current && current !== document.body) {
      ancestors.push(current);
      current = current.parentElement;
    }

    current = right;
    while (current && current !== document.body) {
      if (ancestors.indexOf(current) !== -1) return current;
      current = current.parentElement;
    }
    return null;
  }

  function findActionContainer(themeAction, accountAction, header) {
    var common = lowestCommonAncestor(themeAction, accountAction);
    if (common && common !== header) {
      var commonRect = common.getBoundingClientRect();
      if (commonRect.width <= 640 && commonRect.height <= 72) return common;
    }

    var action = themeAction || accountAction;
    var current = action && action.parentElement;
    while (current && current !== header && current !== document.body) {
      var rect = current.getBoundingClientRect();
      if (rect.width > 640 || rect.height > 72) break;
      if (current.children.length > 1) return current;
      current = current.parentElement;
    }
    return header;
  }

  function directChild(container, element) {
    if (!container || !element) return null;
    var current = element;
    while (current && current.parentElement !== container) {
      current = current.parentElement;
    }
    return current && current.parentElement === container ? current : null;
  }

  function syncHeaderAppearance(reference) {
    if (!switcherHost || !reference) return;
    var style = root.getComputedStyle(reference);
    switcherHost.style.setProperty(
      "--v2board-language-color",
      style.color || "#fff",
    );
    switcherHost.style.setProperty(
      "--v2board-language-height",
      Math.max(30, Math.min(40, reference.getBoundingClientRect().height)) + "px",
    );
  }

  function placeLanguageSwitcher() {
    if (!switcherHost) return;
    if (!document.body.contains(switcherHost)) {
      document.body.appendChild(switcherHost);
    }

    var themeAction = findThemeAction();
    var accountAction = findAccountAction();
    var header =
      findHeader() ||
      findHeaderFromAction(themeAction) ||
      findHeaderFromAction(accountAction);
    var container = findActionContainer(themeAction, accountAction, header);

    if (!container || !header || !header.contains(container)) {
      switcherHost.setAttribute("data-floating", "true");
      if (switcherHost.parentElement !== document.body) {
        document.body.appendChild(switcherHost);
      }
      return;
    }

    var themeChild = directChild(container, themeAction);
    var accountChild = directChild(container, accountAction);
    var needsMove = switcherHost.parentElement !== container;
    if (themeChild) {
      needsMove = needsMove || switcherHost.previousSibling !== themeChild;
    } else if (accountChild) {
      needsMove = needsMove || switcherHost.nextSibling !== accountChild;
    }

    if (needsMove) {
      container.insertBefore(
        switcherHost,
        themeChild ? themeChild.nextSibling : accountChild || null,
      );
    }
    switcherHost.removeAttribute("data-floating");
    syncHeaderAppearance(themeAction || accountAction);
  }

  function closeLanguageMenu() {
    if (!switcherShadow || !menuShadow) return;
    var menu = menuShadow.querySelector(".menu");
    var trigger = switcherShadow.querySelector(".trigger");
    if (menu) menu.hidden = true;
    if (trigger) trigger.setAttribute("aria-expanded", "false");
  }

  function positionLanguageMenu() {
    if (!switcherShadow || !menuHost) return;
    var trigger = switcherShadow.querySelector(".trigger");
    if (!trigger) return;

    var rect = trigger.getBoundingClientRect();
    var menuWidth = 168;
    var viewportWidth = root.innerWidth || document.documentElement.clientWidth;
    var left = Math.max(
      8,
      Math.min(viewportWidth - menuWidth - 8, rect.right - menuWidth),
    );
    menuHost.style.left = left + "px";
    menuHost.style.top = rect.bottom + 6 + "px";
  }

  function toggleLanguageMenu() {
    if (!switcherShadow || !menuShadow) return;
    var menu = menuShadow.querySelector(".menu");
    var trigger = switcherShadow.querySelector(".trigger");
    if (!menu || !trigger) return;

    var shouldOpen = menu.hidden;
    menu.hidden = !shouldOpen;
    trigger.setAttribute("aria-expanded", shouldOpen ? "true" : "false");
    if (shouldOpen) {
      positionLanguageMenu();
      var selected = menu.querySelector(".option.selected");
      if (selected) selected.focus();
    }
  }

  function createLanguageIcon() {
    var icon = document.createElementNS("http://www.w3.org/2000/svg", "svg");
    icon.setAttribute("class", "icon");
    icon.setAttribute("viewBox", "0 0 24 24");
    icon.setAttribute("aria-hidden", "true");
    icon.innerHTML =
      '<path d="M12.87 15.07 10.33 12.56l.03-.03A17.52 17.52 0 0 0 14.07 6H17V4h-7V2H8v2H1v2h11.17a15.66 15.66 0 0 1-3.23 5.44A15.62 15.62 0 0 1 6.59 8H4.58a17.6 17.6 0 0 0 3.03 4.78L2.55 17.77 4 19.22l4.94-4.94 3.07 3.07.86-2.28ZM18.5 10h-2L12 22h2l1.12-3h4.75L21 22h2l-4.5-12Zm-2.63 7 1.63-4.33L19.13 17h-3.26Z"/>';
    return icon;
  }

  function createLanguageSwitcher() {
    if (document.getElementById("v2board-admin-language")) return;

    switcherHost = document.createElement("div");
    switcherHost.id = "v2board-admin-language";
    switcherHost.className = "dropdown d-inline-block";
    switcherHost.setAttribute("data-v2board-admin-i18n", "true");
    switcherHost.setAttribute("dir", "ltr");
    switcherShadow = switcherHost.attachShadow({ mode: "open" });

    menuHost = document.createElement("div");
    menuHost.id = "v2board-admin-language-menu";
    menuHost.setAttribute("dir", "ltr");
    menuShadow = menuHost.attachShadow({ mode: "open" });

    var style = document.createElement("style");
    style.textContent =
      ":host{--v2board-language-color:#fff;--v2board-language-height:36px;" +
      "align-items:center;align-self:stretch;display:inline-flex;position:relative;" +
      "z-index:2147483647}" +
      ":host([data-floating]){align-self:auto;position:fixed;right:16px;top:14px;" +
      "z-index:2147483647}" +
      ".trigger{align-items:center;background:rgba(0,0,0,.1);border:0;" +
      "border-radius:3px;color:var(--v2board-language-color);cursor:pointer;" +
      "display:inline-flex;height:var(--v2board-language-height);justify-content:center;" +
      "margin:0 4px;min-height:30px;padding:0;transition:background .2s ease;" +
      "width:var(--v2board-language-height)}" +
      ".trigger:hover,.trigger:focus-visible,.trigger[aria-expanded=true]{" +
      "background:rgba(0,0,0,.18);outline:0}" +
      ".icon{fill:currentColor;height:17px;width:17px}" +
      ".menu{background:#fff;border:1px solid rgba(0,0,0,.08);border-radius:6px;" +
      "box-shadow:0 6px 18px rgba(0,0,0,.16);box-sizing:border-box;color:#303133;" +
      "list-style:none;margin:0;min-width:168px;padding:5px;position:absolute;" +
      "right:4px;top:calc(100% + 6px)}" +
      ".option{align-items:center;background:transparent;border:0;border-radius:4px;" +
      "color:inherit;cursor:pointer;display:flex;font:400 13px/1.2 -apple-system," +
      "BlinkMacSystemFont,\"Segoe UI\",sans-serif;gap:8px;height:34px;" +
      "justify-content:flex-start;padding:0 10px;text-align:left;width:100%}" +
      ".option:hover,.option:focus-visible{background:#f0f5ff;color:#1890ff;outline:0}" +
      ".option.selected{color:#1890ff;font-weight:500}" +
      ".check{font-size:14px;opacity:0;width:14px}" +
      ".option.selected .check{opacity:1}" +
      ":host([data-floating]) .trigger{--v2board-language-color:#fff;" +
      "background:#1677d2;box-shadow:0 3px 12px rgba(0,0,0,.16);margin:0}" +
      ":host([data-floating]) .trigger:hover,:host([data-floating]) " +
      ".trigger:focus-visible{background:#0f65b8}" +
      "@media(max-width:640px){:host([data-floating]){right:10px;top:10px}}";

    var trigger = document.createElement("button");
    trigger.className = "trigger";
    trigger.type = "button";
    trigger.setAttribute("aria-haspopup", "listbox");
    trigger.setAttribute("aria-expanded", "false");
    trigger.appendChild(createLanguageIcon());
    trigger.addEventListener("click", toggleLanguageMenu);

    var menuStyle = document.createElement("style");
    menuStyle.textContent =
      ":host{display:block;left:8px;position:fixed;top:54px;" +
      "z-index:2147483647}" +
      ".menu[hidden]{display:none!important}" +
      ".menu{background:#fff;border:1px solid rgba(0,0,0,.08);border-radius:6px;" +
      "box-shadow:0 6px 18px rgba(0,0,0,.16);box-sizing:border-box;color:#303133;" +
      "list-style:none;margin:0;min-width:168px;padding:5px}" +
      ".option{align-items:center;background:transparent;border:0;border-radius:4px;" +
      "color:inherit;cursor:pointer;display:flex;font:400 13px/1.2 -apple-system," +
      "BlinkMacSystemFont,\"Segoe UI\",sans-serif;gap:8px;height:34px;" +
      "justify-content:flex-start;padding:0 10px;text-align:left;width:100%}" +
      ".option:hover,.option:focus-visible{background:#f0f5ff;color:#1890ff;outline:0}" +
      ".option.selected{color:#1890ff;font-weight:500}" +
      ".check{font-size:14px;opacity:0;width:14px}" +
      ".option.selected .check{opacity:1}";

    var menu = document.createElement("div");
    menu.className = "menu";
    menu.setAttribute("role", "listbox");
    menu.hidden = true;

    Object.keys(LOCALES).forEach(function (locale) {
      var option = document.createElement("button");
      option.className = "option";
      option.type = "button";
      option.setAttribute("data-locale", locale);
      option.setAttribute("role", "option");

      var check = document.createElement("span");
      check.className = "check";
      check.setAttribute("aria-hidden", "true");
      check.textContent = "✓";

      var label = document.createElement("span");
      label.textContent = LOCALES[locale].label;

      option.appendChild(check);
      option.appendChild(label);
      option.addEventListener("click", function () {
        setLocale(locale);
        closeLanguageMenu();
        trigger.focus();
      });
      menu.appendChild(option);
    });

    switcherShadow.appendChild(style);
    switcherShadow.appendChild(trigger);
    menuShadow.appendChild(menuStyle);
    menuShadow.appendChild(menu);
    document.body.appendChild(switcherHost);
    document.body.appendChild(menuHost);

    document.addEventListener("click", function (event) {
      if (
        switcherHost &&
        event.composedPath &&
        event.composedPath().indexOf(switcherHost) === -1 &&
        event.composedPath().indexOf(menuHost) === -1
      ) {
        closeLanguageMenu();
      }
    });
    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape") closeLanguageMenu();
    });

    updateSwitcherSelection();
    placeLanguageSwitcher();
  }

  function observeChanges() {
    if (observer) observer.disconnect();
    observer = new MutationObserver(function (mutations) {
      mutations.forEach(function (mutation) {
        if (mutation.type === "characterData") {
          translateTextNode(mutation.target);
          return;
        }
        if (mutation.type === "attributes") {
          translateAttribute(mutation.target, mutation.attributeName);
          return;
        }
        mutation.addedNodes.forEach(translateTree);
      });
      if (
        !switcherHost ||
        !document.body.contains(switcherHost) ||
        switcherHost.hasAttribute("data-floating")
      ) {
        placeLanguageSwitcher();
      }
    });
    observer.observe(document.body, {
      attributeFilter: ATTRIBUTE_NAMES,
      attributes: true,
      characterData: true,
      childList: true,
      subtree: true,
    });
  }

  function start() {
    updateDocumentLanguage();
    createLanguageSwitcher();
    translateTree(document.body);
    observeChanges();
  }

  root.V2BOARD_ADMIN_I18N_API = {
    getLocale: function () {
      return currentLocale;
    },
    getSupportedLocales: function () {
      return Object.keys(LOCALES);
    },
    setLocale: setLocale,
  };

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", start, { once: true });
  } else {
    start();
  }
})(window, document);
