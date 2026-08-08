<?php

namespace Tests\Feature;

use App\Http\Middleware\Language;
use App\Http\Middleware\UAfilter;
use App\Utils\ClientProfileLocalizer;
use Illuminate\Contracts\Http\Kernel as HttpKernelContract;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    public function testVietnameseIsTheDefaultAndOnlyConfiguredLocalesAreSupported(): void
    {
        $this->assertSame('vi-VN', config('app.locale'));
        $this->assertSame('vi-VN', config('app.default_locale'));
        $this->assertSame('en-US', config('app.fallback_locale'));
        $this->assertSame('vi_VN', config('app.faker_locale'));
        $this->assertSame(
            ['vi-VN', 'ru-RU', 'en-US', 'zh-CN'],
            array_keys(config('app.supported_locales'))
        );
    }

    /**
     * @dataProvider localeAliasProvider
     */
    public function testContentLanguageAliasesAreNormalized(string $header, string $expected): void
    {
        $response = $this->runMiddleware(['Content-Language' => $header]);

        $this->assertSame($expected, app()->getLocale());
        $this->assertSame($expected, $response->headers->get('Content-Language'));
    }

    public function localeAliasProvider(): array
    {
        return [
            'Vietnamese base' => ['vi', 'vi-VN'],
            'Vietnamese underscore' => ['vi_VN', 'vi-VN'],
            'Russian base' => ['ru', 'ru-RU'],
            'English region variant' => ['en-GB', 'en-US'],
            'Chinese underscore' => ['zh_CN', 'zh-CN'],
            'Simplified Chinese alias' => ['zh-Hans', 'zh-CN'],
        ];
    }

    public function testInvalidLocaleFallsBackToVietnamese(): void
    {
        $response = $this->runMiddleware([
            'Content-Language' => '../../invalid',
            'Accept-Language' => 'xx-XX;q=1, *;q=0.5',
        ]);

        $this->assertSame('vi-VN', app()->getLocale());
        $this->assertSame('vi-VN', $response->headers->get('Content-Language'));
    }

    public function testInvalidContentLanguageFallsThroughToAcceptLanguage(): void
    {
        $response = $this->runMiddleware([
            'Content-Language' => 'xx-XX',
            'Accept-Language' => 'en-US;q=0.6, ru-RU;q=0.9',
        ]);

        $this->assertSame('ru-RU', app()->getLocale());
        $this->assertSame('ru-RU', $response->headers->get('Content-Language'));
    }

    public function testLocaleDoesNotLeakBetweenSequentialRequests(): void
    {
        $first = $this->runMiddleware(['Content-Language' => 'ru-RU']);
        $second = $this->runMiddleware();

        $this->assertSame('ru-RU', $first->headers->get('Content-Language'));
        $this->assertSame('vi-VN', $second->headers->get('Content-Language'));
        $this->assertSame('vi-VN', app()->getLocale());
    }

    public function testAValidatedDownstreamLocaleOverrideIsReflectedInTheResponse(): void
    {
        $request = Request::create('/localization-test', 'GET');
        $request->headers->set('Accept-Language', '');
        $request->headers->set('Content-Language', '');

        $response = (new Language())->handle($request, static function (): Response {
            app()->setLocale('ru-RU');

            return new Response('ok');
        });

        $this->assertSame('ru-RU', $response->headers->get('Content-Language'));
    }

    public function testLanguageMiddlewareIsGlobal(): void
    {
        $kernel = app(HttpKernelContract::class);

        $this->assertTrue($kernel->hasMiddleware(Language::class));
    }

    public function testUnsupportedBrowserPageUsesTheNegotiatedLocale(): void
    {
        $request = Request::create('/localization-test', 'GET');
        $request->headers->set('Content-Language', 'ru');
        $request->headers->set('User-Agent', 'MicroMessenger');

        $response = (new Language())->handle($request, static function ($request): Response {
            return (new UAfilter())->handle($request, static function (): Response {
                return new Response('ok');
            });
        });

        $this->assertSame('ru-RU', $response->headers->get('Content-Language'));
        $this->assertStringContainsString('Браузер не поддерживается', $response->getContent());
        $this->assertStringContainsString('<html lang="ru-RU">', $response->getContent());
    }

    /**
     * @dataProvider clientProfileLocaleProvider
     */
    public function testBuiltInClientProfileLabelsAreLocalized(
        string $locale,
        array $expected
    ): void {
        app()->setLocale($locale);

        $localized = ClientProfileLocalizer::localize([
            'groups' => ['自动选择', '故障转移', '节点选择'],
            'modes' => ['关闭代理', '全局代理', '海外代理'],
        ]);

        $this->assertSame($expected, array_merge($localized['groups'], $localized['modes']));
    }

    public function clientProfileLocaleProvider(): array
    {
        return [
            'Vietnamese' => ['vi-VN', ['Tự động chọn', 'Chuyển đổi dự phòng', 'Chọn máy chủ', 'Tắt proxy', 'Proxy toàn cục', 'Proxy quốc tế']],
            'Russian' => ['ru-RU', ['Автоматический выбор', 'Резервное переключение', 'Выбор сервера', 'Отключить прокси', 'Глобальный прокси', 'Зарубежный прокси']],
            'English' => ['en-US', ['Automatic Selection', 'Failover', 'Node Selection', 'Disable Proxy', 'Global Proxy', 'Overseas Proxy']],
            'Simplified Chinese' => ['zh-CN', ['自动选择', '故障转移', '节点选择', '关闭代理', '全局代理', '海外代理']],
        ];
    }

    private function runMiddleware(array $headers = []): Response
    {
        $request = Request::create('/localization-test', 'GET');
        $request->headers->set('Accept-Language', '');
        $request->headers->set('Content-Language', '');
        foreach ($headers as $name => $value) {
            $request->headers->set($name, $value);
        }

        return (new Language())->handle($request, static function (): Response {
            return new Response('ok');
        });
    }
}
