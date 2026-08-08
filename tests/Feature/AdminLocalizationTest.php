<?php

namespace Tests\Feature;

use App\Exceptions\Handler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AdminLocalizationTest extends TestCase
{
    /**
     * @dataProvider adminMessageProvider
     */
    public function testAdminMessagesAreTranslated(
        string $locale,
        string $serverMessage,
        string $validationMessage,
        string $telegramMessage
    ): void {
        app()->setLocale($locale);

        $this->assertSame($serverMessage, __('Server does not exist'));
        $this->assertSame($validationMessage, __('The given data was invalid.'));
        $this->assertSame($telegramMessage, __('Telegram error: :error', ['error' => 'E42']));
    }

    public function adminMessageProvider(): array
    {
        return [
            'Vietnamese' => ['vi-VN', 'Máy chủ không tồn tại', 'Dữ liệu gửi lên không hợp lệ.', 'Lỗi từ Telegram: E42'],
            'Russian' => ['ru-RU', 'Сервер не существует', 'Отправленные данные некорректны.', 'Ошибка Telegram: E42'],
            'English' => ['en-US', 'The server does not exist', 'The submitted data is invalid.', 'Telegram error: E42'],
            'Simplified Chinese' => ['zh-CN', '服务器不存在', '提交的数据无效。', '来自Telegram的错误：E42'],
        ];
    }

    public function testValidationExceptionUsesLocalizedJsonSummary(): void
    {
        app()->setLocale('vi-VN');
        $request = Request::create('/api/v1/admin/test', 'POST', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);
        $exception = new ValidationException(Validator::make([], ['name' => 'required']));
        $handler = new class(app()) extends Handler {
            public function renderInvalidJson(Request $request, ValidationException $exception)
            {
                return $this->invalidJson($request, $exception);
            }
        };

        $response = $handler->renderInvalidJson($request, $exception);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('Dữ liệu gửi lên không hợp lệ.', $response->getData(true)['message']);
    }
}
