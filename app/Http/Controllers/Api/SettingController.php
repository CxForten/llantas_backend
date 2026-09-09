<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $business = Business::findOrFail($request->user()->business_id);

        return response()->json([
            'business' => [
                'id'              => $business->id,
                'name'            => $business->name,
                'ruc'             => $business->ruc,
                'address'         => $business->address,
                'phone'           => $business->phone,
                'email'           => $business->email,
                'logo_path'       => $business->logo_path,
                'estab'           => $business->estab,
                'pto_emision'     => $business->pto_emision,
                'sri_environment' => $business->sri_environment,
            ],
            'defaults' => [
                'margin_main'  => config('llantera.margin_main'),
                'margin_alt'   => config('llantera.margin_alt'),
                'card_fee_pct' => config('llantera.card_fee_pct'),
                'iva_rate'     => config('llantera.iva_rate'),
                'calc_order'   => config('llantera.calc_order'),
            ],
            'settings' => $this->keyValues($business->id),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'business'          => ['sometimes', 'array'],
            'business.name'     => ['sometimes', 'string', 'max:180'],
            'business.ruc'      => ['sometimes', 'nullable', 'string', 'max:20'],
            'business.address'  => ['sometimes', 'nullable', 'string', 'max:255'],
            'business.phone'    => ['sometimes', 'nullable', 'string', 'max:30'],
            'business.email'    => ['sometimes', 'nullable', 'email', 'max:180'],
            'business.estab'    => ['sometimes', 'string', 'size:3'],
            'business.pto_emision'     => ['sometimes', 'string', 'size:3'],
            'business.sri_environment' => ['sometimes', 'in:pruebas,produccion'],

            'settings'   => ['sometimes', 'array'],
            'settings.*' => ['nullable'],
        ]);

        $business = Business::findOrFail($request->user()->business_id);

        if (! empty($data['business'])) {
            $business->update($data['business']);
        }

        foreach ($data['settings'] ?? [] as $key => $value) {
            Setting::updateOrCreate(
                ['business_id' => $business->id, 'key' => $key],
                [
                    'value' => is_array($value) ? json_encode($value) : (string) $value,
                    'type'  => match (true) {
                        is_array($value)  => 'json',
                        is_bool($value)   => 'bool',
                        is_int($value)    => 'int',
                        default           => 'string',
                    },
                ]
            );
        }

        return response()->json([
            'message'  => 'Configuración guardada.',
            'business' => $business->fresh(),
            'settings' => $this->keyValues($business->id),
        ]);
    }

    /** Devuelve los settings como objeto plano, ya casteados. */
    private function keyValues(int $businessId): array
    {
        return Setting::where('business_id', $businessId)
            ->get()
            ->mapWithKeys(fn (Setting $s) => [
                $s->key => match ($s->type) {
                    'int'  => (int) $s->value,
                    'bool' => filter_var($s->value, FILTER_VALIDATE_BOOLEAN),
                    'json' => json_decode($s->value, true),
                    default => $s->value,
                },
            ])
            ->all();
    }
}
