<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class TranslationDictionaryTest extends TestCase
{
    private const LOCALES = ['vi-VN', 'ru-RU', 'en-US', 'zh-CN'];

    public function testJsonDictionariesHaveKeyAndPlaceholderParity(): void
    {
        $dictionaries = [];
        foreach (self::LOCALES as $locale) {
            $path = dirname(__DIR__, 2) . "/resources/lang/{$locale}.json";
            $dictionary = json_decode((string) file_get_contents($path), true);

            $this->assertIsArray($dictionary, "Invalid JSON dictionary: {$path}");
            $this->assertGreaterThanOrEqual(120, count($dictionary));
            $dictionaries[$locale] = $dictionary;
        }

        $referenceKeys = array_keys($dictionaries['en-US']);
        sort($referenceKeys);

        foreach ($dictionaries as $locale => $dictionary) {
            $keys = array_keys($dictionary);
            sort($keys);
            $this->assertSame($referenceKeys, $keys, "Translation keys differ for {$locale}");

            foreach ($dictionary as $key => $translation) {
                $this->assertSame(
                    $this->placeholders($key),
                    $this->placeholders($translation),
                    "Placeholder mismatch for {$locale}: {$key}"
                );
            }
        }
    }

    public function testEveryLiteralTranslationCallHasAJsonEntry(): void
    {
        $root = dirname(__DIR__, 2);
        $dictionary = json_decode((string) file_get_contents($root . '/resources/lang/en-US.json'), true);
        $missing = [];

        foreach (['app', 'config', 'resources', 'routes'] as $directory) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($root . '/' . $directory)
            );

            foreach ($iterator as $file) {
                if (!$file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }

                $contents = (string) file_get_contents($file->getPathname());
                preg_match_all('/(?:__|trans)\(\s*([\'\"])((?:\\\\.|(?!\1).)*)\1/sU', $contents, $matches);
                foreach ($matches[2] as $key) {
                    if (!$this->translationExists($root, $key, $dictionary)) {
                        $missing[$key] = $file->getPathname();
                    }
                }
            }
        }

        $this->assertSame([], $missing, 'Literal translation calls are missing dictionary entries');
    }

    public function testDynamicCommandDescriptionKeysExist(): void
    {
        $root = dirname(__DIR__, 2);
        $dictionary = json_decode((string) file_get_contents($root . '/resources/lang/en-US.json'), true);
        $missing = [];
        $patterns = [
            $root . '/app/Console/Commands/*.php',
            $root . '/app/Plugins/Telegram/Commands/*.php',
        ];

        foreach ($patterns as $pattern) {
            foreach (glob($pattern) as $path) {
                $contents = (string) file_get_contents($path);
                preg_match_all(
                    '/\$(?:descriptionKey|description)\s*=\s*([\'\"])([^\'\"]+\.\w+)\1/',
                    $contents,
                    $matches
                );
                foreach ($matches[2] as $key) {
                    if (!$this->translationExists($root, $key, $dictionary)) {
                        $missing[$key] = $path;
                    }
                }
            }
        }

        $this->assertSame([], $missing, 'Dynamic command descriptions are missing translations');
    }

    public function testCommandSourcesDoNotContainHardcodedChineseText(): void
    {
        $root = dirname(__DIR__, 2);
        $paths = array_merge(
            glob($root . '/app/Console/Commands/*.php'),
            glob($root . '/app/Plugins/Telegram{,/**}/*.php', GLOB_BRACE)
        );

        foreach ($paths as $path) {
            if (!is_file($path)) {
                continue;
            }
            $this->assertDoesNotMatchRegularExpression(
                '/[\x{3400}-\x{9fff}]/u',
                (string) file_get_contents($path),
                "Hardcoded Chinese text remains in {$path}"
            );
        }
    }

    private function translationExists(string $root, string $key, array $jsonDictionary): bool
    {
        if (array_key_exists($key, $jsonDictionary)) {
            return true;
        }

        $segments = explode('.', $key, 2);
        if (count($segments) !== 2 || !preg_match('/^[A-Za-z0-9_-]+$/', $segments[0])) {
            return false;
        }

        $path = "{$root}/resources/lang/en-US/{$segments[0]}.php";
        if (!is_file($path)) {
            return false;
        }

        return array_key_exists($segments[1], $this->flatten(require $path));
    }

    public function testValidationDictionariesHaveKeyAndPlaceholderParity(): void
    {
        $translations = [];
        foreach (self::LOCALES as $locale) {
            $path = dirname(__DIR__, 2) . "/resources/lang/{$locale}/validation.php";
            $translations[$locale] = $this->flatten(require $path);
        }

        $referenceKeys = array_keys($translations['en-US']);
        sort($referenceKeys);

        foreach ($translations as $locale => $messages) {
            $keys = array_keys($messages);
            sort($keys);
            $this->assertSame($referenceKeys, $keys, "Validation keys differ for {$locale}");

            foreach ($messages as $key => $message) {
                $this->assertSame(
                    $this->placeholders($translations['en-US'][$key]),
                    $this->placeholders($message),
                    "Validation placeholder mismatch for {$locale}: {$key}"
                );
            }
        }
    }

    public function testPhpTranslationGroupsHaveKeyAndPlaceholderParity(): void
    {
        $root = dirname(__DIR__, 2);
        $groups = ['client', 'console', 'mail', 'payment', 'telegram'];

        foreach ($groups as $group) {
            $translations = [];
            foreach (self::LOCALES as $locale) {
                $path = "{$root}/resources/lang/{$locale}/{$group}.php";
                $this->assertFileExists($path);
                $translations[$locale] = $this->flatten(require $path);
            }

            $referenceKeys = array_keys($translations['en-US']);
            sort($referenceKeys);

            foreach ($translations as $locale => $messages) {
                $keys = array_keys($messages);
                sort($keys);
                $this->assertSame(
                    $referenceKeys,
                    $keys,
                    "Translation keys differ for {$locale}/{$group}.php"
                );

                foreach ($messages as $key => $message) {
                    $this->assertIsString($message, "Translation must be a string: {$locale}/{$group}.{$key}");
                    $this->assertSame(
                        $this->placeholders($translations['en-US'][$key]),
                        $this->placeholders($message),
                        "Placeholder mismatch for {$locale}/{$group}.{$key}"
                    );
                }
            }
        }
    }

    private function placeholders(string $message): array
    {
        preg_match_all('/:[A-Za-z_][A-Za-z0-9_]*/', $message, $matches);
        $placeholders = array_values(array_unique($matches[0]));
        sort($placeholders);

        return $placeholders;
    }

    private function flatten(array $messages, string $prefix = ''): array
    {
        $flattened = [];
        foreach ($messages as $key => $message) {
            $path = $prefix === '' ? $key : $prefix . '.' . $key;
            if (is_array($message)) {
                $flattened += $this->flatten($message, $path);
            } else {
                $flattened[$path] = $message;
            }
        }

        return $flattened;
    }
}
