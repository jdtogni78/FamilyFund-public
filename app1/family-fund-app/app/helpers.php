<?php

use App\Models\ConfigSetting;

if (!function_exists('config_setting')) {
    /**
     * Get a config setting value
     */
    function config_setting(string $key, mixed $default = null): mixed
    {
        return ConfigSetting::getValue($key, $default);
    }
}
