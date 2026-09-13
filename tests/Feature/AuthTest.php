<?php

use App\Enum\RoleEnum;
use App\Enum\StatusEnum;
use App\Enum\UserBranchRoleStatusEnum;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use App\Models\UserBranchRole;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('user cannot login with invalid credentials', function () {
    User::create([
        'name'     => 'Test User',
        'username' => 'testuser',
        'password' => bcrypt('password123'),
        'status'   => StatusEnum::ACTIVE,
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'username' => 'testuser',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(401)
             ->assertJson(['message' => 'Invalid credentials.']);
});

test('login requires username or email and password', function () {
    $response = $this->postJson('/api/v1/auth/login', []);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['username', 'password']);
});

test('inactive user cannot login', function () {
    User::create([
        'name'     => 'Inactive User',
        'username' => 'inactive',
        'password' => bcrypt('password123'),
        'status'   => StatusEnum::INACTIVE,
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'username' => 'inactive',
        'password' => 'password123',
    ]);

    $response->assertStatus(403)
             ->assertJson(['message' => 'Account is inactive.']);
});

test('owner can login with username and receives all permissions', function () {
    $owner = User::create([
        'name'     => 'Owner User',
        'username' => 'owner',
        'password' => bcrypt('password123'),
        'status'   => StatusEnum::ACTIVE,
    ]);

    $ownerRole = Role::where('name', RoleEnum::OWNER->value)->first();

    UserBranchRole::create([
        'userUuid'   => $owner->uuid,
        'branchUuid' => null,
        'roleId'     => $ownerRole->id,
        'status'     => UserBranchRoleStatusEnum::ACTIVE,
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'username' => 'owner',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
             ->assertJsonStructure([
                 'data' => [
                     'token',
                     'user' => [
                         'uuid',
                         'name',
                         'username',
                         'email',
                         'assignedBranch',
                         'role',
                         'status',
                         'permissions',
                         'createdAt',
                         'updatedAt',
                     ],
                 ],
             ]);

    $data = $response->json('data');
    expect($data['token'])->not->toBeNull()
        ->and($data['user']['username'])->toBe('owner')
        ->and($data['user']['role'])->toBe('Owner')
        ->and($data['user']['status'])->toBe('Active')
        ->and(count($data['user']['permissions']))->toBe(24);
});

test('staff user logs in with username and receives assigned branch automatically', function () {
    $branch = Branch::create([
        'name'          => 'Main Pharmacy',
        'address'       => '123 Main St',
        'phone'         => '0712345678',
        'invoicePrefix' => 'MPH',
        'status'        => StatusEnum::ACTIVE,
    ]);

    $pharmacist = User::create([
        'name'     => 'Pharmacist Jane',
        'username' => 'jane',
        'email'    => 'jane@pharmacy.com',
        'password' => bcrypt('password123'),
        'status'   => StatusEnum::ACTIVE,
    ]);

    $role = Role::where('name', RoleEnum::PHARMACIST->value)->first();

    UserBranchRole::create([
        'userUuid'   => $pharmacist->uuid,
        'branchUuid' => $branch->uuid,
        'roleId'     => $role->id,
        'status'     => UserBranchRoleStatusEnum::ACTIVE,
    ]);

    // Login with username only (no branchUuid in request!)
    $response = $this->postJson('/api/v1/auth/login', [
        'username' => 'jane',
        'password' => 'password123',
    ]);

    $response->assertStatus(200);

    $data = $response->json('data');
    expect($data['token'])->not->toBeNull()
        ->and($data['user']['username'])->toBe('jane')
        ->and($data['user']['assignedBranch']['uuid'])->toBe($branch->uuid)
        ->and($data['user']['assignedBranch']['name'])->toBe('Main Pharmacy')
        ->and($data['user']['role'])->toBe('Pharmacist')
        ->and($data['user']['status'])->toBe('Active')
        ->and(count($data['user']['permissions']))->toBe(13);
});

test('staff user can login with email instead of username', function () {
    $branch = Branch::create([
        'name'          => 'Kariakoo Pharmacy',
        'address'       => '77 Kariakoo St',
        'phone'         => '0712345600',
        'invoicePrefix' => 'KRK',
        'status'        => StatusEnum::ACTIVE,
    ]);

    $manager = User::create([
        'name'     => 'Manager Alice',
        'username' => 'alice',
        'email'    => 'alice@pharmacy.com',
        'password' => bcrypt('password123'),
        'status'   => StatusEnum::ACTIVE,
    ]);

    $role = Role::where('name', RoleEnum::BRANCH_MANAGER->value)->first();

    UserBranchRole::create([
        'userUuid'   => $manager->uuid,
        'branchUuid' => $branch->uuid,
        'roleId'     => $role->id,
        'status'     => UserBranchRoleStatusEnum::ACTIVE,
    ]);

    // Login with email only
    $response = $this->postJson('/api/v1/auth/login', [
        'email'    => 'alice@pharmacy.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200);

    $data = $response->json('data');
    expect($data['token'])->not->toBeNull()
        ->and($data['user']['username'])->toBe('alice')
        ->and($data['user']['assignedBranch']['name'])->toBe('Kariakoo Pharmacy')
        ->and($data['user']['role'])->toBe('Branch Manager')
        ->and($data['user']['status'])->toBe('Active')
        ->and(count($data['user']['permissions']))->toBe(18);
});

test('staff user cannot login if not assigned to any active branch', function () {
    $user = User::create([
        'name'     => 'Unassigned User',
        'username' => 'unassigned',
        'password' => bcrypt('password123'),
        'status'   => StatusEnum::ACTIVE,
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'username' => 'unassigned',
        'password' => 'password123',
    ]);

    $response->assertStatus(403)
             ->assertJson(['message' => 'User is not assigned to any active branch.']);
});

test('authenticated user can fetch their profile via me endpoint', function () {
    $branch = Branch::create([
        'name'          => 'Branch A',
        'address'       => '789 Market Rd',
        'phone'         => '0712345670',
        'invoicePrefix' => 'BRA',
        'status'        => StatusEnum::ACTIVE,
    ]);

    $user = User::create([
        'name'     => 'Cashier John',
        'username' => 'john',
        'password' => bcrypt('password123'),
        'status'   => StatusEnum::ACTIVE,
    ]);

    $role = Role::where('name', RoleEnum::CASHIER->value)->first();

    UserBranchRole::create([
        'userUuid'   => $user->uuid,
        'branchUuid' => $branch->uuid,
        'roleId'     => $role->id,
        'status'     => UserBranchRoleStatusEnum::ACTIVE,
    ]);

    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeaders([
        'Authorization' => "Bearer {$token}",
    ])->getJson('/api/v1/auth/me');

    $response->assertStatus(200);
    $data = $response->json('data');
    expect($data['token'])->toBeNull()
        ->and($data['user']['username'])->toBe('john')
        ->and($data['user']['assignedBranch']['uuid'])->toBe($branch->uuid)
        ->and($data['user']['role'])->toBe('Cashier')
        ->and($data['user']['status'])->toBe('Active')
        ->and(count($data['user']['permissions']))->toBe(7);
});

test('user can logout and revoke their token', function () {
    $user = User::create([
        'name'     => 'Logout User',
        'username' => 'logoutuser',
        'password' => bcrypt('password123'),
        'status'   => StatusEnum::ACTIVE,
    ]);

    $token = $user->createToken('logout-token')->plainTextToken;

    $response = $this->withHeaders([
        'Authorization' => "Bearer {$token}",
    ])->postJson('/api/v1/auth/logout');

    $response->assertStatus(200)
             ->assertJson(['message' => 'Logged out successfully.']);

    expect($user->tokens()->count())->toBe(0);

    // Attempting to access protected route with revoked token should fail
    auth()->forgetGuards();

    $meResponse = $this->withHeaders([
        'Authorization' => "Bearer {$token}",
    ])->getJson('/api/v1/auth/me');

    $meResponse->assertStatus(401);
});
