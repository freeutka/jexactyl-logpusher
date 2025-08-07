<?php

namespace Jexactyl\Models;

use Illuminate\Database\Eloquent\Model;

class SettingPaste extends Model
{
    protected $table = 'settings_paste';
    protected $fillable = ['key', 'value'];

    public static function getValue($key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    public static function setValue($key, $value)
    {
        return self::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
