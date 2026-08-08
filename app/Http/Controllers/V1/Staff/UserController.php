<?php

namespace App\Http\Controllers\V1\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserSendMail;
use App\Http\Requests\Staff\UserUpdate;
use App\Jobs\SendEmailJob;
use App\Models\User;
use App\Services\AuthService;
use App\Services\StaffAccessService;
use App\Support\AdminFilter;
use Illuminate\Http\Request;

class UserController extends Controller
{
    private $access;

    public function __construct(StaffAccessService $access)
    {
        $this->access = $access;
    }

    public function getUserInfoById(Request $request)
    {
        if (empty($request->input('id'))) {
            abort(500, __('Invalid parameter'));
        }
        $user = $this->access->findUser(
            $request->input('user.id'),
            $request->input('id')
        );
        if (!$user) abort(500, __('The user does not exist'));

        $user->makeHidden([
            'password',
            'password_algo',
            'password_salt',
            'token',
            'uuid',
        ]);

        return response([
            'data' => $user
        ]);
    }

    public function update(UserUpdate $request)
    {
        $params = $request->validated();
        $user = $this->access->findUser(
            $request->input('user.id'),
            $request->input('id')
        );
        if (!$user) {
            abort(500, __('The user does not exist'));
        }

        $updates = [
            'banned' => (int)$params['banned'],
        ];
        $passwordChanged = isset($params['password'])
            && trim((string)$params['password']) !== '';
        if ($passwordChanged) {
            $updates['password'] = password_hash($params['password'], PASSWORD_DEFAULT);
            $updates['password_algo'] = NULL;
            $updates['password_salt'] = NULL;
        }

        try {
            $user->update($updates);
        } catch (\Exception $e) {
            abort(500, __('Save failed'));
        }

        if ($passwordChanged || (int)$updates['banned'] === 1) {
            (new AuthService($user))->removeAllSession();
        }

        return response([
            'data' => true
        ]);
    }

    public function sendMail(UserSendMail $request)
    {
        $sortType = in_array($request->input('sort_type'), ['ASC', 'DESC']) ? $request->input('sort_type') : 'DESC';
        $sort = in_array($request->input('sort'), ['id', 'email', 'created_at', 'expired_at'], true)
            ? $request->input('sort')
            : 'created_at';
        $builder = $this->filteredUsers($request)->orderBy($sort, $sortType);
        $users = $builder->get();
        foreach ($users as $user) {
            SendEmailJob::dispatch([
                'email' => $user->email,
                'subject' => $request->input('subject'),
                'template_name' => 'notify',
                'template_value' => [
                    'name' => config('v2board.app_name', 'V2Board'),
                    'url' => config('v2board.app_url'),
                    'content' => $request->input('content')
                ]
            ]);
        }

        return response([
            'data' => true
        ]);
    }

    public function ban(Request $request)
    {
        $targetUserId = $request->input('target_user_id', $request->input('id'));
        $filters = $this->validFilters($request);
        if (!$targetUserId && !$filters) {
            abort(422, __('Invalid parameter'));
        }

        $builder = $this->filteredUsers($request, $filters);
        if ($targetUserId) {
            $builder->where('id', (int)$targetUserId);
        }
        $userIds = $builder->pluck('id');
        if ($userIds->isEmpty()) {
            abort(500, __('The user does not exist'));
        }

        try {
            User::whereIn('id', $userIds)->update([
                'banned' => 1
            ]);
        } catch (\Exception $e) {
            abort(500, __('Processing failed'));
        }

        foreach (User::whereIn('id', $userIds)->get() as $user) {
            (new AuthService($user))->removeAllSession();
        }

        return response([
            'data' => true
        ]);
    }

    private function filteredUsers(Request $request, ?array $filters = null)
    {
        $builder = $this->access->users($request->input('user.id'));
        $filters = $filters ?? $this->validFilters($request);

        foreach ($filters as $filter) {
            $condition = AdminFilter::normalizeCondition($filter['condition']);
            $builder->where(
                $filter['key'],
                $condition,
                AdminFilter::prepareValue($condition, $filter['value'])
            );
        }

        return $builder;
    }

    private function validFilters(Request $request): array
    {
        $allowedKeys = ['id', 'email', 'plan_id', 'banned', 'created_at', 'expired_at'];
        $allowedConditions = AdminFilter::allowedConditions();
        $validFilters = [];

        foreach ((array)$request->input('filter', []) as $filter) {
            if (
                !isset($filter['key'], $filter['condition'])
                || !array_key_exists('value', $filter)
                || (!is_scalar($filter['value']) && $filter['value'] !== null)
                || !in_array($filter['key'], $allowedKeys, true)
                || !in_array($filter['condition'], $allowedConditions, true)
            ) {
                continue;
            }
            $validFilters[] = $filter;
        }

        return $validFilters;
    }
}
