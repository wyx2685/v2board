(function () {
    function translateText(text) {
      if (!window.zhViDictionary) return text;
      const trimmed = text.trim();
      return window.zhViDictionary[trimmed] || text;
    }
  
    function walkAndTranslate(node) {
      if (node.nodeType === Node.TEXT_NODE) {
        const translated = translateText(node.nodeValue);
        if (translated !== node.nodeValue) {
          node.nodeValue = translated;
        }
      } else if (node.nodeType === Node.ELEMENT_NODE) {
        for (let child of node.childNodes) {
          walkAndTranslate(child);
        }
      }
    }
  
    function translatePage() {
      walkAndTranslate(document.body);
    }
  
    const observer = new MutationObserver(() => {
      translatePage();
    });
  
    observer.observe(document.body, {
      childList: true,
      subtree: true,
    });

    window.addEventListener('load', () => {
      function waitForDict() {
        if (window.zhViDictionary) {
          translatePage();
        } else {
          setTimeout(waitForDict, 100);
        }
      }
      waitForDict();
    });
  })();
  