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
        if ($request->input('id')) {
            $knowledge = Knowledge::where('id', $request->input('id'))
                ->where('show', 1)
                ->first()
                ->toArray();
            if (!$knowledge) abort(500, __('Article does not exist'));
            $user = User::find($request->user['id']);
            $userService = new UserService();
            if (!$userService->isAvailable($user)) {
                $this->formatAccessData($knowledge['body']);
            }
            class KnowledgeController extends Controller
{
    public function fetch(Request $request)
    {
        if ($request->input('id')) {
            $knowledge = Knowledge::where('id', $request->input('id'))
                ->where('show', 1)
                ->first()
                ->toArray();
            if (!$knowledge) abort(500, __('Article does not exist'));
            $user = User::find($request->user['id']);
            $userService = new UserService();
            if (!$userService->isAvailable($user)) {
                $this->formatAccessData($knowledge['body']);
            }
            $knowledge['body'] = $this->replaceSubscribeUrls($knowledge['body'], $user['token']);
            $this->apple($knowledge['body']);
            return response([
                'data' => $knowledge
            ]);
        }
        $builder = Knowledge::select(['id', 'category', 'title', 'updated_at'])
            ->where('language', $request->input('language'))
            ->where('show', 1)
            ->orderBy('sort', 'ASC');
        $keyword = $request->input('keyword');
        if ($keyword) {
            $builder = $builder->where(function ($query) use ($keyword) {
                $query->where('title', 'LIKE', "%{$keyword}%")
                    ->orWhere('body', 'LIKE', "%{$keyword}%");
            });
        }

        $knowledges = $builder->get()
            ->groupBy('category');
        return response([
            'data' => $knowledges
        ]);
    }
    private function replaceSubscribeUrls($body, $userToken) {
    $subscribeUrl = Helper::getSubscribeUrl("/api/v1/client/subscribe?token={$userToken}");
    $websitesubscribeUrl = Helper::getwebsiteSubscribeUrl("/api/v1/client/subscribe?token={$userToken}");

    $body = str_replace('{{siteName}}', config('v2board.app_name', 'qmxy'), $body);
    $body = str_replace('{{websitesubscribeUrl}}', $websitesubscribeUrl, $body);
    $body = str_replace('{{urlEncodeSubscribeUrl}}', urlencode($websitesubscribeUrl), $body);
    $body = str_replace(
        '{{safeBase64SubscribeUrl}}',
        str_replace(array('+', '/', '='), array('-', '_', ''), base64_encode($websitesubscribeUrl)),
        $body
    );
    $body = str_replace('{{subscribeUrl}}', $subscribeUrl, $body);
    $body = str_replace('{{urlEncodeSubscribeUrl}}', urlencode($subscribeUrl), $body);
    $body = str_replace(
        '{{safeBase64SubscribeUrl}}',
        str_replace(array('+', '/', '='), array('-', '_', ''), base64_encode($subscribeUrl)),
        $body
    );

    return $body;
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
