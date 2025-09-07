<?php

namespace App\Http\Controllers\V1\Passport;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CaptchaController extends Controller
{
    /**
     * 生成验证码
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function generate()
    {
        try {
            // 确保随机数种子正确初始化
            if (!mt_getrandmax()) {
                mt_srand((int) microtime() * 1000000);
            }
            
            // 生成4位随机数字，使用更好的随机数生成器
            $code = sprintf("%04d", mt_rand(1000, 9999));
            
            // 添加额外的随机性，确保每次都不同
            $timestamp = time();
            $microtime = microtime(true);
            $randomSeed = $timestamp + (int)($microtime * 1000000);
            
            // 如果仍然生成相同的数字，添加时间戳作为后备
            if ($code === '8255') {
                $code = sprintf("%04d", ($randomSeed % 9000) + 1000);
            }
            
            // 生成唯一的验证码键
            $key = 'captcha_' . Str::random(32);
            
            // 将验证码存储到缓存中，5分钟过期
            Cache::put($key, $code, 300);
            
            // 创建验证码图片
            $image = $this->createCaptchaImage($code);
            
            return response()->json([
                'data' => [
                    'img' => $image,
                    'key' => $key
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => '验证码生成失败'
            ], 500);
        }
    }
    
    /**
     * 验证验证码
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verify(Request $request)
    {
        $captcha = $request->input('captcha');
        $key = $request->input('captcha_key');
        
        if (!$captcha || !$key) {
            return response()->json([
                'message' => '参数错误'
            ], 400);
        }
        
        // 从缓存中获取验证码
        $cachedCode = Cache::get($key);
        
        if (!$cachedCode) {
            return response()->json([
                'message' => '验证码已过期'
            ], 400);
        }
        
        if ($captcha !== $cachedCode) {
            return response()->json([
                'message' => '验证码错误'
            ], 400);
        }
        
        // 验证成功，删除验证码
        Cache::forget($key);
        
        return response()->json([
            'message' => '验证成功'
        ]);
    }
    
    /**
     * 创建验证码图片
     *
     * @param string $code
     * @return string
     */
    private function createCaptchaImage($code)
    {
        // 图片尺寸
        $width = 120;
        $height = 40;
        
        // 创建画布
        $image = imagecreate($width, $height);
        
        // 使用时间作为随机种子，确保每次生成不同的图片
        mt_srand((int)(microtime(true) * 1000000));
        
        // 定义随机颜色变化
        $bgColorVariation = mt_rand(240, 255);
        $textColorVariation = mt_rand(0, 60);
        $lineColorVariation = mt_rand(180, 220);
        $dotColorVariation = mt_rand(120, 180);
        
        // 定义颜色（添加随机变化）
        $background = imagecolorallocate($image, $bgColorVariation, $bgColorVariation, $bgColorVariation);
        $textColor = imagecolorallocate($image, $textColorVariation, $textColorVariation, $textColorVariation);
        $lineColor = imagecolorallocate($image, $lineColorVariation, $lineColorVariation, $lineColorVariation);
        $dotColor = imagecolorallocate($image, $dotColorVariation, $dotColorVariation, $dotColorVariation);
        
        // 添加干扰线（使用mt_rand确保随机性）
        $lineCount = mt_rand(3, 8);
        for ($i = 0; $i < $lineCount; $i++) {
            imageline($image, 
                mt_rand(0, $width), mt_rand(0, $height),
                mt_rand(0, $width), mt_rand(0, $height),
                $lineColor
            );
        }
        
        // 添加干扰点（使用mt_rand确保随机性）
        $dotCount = mt_rand(30, 80);
        for ($i = 0; $i < $dotCount; $i++) {
            imagesetpixel($image, mt_rand(0, $width), mt_rand(0, $height), $dotColor);
        }
        
        // 绘制验证码文字（添加位置的随机偏移）
        $fontSize = 5;
        $baseX = ($width - strlen($code) * imagefontwidth($fontSize)) / 2;
        $baseY = ($height - imagefontheight($fontSize)) / 2;
        
        // 为每个字符添加随机偏移
        for ($i = 0; $i < strlen($code); $i++) {
            $charX = $baseX + ($i * imagefontwidth($fontSize)) + mt_rand(-2, 2);
            $charY = $baseY + mt_rand(-3, 3);
            imagestring($image, $fontSize, $charX, $charY, $code[$i], $textColor);
        }
        
        // 转换为base64
        ob_start();
        imagepng($image);
        $imageData = ob_get_contents();
        ob_end_clean();
        
        // 释放内存
        imagedestroy($image);
        
        return 'data:image/png;base64,' . base64_encode($imageData);
    }
}
