<?php

namespace Tests\Feature;

use App\Http\Controllers\V1\Staff\UserController;
use App\Http\Requests\Staff\UserUpdate;
use App\Models\Ticket;
use App\Models\User;
use App\Services\AuthService;
use App\Services\StaffAccessService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class StaffSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Model::unguard();

        config([
            'app.key' => 'staff-security-test-key-32-characters',
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        Cache::flush();
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        DB::reconnect('sqlite');

        Schema::connection('sqlite')->create('v2_user', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('invite_user_id')->nullable();
            $table->string('email')->unique();
            $table->string('password')->default('password-hash');
            $table->string('password_algo')->nullable();
            $table->string('password_salt')->nullable();
            $table->string('token')->default('subscription-token');
            $table->string('uuid')->default('subscription-uuid');
            $table->integer('is_admin')->default(0);
            $table->integer('is_staff')->default(0);
            $table->integer('banned')->default(0);
            $table->unsignedInteger('plan_id')->nullable();
            $table->integer('expired_at')->nullable();
            $table->integer('created_at')->nullable();
            $table->integer('updated_at')->nullable();
        });

        Schema::connection('sqlite')->create('v2_ticket', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('subject')->default('Support');
            $table->integer('status')->default(0);
            $table->integer('created_at')->nullable();
            $table->integer('updated_at')->nullable();
        });
    }

    protected function tearDown(): void
    {
        Model::reguard();
        parent::tearDown();
    }

    public function testStaffAccessIsLimitedToOwnedNonPrivilegedUsersAndTickets(): void
    {
        $staff = $this->createUser('staff@example.com', null, 0, 1);
        $owned = $this->createUser('owned@example.com', $staff->id);
        $other = $this->createUser('other@example.com');
        $ownedAdmin = $this->createUser('admin@example.com', $staff->id, 1, 0);
        $ownedStaff = $this->createUser('other-staff@example.com', $staff->id, 0, 1);

        $ownedTicket = Ticket::create(['user_id' => $owned->id]);
        Ticket::create(['user_id' => $other->id]);
        Ticket::create(['user_id' => $ownedAdmin->id]);
        Ticket::create(['user_id' => $ownedStaff->id]);

        $access = new StaffAccessService();

        $this->assertSame([$owned->id], $access->users($staff->id)->pluck('id')->all());
        $this->assertSame([$ownedTicket->id], $access->tickets($staff->id)->pluck('id')->all());
        $this->assertNull($access->findUser($staff->id, $other->id));
        $this->assertNull($access->findUser($staff->id, $ownedAdmin->id));
        $this->assertNull($access->findUser($staff->id, $ownedStaff->id));
    }

    public function testStaffUserResponseDoesNotExposeAuthenticationSecrets(): void
    {
        $staff = $this->createUser('staff@example.com', null, 0, 1);
        $owned = $this->createUser('owned@example.com', $staff->id);
        $request = Request::create('/', 'GET', [
            'id' => $owned->id,
            'user' => ['id' => $staff->id],
        ]);

        $response = (new UserController(new StaffAccessService()))
            ->getUserInfoById($request);
        $data = $response->getOriginalContent()['data']->toArray();

        foreach (['password', 'password_algo', 'password_salt', 'token', 'uuid'] as $secret) {
            $this->assertArrayNotHasKey($secret, $data);
        }
    }

    public function testStaffUpdateValidationIgnoresFinancialAndSubscriptionFields(): void
    {
        $request = new UserUpdate();
        $validator = Validator::make([
            'id' => 10,
            'password' => 'safe-password',
            'banned' => 0,
            'balance' => 999999,
            'commission_balance' => 999999,
            'plan_id' => 99,
            'is_admin' => 1,
        ], $request->rules());

        $this->assertTrue($validator->passes());
        $this->assertSame(
            ['id', 'password', 'banned'],
            array_keys($validator->validated())
        );

        $shortPassword = Validator::make([
            'id' => 10,
            'password' => 'short',
            'banned' => 0,
        ], $request->rules());
        $this->assertFalse($shortPassword->passes());
    }

    public function testBulkBanRequiresAValidTargetOrFilter(): void
    {
        $staff = $this->createUser('staff@example.com', null, 0, 1);
        $request = Request::create('/', 'POST', [
            'user' => ['id' => $staff->id],
            'filter' => [[
                'key' => 'is_admin',
                'condition' => '=',
                'value' => 0,
            ]],
        ]);

        try {
            (new UserController(new StaffAccessService()))->ban($request);
            $this->fail('Invalid bulk filter should have been rejected.');
        } catch (HttpException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
        }
    }

    public function testAuthDataUsesCurrentRoleAndBanStateAndHonoursSessionRemoval(): void
    {
        $staff = $this->createUser('staff@example.com', null, 0, 1);
        $authService = new AuthService($staff);
        $authData = $authService->generateAuthData(Request::create('/', 'POST'))['auth_data'];

        $this->assertSame(1, (int)AuthService::decryptAuthData($authData)['is_staff']);

        $staff->update(['is_staff' => 0]);
        $this->assertSame(0, (int)AuthService::decryptAuthData($authData)['is_staff']);

        $staff->update(['banned' => 1]);
        $this->assertFalse(AuthService::decryptAuthData($authData));

        $staff->update(['banned' => 0]);
        $sessions = $authService->getSessions();
        $sessionId = array_key_first($sessions);
        $this->assertNotNull($sessionId);
        $this->assertTrue($authService->removeSession($sessionId));
        $this->assertFalse(AuthService::decryptAuthData($authData));
    }

    private function createUser(
        string $email,
        $inviteUserId = null,
        int $isAdmin = 0,
        int $isStaff = 0
    ): User {
        return User::create([
            'invite_user_id' => $inviteUserId,
            'email' => $email,
            'is_admin' => $isAdmin,
            'is_staff' => $isStaff,
            'banned' => 0,
            'token' => hash('md5', $email),
            'uuid' => hash('sha256', $email),
        ]);
    }
}
