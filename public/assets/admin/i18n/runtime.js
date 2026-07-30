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
    "fa-IR": { label: "فارسی", direction: "rtl" },
    "ja-JP": { label: "日本語", direction: "ltr" },
    "ko-KR": { label: "한국어", direction: "ltr" },
    "vi-VN": { label: "Tiếng Việt", direction: "ltr" },
    "zh-CN": { label: "简体中文", direction: "ltr" },
    "zh-TW": { label: "繁體中文", direction: "ltr" },
  };

  var currentLocale = resolveInitialLocale();
  var observer = null;
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
    var locale = LOCALES[currentLocale] || LOCALES[DEFAULT_LOCALE];
    document.documentElement.lang = currentLocale;
    document.documentElement.dir = locale.direction;
  }

  function persistLocale(locale) {
    try {
      root.localStorage.setItem(STORAGE_KEY, locale);
      root.localStorage.setItem(LEGACY_STORAGE_KEY, locale);
    } catch (error) {
      // The selected language still works when storage is unavailable.
    }
  }

  function setLocale(locale) {
    var normalized = normalizeLocale(locale);
    if (!normalized || normalized === currentLocale) return;

    currentLocale = normalized;
    persistLocale(normalized);
    updateDocumentLanguage();
    translateTree(document.body);

    document.dispatchEvent(
      new CustomEvent("v2board:admin-locale-changed", {
        detail: { locale: normalized },
      }),
    );
  }

  function createLanguageSwitcher() {
    if (document.getElementById("v2board-admin-language")) return;

    var host = document.createElement("div");
    host.id = "v2board-admin-language";
    host.setAttribute("data-v2board-admin-i18n", "true");
    var shadow = host.attachShadow({ mode: "open" });

    var style = document.createElement("style");
    style.textContent =
      ":host{position:fixed;top:14px;right:16px;z-index:2147483647}" +
      ".control{align-items:center;background:rgba(255,255,255,.96);" +
      "border:1px solid rgba(0,0,0,.1);border-radius:8px;box-shadow:" +
      "0 3px 14px rgba(0,0,0,.14);display:flex;height:36px;padding:0 9px;" +
      "backdrop-filter:blur(8px)}" +
      ".icon{color:#606266;font:16px/1 sans-serif;margin-right:6px}" +
      "select{appearance:auto;background:transparent;border:0;color:#303133;" +
      "cursor:pointer;font:500 13px/1.2 -apple-system,BlinkMacSystemFont," +
      "\"Segoe UI\",sans-serif;max-width:116px;outline:0}" +
      "@media(max-width:640px){:host{right:10px;top:10px}.control{height:34px}}";

    var control = document.createElement("label");
    control.className = "control";
    control.title = "Admin language";

    var icon = document.createElement("span");
    icon.className = "icon";
    icon.setAttribute("aria-hidden", "true");
    icon.textContent = "🌐";

    var select = document.createElement("select");
    select.setAttribute("aria-label", "Admin language");
    Object.keys(LOCALES).forEach(function (locale) {
      var option = document.createElement("option");
      option.value = locale;
      option.textContent = LOCALES[locale].label;
      option.selected = locale === currentLocale;
      select.appendChild(option);
    });
    select.addEventListener("change", function (event) {
      setLocale(event.target.value);
    });

    control.appendChild(icon);
    control.appendChild(select);
    shadow.appendChild(style);
    shadow.appendChild(control);
    document.body.appendChild(host);
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
