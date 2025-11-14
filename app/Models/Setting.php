<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
    ];

    // Helper method to get setting value
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();

        if (!$setting) {
            return $default;
        }

        return match ($setting->type) {
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $setting->value,
            'json' => json_decode($setting->value, true),
            default => $setting->value,
        };
    }

    // Helper method to set setting value
    public static function set(string $key, mixed $value, string $type = 'string', ?string $group = null): void
    {
        $valueToStore = match ($type) {
            'json' => json_encode($value),
            'boolean' => $value ? '1' : '0',
            default => (string) $value,
        };

        static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $valueToStore,
                'type' => $type,
                'group' => $group,
            ]
        );
    }
}
