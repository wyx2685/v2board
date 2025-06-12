(function() {
  'use strict';
  const ATTRS = ['placeholder', 'value', 'title', 'alt'];

  function translateText(text) {
    if (!window.zhViDictionary) return text;
    const key = text.trim();
    return window.zhViDictionary[key] || text;
  }

  function translateNode(node) {
    if (node.nodeType === Node.TEXT_NODE) {
      const translated = translateText(node.nodeValue);
      if (translated !== node.nodeValue) {
        node.nodeValue = translated;
      }
    } else if (node.nodeType === Node.ELEMENT_NODE) {
      ATTRS.forEach(attr => {
        if (node.hasAttribute(attr)) {
          const original = node.getAttribute(attr);
          const translated = translateText(original);
          if (translated !== original) {
            node.setAttribute(attr, translated);
          }
        }
      });
      node.childNodes.forEach(translateNode);
    }
  }

  function translatePage() {
    translateNode(document.body);
  }

  function loadDictionary() {
    fetch(DICT_URL)
      .then(res => res.json())
      .then(dict => {
        window.zhViDictionary = dict;
        translatePage();
      })
      .catch(err => console.error(err));
  }

  window.addEventListener('load', loadDictionary);
  const observer = new MutationObserver(translatePage);
  observer.observe(document.body, { childList: true, subtree: true });
})();
