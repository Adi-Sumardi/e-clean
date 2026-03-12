<?php

namespace Tests\Unit\Models;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_returns_default_when_key_not_found(): void
    {
        $result = Setting::get('nonexistent_key', 'default_value');
        $this->assertEquals('default_value', $result);
    }

    public function test_get_returns_null_when_key_not_found_and_no_default(): void
    {
        $result = Setting::get('nonexistent_key');
        $this->assertNull($result);
    }

    public function test_set_and_get_string_value(): void
    {
        Setting::set('app_name', 'E-Cleaning', 'string');

        $result = Setting::get('app_name');
        $this->assertEquals('E-Cleaning', $result);
    }

    public function test_set_and_get_boolean_value(): void
    {
        Setting::set('maintenance_mode', true, 'boolean');
        $this->assertTrue(Setting::get('maintenance_mode'));

        Setting::set('maintenance_mode', false, 'boolean');
        $this->assertFalse(Setting::get('maintenance_mode'));
    }

    public function test_set_and_get_integer_value(): void
    {
        Setting::set('max_uploads', 10, 'integer');

        $result = Setting::get('max_uploads');
        $this->assertIsInt($result);
        $this->assertEquals(10, $result);
    }

    public function test_set_and_get_json_value(): void
    {
        $data = ['key1' => 'value1', 'key2' => 'value2'];
        Setting::set('config', $data, 'json');

        $result = Setting::get('config');
        $this->assertIsArray($result);
        $this->assertEquals('value1', $result['key1']);
    }

    public function test_set_updates_existing_value(): void
    {
        Setting::set('app_name', 'Old Name', 'string');
        Setting::set('app_name', 'New Name', 'string');

        $this->assertEquals('New Name', Setting::get('app_name'));
        $this->assertEquals(1, Setting::where('key', 'app_name')->count());
    }

    public function test_set_with_group(): void
    {
        Setting::set('smtp_host', 'smtp.gmail.com', 'string', 'email');

        $setting = Setting::where('key', 'smtp_host')->first();
        $this->assertEquals('email', $setting->group);
    }

    public function test_setting_has_correct_fillable_attributes(): void
    {
        $setting = new Setting();
        $expected = ['key', 'value', 'type', 'group', 'description'];
        $this->assertEquals($expected, $setting->getFillable());
    }
}
