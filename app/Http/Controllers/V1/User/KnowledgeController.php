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
    private $share_url = "";
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

            $this->share_url = config('v2board.apple_share_api');
            if (!empty($this->share_url)) {
                $this->apple($knowledge['body']);
            }

            $knowledge['body'] = str_replace('{{subscribeToken}}', $user['token'], $knowledge['body']);

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

    private function apple(&$body)
    {
        try {
            $stream_opts = [
                "ssl" => [
                    "verify_peer" => false,
                    "verify_peer_name" => false,
                ],
                "http" => [
                    'timeout' => 5,
                    "header" => [
                        "Content-Type: application/json",
                        "Accept: application/json, text/plain, */*"
                    ]
                ]
            ];
            $result = file_get_contents($this->share_url, false, stream_context_create($stream_opts));
            if ($result === false) {
                throw new Exception("Không lấy được, đã xảy ra lỗi khi yêu cầu trang");
            }
            $req = json_decode($result, true);
            if (json_last_error() != JSON_ERROR_NONE) {
                throw new Exception("Không lấy được, lỗi phân tích dữ liệu JSON, vui lòng kiểm tra xem đó có phải là shareapi không");
            }
            if ($req["status"]) {
                $accounts = $req["accounts"];
                for ($i = 0; $i < sizeof($accounts); $i++) {
                    $body = str_replace("{{apple_id$i}}", $accounts[$i]["username"], $body);
                    $body = str_replace("{{apple_pw$i}}", $accounts[$i]["password"], $body);
                    $body = str_replace("{{apple_status$i}}", $accounts[$i]["status"] ? "Bình Thường" : "Đang lỗi, chờ cập nhật", $body);
                    $body = str_replace("{{apple_time$i}}", $accounts[$i]["last_check"], $body);
                }
            } else {
                $body = str_replace("{{apple_id0}}", "Không thể lấy được,{$req["msg"]}", $body);
            }
        } catch (Exception $error) {
            $body = str_replace("{{apple_id0}}", $error, $body);
        }
    }
}
