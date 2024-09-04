<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GiftcardGenerate;
use App\Models\Giftcard;
use App\Utils\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GiftcardController extends Controller
{
    public function fetch(Request $request)
    {
        $current = $request->input('current', 1);
        $pageSize = max($request->input('pageSize', 10), 10);
        $sortType = in_array($request->input('sort_type'), ['ASC', 'DESC']) ? $request->input('sort_type') : 'DESC';
        $sort = $request->input('sort', 'id');
        
        $builder = Giftcard::orderBy($sort, $sortType);
        $total = $builder->count();
        $giftcards = $builder->forPage($current, $pageSize)->get();

        return response([
            'data' => $giftcards,
            'total' => $total
        ]);
    }

    public function generate(GiftcardGenerate $request)
    {
        if ($request->input('generate_count')) {
            $this->multiGenerate($request);
            return;
        }

        $params = $request->validated();
        if (!$request->input('id')) {
            if (!isset($params['code'])) {
                $params['code'] = Helper::randomChar(16);
            }
            if (!Giftcard::create($params)) {
                abort(500, '礼品卡创建失败');
            }
        } else {
            $giftcard = Giftcard::find($request->input('id'));
            if (!$giftcard) {
                abort(404, '礼品卡不存在');
            }
            try {
                $giftcard->update($params);
            } catch (\Exception $e) {
                abort(500, '礼品卡保存失败');
            }
        }

        return response([
            'data' => true
        ]);
    }

    private function multiGenerate(GiftcardGenerate $request)
    {
        $giftcards = [];
        $giftcard = $request->validated();
        $giftcard['created_at'] = $giftcard['updated_at'] = time();
        unset($giftcard['generate_count']);
        
        for ($i = 0; $i < $request->input('generate_count'); $i++) {
            do {
                $giftcard['code'] = Helper::randomChar(16);
            } while (Giftcard::where('code', $giftcard['code'])->exists());
            array_push($giftcards, $giftcard);
        }
        DB::beginTransaction();
        try {
            if (!Giftcard::insert($giftcards)) {
                throw new \Exception('礼品卡批量生成失败');
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            abort(500, $e->getMessage());
        }

        $data = "名称,类型,数值,开始时间,结束时间,可用次数,礼品卡卡密,生成时间\r\n";
        foreach ($giftcards as $giftcard) {
            $type = ['', '金额', '时长', '流量', '重置', '套餐'][$giftcard['type']];
            $value = ['', round($giftcard['value']/100, 2), $giftcard['value'] . '天', $giftcard['value'] . 'GB', '-', $giftcard['value'] . '天'][$giftcard['type']];
            $startTime = date('Y-m-d H:i:s', $giftcard['started_at']);
            $endTime = date('Y-m-d H:i:s', $giftcard['ended_at']);
            $limitUse = $giftcard['limit_use'] ?? '不限制';
            $createTime = date('Y-m-d H:i:s', $giftcard['created_at']);
            $data .= "{$giftcard['name']},{$type},{$value},{$startTime},{$endTime},{$limitUse},{$giftcard['code']},{$createTime}\r\n";
        }

        // Return the CSV data as a response
       echo($data);
    }

    public function drop(Request $request)
    {
        $giftcardId = $request->input('id');
        if (empty($giftcardId)) {
            abort(400, '未找到礼品卡');
        }

        $giftcard = Giftcard::find($giftcardId);
        if (!$giftcard) {
            abort(404, '礼品卡不存在');
        }

        if (!$giftcard->delete()) {
            abort(500, '删除失败');
        }

        return response([
            'data' => true
        ]);
    }
}
