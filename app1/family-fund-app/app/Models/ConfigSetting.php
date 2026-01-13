<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ConfigSetting extends Model
{
    protected $fillable = [
        'key', 'value', 'type', 'category', 'description', 'is_sensitive', 'updated_by'
    ];

    protected $casts = [
        'is_sensitive' => 'boolean',
    ];

    public static array $types = [
        'string' => 'String',
        'integer' => 'Integer',
        'boolean' => 'Boolean',
        'json' => 'JSON',
        'email' => 'Email',
        'url' => 'URL',
        'path' => 'Path',
        'csv' => 'CSV List',
    ];

    public static array $categories = [
        'mail' => 'Email',
        'display' => 'Display & UI',
        'reports' => 'Reports',
        'storage' => 'Storage & Retention',
        'security' => 'Security',
        'features' => 'Feature Flags',
        'system' => 'System',
    ];

    /**
     * Get a config value with caching
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = Cache::remember("config_setting.{$key}", 3600, function () use ($key) {
            return static::where('key', $key)->first();
        });

        if (!$setting) {
            return $default;
        }

        return static::castValue($setting->value, $setting->type);
    }

    /**
     * Set a config value and clear cache
     */
    public static function setValue(string $key, mixed $value, ?string $updatedBy = null): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'updated_by' => $updatedBy]
        );

        Cache::forget("config_setting.{$key}");
        Cache::forget('config_settings.all');
    }

    /**
     * Cast value based on type
     */
    public static function castValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'integer' => (int) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($value, true),
            'csv' => array_map('trim', explode(',', $value ?? '')),
            default => $value,
        };
    }

    /**
     * Validate value based on type
     */
    public function validateValue(mixed $value): bool|string
    {
        return match ($this->type) {
            'integer' => is_numeric($value) || empty($value) ? true : 'Must be a number',
            'boolean' => in_array(strtolower((string) $value), ['true', 'false', '1', '0', 'yes', 'no', '']) ? true : 'Must be true/false',
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) || empty($value) ? true : 'Invalid email format',
            'url' => filter_var($value, FILTER_VALIDATE_URL) || empty($value) ? true : 'Invalid URL format',
            'json' => $this->isValidJson($value) ? true : 'Invalid JSON format',
            default => true,
        };
    }

    private function isValidJson(?string $value): bool
    {
        if (empty($value)) return true;
        json_decode($value);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Get display value (masked if sensitive)
     */
    public function getDisplayValueAttribute(): string
    {
        if ($this->is_sensitive && !empty($this->value)) {
            return str_repeat('*', 8);
        }
        return $this->value ?? '';
    }
}
