<?php

namespace Tests\Unit\Models;

use App\Models\NotificationLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_log_belongs_to_user(): void
    {
        $user = User::factory()->create();

        $log = NotificationLog::create([
            'user_id' => $user->id,
            'type' => 'whatsapp',
            'title' => 'Test Notification',
            'message' => 'Test message',
            'status' => 'sent',
        ]);

        $this->assertInstanceOf(User::class, $log->user);
        $this->assertEquals($user->id, $log->user->id);
    }

    public function test_notification_log_casts_data_as_array(): void
    {
        $user = User::factory()->create();
        $data = ['key' => 'value', 'nested' => ['a' => 1]];

        $log = NotificationLog::create([
            'user_id' => $user->id,
            'type' => 'whatsapp',
            'title' => 'Test',
            'message' => 'Test',
            'data' => $data,
            'status' => 'sent',
        ]);

        $log->refresh();
        $this->assertIsArray($log->data);
        $this->assertEquals('value', $log->data['key']);
    }

    public function test_notification_log_casts_dates(): void
    {
        $user = User::factory()->create();

        $log = NotificationLog::create([
            'user_id' => $user->id,
            'type' => 'whatsapp',
            'title' => 'Test',
            'message' => 'Test',
            'status' => 'sent',
            'sent_at' => now(),
            'read_at' => now(),
        ]);

        $log->refresh();
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $log->sent_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $log->read_at);
    }

    public function test_notification_log_has_correct_fillable(): void
    {
        $log = new NotificationLog();
        $fillable = $log->getFillable();

        $this->assertContains('user_id', $fillable);
        $this->assertContains('type', $fillable);
        $this->assertContains('title', $fillable);
        $this->assertContains('message', $fillable);
        $this->assertContains('status', $fillable);
        $this->assertContains('error_message', $fillable);
    }
}
