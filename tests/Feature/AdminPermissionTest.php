<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AdminPermissionService;
use App\Services\AuthService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminPermissionTest extends TestCase
{
    private $base;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'cache.default' => 'array', 'app.key' => str_repeat('k', 32)]);
        Cache::flush();
        foreach (app('router')->getRoutes() as $route) {
            if (strpos($route->getActionName(), 'PermissionController@current') !== false) {
                $this->base = '/' . substr($route->uri(), 0, -strlen('permission/current'));
            }
        }
        Schema::create('v2_user', function (Blueprint $table) {
            $table->increments('id');
            foreach (['email', 'password', 'token', 'uuid'] as $column) $table->string($column);
            foreach (['password_algo', 'password_salt', 'remarks'] as $column) $table->string($column)->nullable();
            foreach (['is_admin', 'is_staff', 'banned', 'u', 'd', 'balance', 'commission_balance', 'commission_type', 'transfer_enable', 'auto_renewal', 'remind_expire', 'remind_traffic', 'last_login_at', 'telegram_id'] as $column) $table->integer($column)->default(0);
            foreach (['invite_user_id', 'plan_id', 'group_id', 'expired_at', 'device_limit', 'speed_limit', 'discount', 'commission_rate'] as $column) $table->integer($column)->nullable();
            $table->text('admin_permissions')->nullable();
            $table->integer('created_at')->nullable();
            $table->integer('updated_at')->nullable();
        });
        Schema::create('v2_plan', function (Blueprint $table) { $table->increments('id'); });
        Schema::create('v2_notice', function (Blueprint $table) { $table->increments('id'); });
        $this->assertNotEmpty($this->base);
    }

    private function account($permissions = null, $admin = true): User
    {
        return User::create(['email' => uniqid('user') . '@example.com', 'password' => 'hashed-password',
            'token' => uniqid('token'), 'uuid' => uniqid('uuid'), 'is_admin' => $admin ? 1 : 0,
            'is_staff' => 0, 'admin_permissions' => $permissions])->fresh();
    }

    private function token(User $user): string
    {
        return (new AuthService($user))->generateAuthData(Request::create('/'))['auth_data'];
    }

    private function updatePayload(User $user, array $changes = []): array
    {
        return array_merge(['id' => $user->id, 'email' => $user->email, 'banned' => $user->banned,
            'is_admin' => $user->is_admin, 'is_staff' => $user->is_staff], $changes);
    }

    public function testExistingFullAdminsAndRestrictedAdminsHaveDifferentAccess(): void
    {
        $full = $this->token($this->account());
        $restricted = $this->token($this->account(['notice.view']));
        $this->withHeader('authorization', $full)->getJson($this->base . 'permission/fetch')->assertOk();
        $this->withHeader('authorization', $restricted)->getJson($this->base . 'notice/fetch')->assertOk();
        $this->postJson($this->base . 'notice/drop', ['id' => 1])->assertForbidden()->assertJson(['permission_denied' => true]);
        $this->getJson($this->base . 'permission/fetch')->assertForbidden();
        $this->postJson($this->base . 'permission/save', ['id' => 1, 'is_super_admin' => true, 'permissions' => []])->assertForbidden();
    }

    public function testEmptyGrantsAndOrdinaryUsersCannotUseAdminEndpoints(): void
    {
        $this->withHeader('authorization', $this->token($this->account([])))
            ->getJson($this->base . 'permission/current')->assertOk()->assertJsonPath('data.is_super_admin', false);
        $this->getJson($this->base . 'notice/fetch')->assertForbidden();
        $this->withHeader('authorization', $this->token($this->account(null, false)))
            ->getJson($this->base . 'permission/current')->assertForbidden();
    }

    public function testChangesTakeEffectWithTheSamePreviouslyCachedToken(): void
    {
        $super = $this->account(); $admin = $this->account(['notice.view']);
        $superToken = $this->token($super); $token = $this->token($admin);
        AuthService::decryptAuthData($token); // Prime the legacy one-hour cache.
        $this->withHeader('authorization', $token)->getJson($this->base . 'notice/fetch')->assertOk();
        $this->withHeader('authorization', $superToken)->postJson($this->base . 'permission/save', [
            'id' => $admin->id, 'is_super_admin' => false, 'permissions' => [],
        ])->assertOk();
        $this->withHeader('authorization', $token)->getJson($this->base . 'notice/fetch')->assertForbidden();
        $admin->refresh()->update(['admin_permissions' => ['notice.view']]);
        $this->getJson($this->base . 'notice/fetch')->assertOk();
        $admin->update(['is_admin' => 0]);
        $this->getJson($this->base . 'notice/fetch')->assertForbidden();
    }

    public function testBansAndSessionRevocationsIgnoreTheOldIdentityCache(): void
    {
        $admin = $this->account(['notice.view']); $token = $this->token($admin);
        AuthService::decryptAuthData($token);
        $admin->update(['banned' => 1]);
        $this->withHeader('authorization', $token)->getJson($this->base . 'notice/fetch')->assertForbidden();
        $admin->update(['banned' => 0]);
        $this->getJson($this->base . 'notice/fetch')->assertOk();
        (new AuthService($admin))->removeAllSession();
        $this->getJson($this->base . 'notice/fetch')->assertForbidden();
    }

    public function testPermissionValidationDependenciesAndSelfProtection(): void
    {
        $super = $this->account(); $target = $this->account([]);
        $this->withHeader('authorization', $this->token($super));
        $this->postJson($this->base . 'permission/save', ['id' => $target->id, 'is_super_admin' => false,
            'permissions' => ['invented.permission']])->assertUnprocessable();
        $this->postJson($this->base . 'permission/save', ['id' => $target->id, 'is_super_admin' => false,
            'permissions' => ['ticket.reply', 'user.finance']])->assertOk();
        $permissions = $target->fresh()->admin_permissions;
        $this->assertContains('ticket.view', $permissions);
        $this->assertContains('user.view', $permissions);
        $this->assertContains('user.update', $permissions);
        $this->postJson($this->base . 'permission/save', ['id' => $super->id, 'is_super_admin' => false,
            'permissions' => []])->assertUnprocessable();
        $this->assertNull($super->fresh()->admin_permissions);
    }

    public function testRestrictedUserManagersCannotChangeAdminsOrPromoteUsers(): void
    {
        $super = $this->account(); $manager = $this->account(['user.view', 'user.update', 'user.delete']);
        $user = $this->account([], false);
        $this->withHeader('authorization', $this->token($manager));
        $this->getJson($this->base . 'user/getUserInfoById?id=' . $super->id)->assertNotFound();
        $this->postJson($this->base . 'user/update', $this->updatePayload($super, ['password' => 'replacement']))->assertStatus(500);
        $this->assertSame('hashed-password', $super->fresh()->password);
        $this->postJson($this->base . 'user/update', $this->updatePayload($user, ['is_admin' => 1]))->assertForbidden();
        $this->postJson($this->base . 'user/delUser', ['id' => $super->id])->assertStatus(500);
        $this->getJson($this->base . 'user/fetch')->assertOk()->assertJsonCount(1, 'data')->assertJsonMissing(['password' => 'hashed-password']);
        $detail = $this->getJson($this->base . 'user/getUserInfoById?id=' . $user->id)->assertOk()->json('data');
        foreach (['password', 'token', 'uuid', 'admin_permissions'] as $secret) $this->assertArrayNotHasKey($secret, $detail);
    }

    public function testFinancialAndBanFieldsNeedSeparateGrants(): void
    {
        $manager = $this->account(['user.view', 'user.update']); $user = $this->account([], false);
        $this->withHeader('authorization', $this->token($manager));
        $this->postJson($this->base . 'user/update', $this->updatePayload($user, ['balance' => 10000]))->assertForbidden()->assertJson(['permission_denied' => true]);
        $this->postJson($this->base . 'user/update', $this->updatePayload($user, ['banned' => 1]))->assertForbidden();
        $this->postJson($this->base . 'user/update', $this->updatePayload($user, ['remarks' => 'updated']))->assertOk();
        $manager->update(['admin_permissions' => ['user.view', 'user.update', 'user.finance']]);
        $this->postJson($this->base . 'user/update', $this->updatePayload($user, ['balance' => 10000]))->assertOk();
        $this->assertSame(10000, (int)$user->fresh()->balance);
    }

    public function testNewlyPromotedAdminsStartRestrictedAndFullAdminsCannotBeDeleted(): void
    {
        $super = $this->account(); $user = $this->account(null, false);
        $this->withHeader('authorization', $this->token($super));
        $this->postJson($this->base . 'user/update', $this->updatePayload($user, ['is_admin' => 1, 'admin_permissions' => null]))->assertOk();
        $this->assertSame([], $user->fresh()->admin_permissions);
        $this->postJson($this->base . 'user/update', $this->updatePayload($super, ['is_admin' => 0]))->assertUnprocessable();
        $this->postJson($this->base . 'user/update', $this->updatePayload($super, ['banned' => 1]))->assertUnprocessable();
        $this->postJson($this->base . 'user/delUser', ['id' => $super->id])->assertUnprocessable();
    }

    public function testEveryExistingAdministrativeActionHasAnExplicitPolicy(): void
    {
        $actions = config('admin_permissions.actions');
        foreach (app('router')->getRoutes() as $route) {
            if (strpos('/' . $route->uri(), $this->base) === 0) {
                $action = ltrim($route->getActionName(), '\\');
                if (strpos($action, 'PermissionController@') !== false) continue;
                $this->assertArrayHasKey($action, $actions, $action);
            }
        }
        $allGrants = ['id' => 1, 'is_admin' => 1, 'admin_permissions' => array_keys(config('admin_permissions.labels'))];
        $this->assertFalse(AdminPermissionService::allowsAction($allGrants, 'NewController@unmapped'));
        $this->assertFalse(AdminPermissionService::allowsAction($allGrants, 'App\\Http\\Controllers\\V1\\Admin\\PermissionController@save'));
    }

    public function testMalformedPermissionStorageNeverBecomesFullAccess(): void
    {
        $admin = $this->account([]); $token = $this->token($admin);
        foreach (['null', '{broken', '"notice.view"', '{"grant":"notice.view"}'] as $invalid) {
            \Illuminate\Support\Facades\DB::table('v2_user')->where('id', $admin->id)->update(['admin_permissions' => $invalid]);
            $this->withHeader('authorization', $token)->getJson($this->base . 'permission/fetch')->assertForbidden();
            $this->getJson($this->base . 'notice/fetch')->assertForbidden();
        }
    }

    public function testAuxiliaryCurrencyLabelsDoNotExposeSecretConfiguration(): void
    {
        config(['v2board.email_password' => 'must-stay-secret', 'v2board.server_token' => 'node-secret']);
        $this->withHeader('authorization', $this->token($this->account(['notice.view'])));
        $this->getJson($this->base . 'config/fetch?key=site')->assertOk()
            ->assertExactJson(['data' => ['site' => ['currency' => 'CNY', 'currency_symbol' => '¥']]]);
        $this->getJson($this->base . 'config/fetch?key=email')->assertForbidden();
        $this->getJson($this->base . 'config/fetch')->assertForbidden();
    }

    public function testBulkBanningCannotAffectAnyPrivilegedAccount(): void
    {
        $super = $this->account(); $manager = $this->account(['user.ban']);
        $user = $this->account([], false); $staff = $this->account([], false);
        $staff->update(['is_staff' => 1]);
        $this->withHeader('authorization', $this->token($manager))->postJson($this->base . 'user/ban')->assertOk();
        $this->assertSame(1, (int)$user->fresh()->banned);
        foreach ([$super, $manager, $staff] as $protected) $this->assertSame(0, (int)$protected->fresh()->banned);
    }

    public function testLoginChecksAndProfileReloadUseCurrentPermissions(): void
    {
        $admin = $this->account(['notice.view']); $token = $this->token($admin);
        AuthService::decryptAuthData($token);
        $admin->update(['admin_permissions' => []]);
        $this->withHeader('authorization', $token)->getJson('/api/v1/user/checkLogin')->assertOk()
            ->assertJsonPath('data.admin_access.permissions', []);
        $profile = $this->getJson('/api/v1/user/info')->assertOk()->assertJsonPath('data.admin_access.permissions', []);
        $this->assertArrayNotHasKey('admin_permissions', $profile->json('data'));
    }
}
