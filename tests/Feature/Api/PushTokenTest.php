<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Services\ExpoPushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PushTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_push_token(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/auth/push-token', [
            'expo_push_token' => 'ExponentPushToken[abc123]',
        ])->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'expo_push_token' => 'ExponentPushToken[abc123]',
        ]);
    }

    public function test_push_token_is_required(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/auth/push-token', [])->assertStatus(422);
    }

    public function test_user_can_unregister_push_token(): void
    {
        $user = User::factory()->create(['expo_push_token' => 'ExponentPushToken[x]']);
        Sanctum::actingAs($user);

        $this->deleteJson('/api/v1/auth/push-token')->assertStatus(200);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'expo_push_token' => null]);
    }

    public function test_expo_service_validates_token_format(): void
    {
        $svc = app(ExpoPushService::class);

        $this->assertTrue($svc->isValidToken('ExponentPushToken[xyz]'));
        $this->assertTrue($svc->isValidToken('ExpoPushToken[xyz]'));
        $this->assertFalse($svc->isValidToken('not-a-token'));
        $this->assertFalse($svc->isValidToken(null));
    }

    public function test_expo_service_skips_users_without_token(): void
    {
        Http::fake();
        $svc = app(ExpoPushService::class);

        $user = User::factory()->create(['expo_push_token' => null]);
        $this->assertFalse($svc->sendToUser($user, 'Title', 'Body'));

        Http::assertNothingSent();
    }

    public function test_expo_service_sends_to_users_with_valid_token(): void
    {
        Http::fake([
            'exp.host/*' => Http::response(['data' => [['status' => 'ok']]], 200),
        ]);
        $svc = app(ExpoPushService::class);

        $user = User::factory()->create(['expo_push_token' => 'ExponentPushToken[valid]']);
        $accepted = $svc->sendToUsers([$user], 'Title', 'Body', ['type' => 'test']);

        $this->assertSame(1, $accepted);
        Http::assertSent(fn ($req) => str_contains($req->url(), 'exp.host'));
    }
}
