<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

final class BusinessSettings
{
    public const EDITABLE = [
        'margin_main'          => ['type' => 'int',  'config' => 'llantera.margin_main'],
        'margin_alt'           => ['type' => 'int',  'config' => 'llantera.margin_alt'],
        'card_fee_pct'         => ['type' => 'int',  'config' => 'llantera.card_fee_pct'],
        'iva_rate'             => ['type' => 'int',  'config' => 'llantera.iva_rate'],
        'calc_order'           => ['type' => 'string', 'config' => 'llantera.calc_order'],
        'require_cash_session' => ['type' => 'bool', 'config' => 'llantera.require_cash_session']
    ];

    private static function cacheKey(int $businessId): string
    {
        return "business_settings_{$businessId}";
    }

    public static function all(int $businessId): array
    {
        return Cache::remember(self::cacheKey($businessId), 3600, function () use ($businessId) {
            $stored = Setting::where('business_id', $businessId)
                ->pluck('value', 'key')
                ->all();

            $result = [];

            foreach (self::EDITABLE as $key => $meta) {
                $raw = $stored[$key] ?? null;

                if ($raw === null) {
                    $result[$key] = config($meta['config']);
                    continue;
                }

                $result[$key] = match ($meta['type']) {
                    'int' => (int) $raw,
                    'bool' => filter_var($raw, FILTER_VALIDATE_BOOLEAN),
                    default => $raw,
                };
            }

            return $result;
        });
    }

    public static function get(int $businessId, string $key, mixed $fallback = null): mixed
    {
        return self::all($businessId)[$key] ?? $fallback;
    }
 
    public static function int(int $businessId, string $key, int $fallback = 0): int
    {
        return (int) (self::all($businessId)[$key] ?? $fallback);
    }
 
    public static function bool(int $businessId, string $key, bool $fallback = false): bool
    {
        $value = self::all($businessId)[$key] ?? $fallback;
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public static function put(int $businessId, array $values): void
    {
        foreach ($values as $key => $value) {
            if (! array_key_exists($key, self::EDITABLE)){
                continue;
            }

            Setting::updateOrCreate(
                ['business_id' => $businessId, 'key' => $key],
                [
                    'value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value,
                    'type' => self::EDITABLE[$key]['type'],
                ]
            );
        }

        self::forget($businessId);
    }

    public static function forget(int $businessId): void
    {
        Cache::forget(self::cacheKey($businessId));
    }
}