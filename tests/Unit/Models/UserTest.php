<?php

namespace Tests\Unit\Models;

use App\Models\ActivityReport;
use App\Models\JadwalKebersihan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    public function test_user_can_have_roles(): void
    {
        $user = User::factory()->create();
        $user->assignRole('petugas');

        $this->assertTrue($user->hasRole('petugas'));
        $this->assertFalse($user->hasRole('admin'));
    }

    public function test_user_has_many_jadwal_kebersihan(): void
    {
        $user = User::factory()->create();
        $user->assignRole('petugas');

        JadwalKebersihan::factory()->count(3)->create([
            'petugas_id' => $user->id,
        ]);

        $this->assertCount(3, $user->jadwalKebersihan);
    }

    public function test_user_has_many_activity_reports(): void
    {
        $user = User::factory()->create();
        $user->assignRole('petugas');

        ActivityReport::factory()->count(5)->create([
            'petugas_id' => $user->id,
        ]);

        $this->assertCount(5, $user->activityReports);
    }

    public function test_user_password_is_hashed(): void
    {
        $user = User::factory()->create([
            'password' => 'plaintext',
        ]);

        $this->assertNotEquals('plaintext', $user->password);
        $this->assertTrue(strlen($user->password) > 20);
    }

    public function test_user_email_is_unique(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        User::factory()->create([
            'email' => 'test@example.com',
        ]);
    }

    public function test_user_has_fillable_attributes(): void
    {
        $fillable = (new User())->getFillable();

        $this->assertContains('name', $fillable);
        $this->assertContains('email', $fillable);
        $this->assertContains('password', $fillable);
    }

    public function test_user_hides_sensitive_attributes(): void
    {
        $user = User::factory()->create();
        $array = $user->toArray();

        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('remember_token', $array);
    }
}
