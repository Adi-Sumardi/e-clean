<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $petugas;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->admin->assignRole('super_admin');
        $this->petugas = User::factory()->create();
        $this->petugas->assignRole('petugas');
    }

    public function test_admin_can_create_user_with_role(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/users', [
            'name' => 'Budi Satpam',
            'email' => 'budi@yapi.test',
            'password' => 'password123',
            'role' => 'satpam',
        ])->assertStatus(201)->assertJsonPath('data.roles.0', 'satpam');

        $this->assertDatabaseHas('users', ['email' => 'budi@yapi.test']);
    }

    public function test_petugas_cannot_create_user(): void
    {
        Sanctum::actingAs($this->petugas);

        $this->postJson('/api/v1/users', [
            'name' => 'X', 'email' => 'x@y.test', 'password' => 'password123', 'role' => 'petugas',
        ])->assertStatus(403);
    }

    public function test_can_filter_users_by_role(): void
    {
        $s = User::factory()->create();
        $s->assignRole('satpam');

        Sanctum::actingAs($this->admin);

        $res = $this->getJson('/api/v1/users?role=satpam')->assertStatus(200);
        $names = collect($res->json('data'))->pluck('id');
        $this->assertTrue($names->contains($s->id));
        $this->assertFalse($names->contains($this->petugas->id));
    }

    public function test_admin_can_update_user_role_and_status(): void
    {
        $user = User::factory()->create();
        $user->assignRole('petugas');

        Sanctum::actingAs($this->admin);

        $this->putJson("/api/v1/users/{$user->id}", [
            'role' => 'office_boy',
            'is_active' => false,
        ])->assertStatus(200)->assertJsonPath('data.roles.0', 'office_boy');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'is_active' => false]);
    }

    public function test_admin_cannot_delete_self(): void
    {
        Sanctum::actingAs($this->admin);

        $this->deleteJson("/api/v1/users/{$this->admin->id}")->assertStatus(422);
    }

    public function test_admin_can_delete_other_user(): void
    {
        $user = User::factory()->create();
        $user->assignRole('petugas');

        Sanctum::actingAs($this->admin);

        $this->deleteJson("/api/v1/users/{$user->id}")->assertStatus(200);
    }

    public function test_roles_endpoint_returns_assignable_roles(): void
    {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/users/roles')
            ->assertStatus(200)
            ->assertJsonFragment(['satpam'])
            ->assertJsonFragment(['petugas']);
    }
}
