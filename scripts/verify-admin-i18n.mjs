import assert from "node:assert/strict";
import fs from "node:fs";
import path from "node:path";
import vm from "node:vm";
import { fileURLToPath } from "node:url";

const projectRoot = path.resolve(
  path.dirname(fileURLToPath(import.meta.url)),
  "..",
);
const localeDirectory = path.join(
  projectRoot,
  "public/assets/admin/i18n/locales",
);
const expectedLocales = [
  "en-US",
  "fa-IR",
  "ja-JP",
  "ko-KR",
  "vi-VN",
  "zh-CN",
  "zh-TW",
];

const context = vm.createContext({ window: {} });
for (const locale of expectedLocales) {
  const filePath = path.join(localeDirectory, `${locale}.js`);
  assert.ok(fs.existsSync(filePath), `Missing locale file: ${locale}.js`);
  new vm.Script(fs.readFileSync(filePath, "utf8"), {
    filename: filePath,
  }).runInContext(context);
}

const dictionaries = context.window.V2BOARD_ADMIN_I18N;
assert.ok(dictionaries, "Locale files did not register their dictionaries");

const sourceKeys = Object.keys(dictionaries["zh-CN"]).sort();
assert.ok(sourceKeys.length > 0, "The source dictionary is empty");

for (const locale of expectedLocales) {
  const keys = Object.keys(dictionaries[locale]).sort();
  assert.deepEqual(
    keys,
    sourceKeys,
    `${locale} does not have exactly the same source strings as zh-CN`,
  );
  for (const key of sourceKeys) {
    assert.equal(
      typeof dictionaries[locale][key],
      "string",
      `${locale} has a non-string translation for ${JSON.stringify(key)}`,
    );
    assert.notEqual(
      dictionaries[locale][key].length,
      0,
      `${locale} has an empty translation for ${JSON.stringify(key)}`,
    );
  }
}

const adminView = fs.readFileSync(
  path.join(projectRoot, "resources/views/admin.blade.php"),
  "utf8",
);
for (const locale of expectedLocales) {
  assert.match(
    adminView,
    new RegExp(`/assets/admin/i18n/locales/${locale}\\.js`),
    `The admin view does not load ${locale}`,
  );
}
assert.match(
  adminView,
  /\/assets\/admin\/i18n\/runtime\.js/,
  "The admin view does not load the translation runtime",
);

console.log(
  `Verified ${expectedLocales.length} admin locales with ${sourceKeys.length} strings each.`,
);
