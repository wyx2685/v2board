<?php

namespace App\Http\Controllers\V1\User;

use App\Http\Controllers\Controller;
use App\Models\Knowledge;
use App\Models\User;
use App\Services\UserService;
use App\Utils\Helper;
use Illuminate\Http\Request;

class KnowledgeController extends Controller
{
    public function fetch(Request $request)
    {
        // 通过ID获取单个文章
        if ($request->input('id')) {
            $knowledge = Knowledge::where('id', $request->input('id'))
                ->where('show', 1)
                ->first()
                ->toArray();
            if (!$knowledge) abort(500, __('Article does not exist')); // 如果文章不存在则返回错误
            $user = User::find($request->user['id']);
            $userService = new UserService();
            if (!$userService->isAvailable($user)) {
                $this->formatAccessData($knowledge['body']); // 如果用户无权限，格式化数据
            }
            $subscribeUrl = Helper::getSubscribeUrl($user['token']);
            $knowledge['body'] = str_replace('{{siteName}}', config('v2board.app_name', 'V2Board'), $knowledge['body']);
            $knowledge['body'] = str_replace('{{subscribeUrl}}', $subscribeUrl, $knowledge['body']);
            $knowledge['body'] = str_replace('{{urlEncodeSubscribeUrl}}', urlencode($subscribeUrl), $knowledge['body']);
            $knowledge['body'] = str_replace(
                '{{safeBase64SubscribeUrl}}',
                str_replace(
                    array('+', '/', '='),
                    array('-', '_', ''),
                    base64_encode($subscribeUrl)
                ),
                $knowledge['body']
            );
            $knowledge['body'] = str_replace('{{subscribeToken}}', $user['token'], $knowledge['body']);
            return response([
                'data' => $knowledge
            ]);
        }
    
        // 添加搜索功能
        $builder = Knowledge::select(['id', 'category', 'title', 'language', 'sort', 'created_at', 'updated_at'])
            ->where('show', 1) // 只获取显示状态为1的文章
            ->orderBy('sort', 'ASC');
    
        // 语言过滤
        $language = $request->input('language');
        if ($language) {
            $builder = $builder->where('language', $language);
        }
    
        // 分类过滤
        $category = $request->input('category');
        if ($category) {
            $builder = $builder->where('category', $category);
        }
    
        // 基于关键词的搜索
        $keyword = $request->input('keyword');
        if ($keyword) {
            $builder = $builder->where(function ($query) use ($keyword) {
                $query->where('title', 'LIKE', "%{$keyword}%")
                    ->orWhere('body', 'LIKE', "%{$keyword}%");
            });
        }
    
        // 返回结果，按分类分组并返回
        $knowledges = $builder->get()->groupBy('category'); // 按分类分组
    
        return response([
            'data' => $knowledges // 返回分类分组后的数据
        ]);
    }

    private function getBetween($input, $start, $end)
    {
        $substr = substr($input, strlen($start) + strpos($input, $start), (strlen($input) - strpos($input, $end)) * (-1));
        return $start . $substr . $end;
    }

    private function formatAccessData(&$body)
    {
        while (strpos($body, '<!--access start-->') !== false) {
            $accessData = $this->getBetween($body, '<!--access start-->', '<!--access end-->');
            if ($accessData) {
                $body = str_replace($accessData, '<div class="v2board-no-access">'. __('You must have a valid subscription to view content in this area') .'</div>', $body);
            }
        }
    }
}
