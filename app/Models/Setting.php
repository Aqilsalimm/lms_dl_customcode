<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group'];

    protected static function booted()
    {
        static::saved(function ($setting) {
            Cache::forget("setting_{$setting->key}");
            Cache::forget("all_settings_pluck");
        });

        static::deleted(function ($setting) {
            Cache::forget("setting_{$setting->key}");
            Cache::forget("all_settings_pluck");
        });
    }

    /**
     * Get setting value with automatic application caching (3600s TTL)
     */
    public static function getValue(string $key, $default = null)
    {
        return Cache::remember("setting_{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Set setting value with automatic cache invalidation
     */
    public static function setValue(string $key, $value, string $group = 'general')
    {
        $setting = static::updateOrCreate(
            ['key' => $key],
            ['value' => (string) $value, 'group' => $group]
        );
        Cache::forget("setting_{$key}");
        Cache::forget("all_settings_pluck");
        return $setting;
    }
}
