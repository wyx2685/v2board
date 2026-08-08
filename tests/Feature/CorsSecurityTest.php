<?php

namespace Tests\Feature;

use App\Http\Middleware\CORS;
use Illuminate\Http\Request;
use Tests\TestCase;

class CorsSecurityTest extends TestCase
{
    public function testItDoesNotReflectAnUntrustedOrigin(): void
    {
        config([
            'app.url' => 'https://panel.mosvpn.example',
            'cors.allowed_origins' => ['https://app.mosvpn.example'],
        ]);
        $request = $this->requestFrom('https://attacker.example');

        $response = (new CORS())->handle($request, function () {
            return response('ok');
        });

        $this->assertFalse($response->headers->has('Access-Control-Allow-Origin'));
        $this->assertFalse($response->headers->has('Access-Control-Allow-Credentials'));
    }

    public function testItAllowsConfiguredAndSameSiteOriginsOnly(): void
    {
        config([
            'app.url' => 'https://panel.mosvpn.example',
            'cors.allowed_origins' => ['https://app.mosvpn.example'],
        ]);

        foreach (['https://app.mosvpn.example', 'https://panel.mosvpn.example'] as $origin) {
            $request = $this->requestFrom($origin);
            $response = (new CORS())->handle($request, function () {
                return response('ok');
            });

            $this->assertSame($origin, $response->headers->get('Access-Control-Allow-Origin'));
            $this->assertSame('true', $response->headers->get('Access-Control-Allow-Credentials'));
            $this->assertStringContainsString('Origin', $response->headers->get('Vary'));
        }
    }

    private function requestFrom(string $origin): Request
    {
        return Request::create('https://panel.mosvpn.example/api/v1/user/info', 'GET', [], [], [], [
            'HTTP_ORIGIN' => $origin,
        ]);
    }
}
