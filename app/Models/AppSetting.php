<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    public const DEFAULT_APPLICATION_FEE = 10000;
    public const DEFAULT_PRINTBOX_BW_LOW_FEE = 750;
    public const DEFAULT_PRINTBOX_BW_BULK_FEE = 500;
    public const DEFAULT_PRINTBOX_COLOR_FEE = 750;

    protected $fillable = [
        'key',
        'value',
    ];

    public static function integer(string $key, int $default = 0): int
    {
        $setting = static::where('key', $key)->first();

        return $setting ? (int) $setting->value : $default;
    }

    public static function setInteger(string $key, int $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => (string) max(0, $value)]
        );
    }

    public static function printboxRates(): array
    {
        return [
            'bw_low' => static::integer('printbox_bw_low_fee', static::DEFAULT_PRINTBOX_BW_LOW_FEE),
            'bw_bulk' => static::integer('printbox_bw_bulk_fee', static::DEFAULT_PRINTBOX_BW_BULK_FEE),
            'color' => static::integer('printbox_color_fee', static::DEFAULT_PRINTBOX_COLOR_FEE),
        ];
    }
}
