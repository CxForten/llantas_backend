<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Support\BusinessSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $businessId = $request->user()->business_id;
        $business   = Business::findOrFail($businessId);

        return response()->json([
            'business' => $this->businessPayload($business),
            'defaults' => BusinessSettings::all($businessId),
            'factory'  => [
                'margin_main'          => config('llantera.margin_main'),
                'margin_alt'           => config('llantera.margin_alt'),
                'card_fee_pct'         => config('llantera.card_fee_pct'),
                'iva_rate'             => config('llantera.iva_rate'),
                'calc_order'           => config('llantera.calc_order'),
                'require_cash_session' => (bool) config('llantera.require_cash_session'),
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'business'                 => ['sometimes', 'array'],
            'business.name'            => ['sometimes', 'string', 'max:180'],
            'business.ruc'             => ['sometimes', 'nullable', 'string', 'max:20'],
            'business.address'         => ['sometimes', 'nullable', 'string', 'max:255'],
            'business.phone'           => ['sometimes', 'nullable', 'string', 'max:30'],
            'business.email'           => ['sometimes', 'nullable', 'email', 'max:180'],
            'business.estab'           => ['sometimes', 'string', 'size:3'],
            'business.pto_emision'     => ['sometimes', 'string', 'size:3'],
            'business.sri_environment' => ['sometimes', 'in:pruebas,produccion'],

            'settings'                      => ['sometimes', 'array'],
            'settings.margin_main'          => ['sometimes', 'integer', 'min:0', 'max:300'],
            'settings.margin_alt'           => ['sometimes', 'integer', 'min:0', 'max:300'],
            'settings.card_fee_pct'         => ['sometimes', 'integer', 'min:0', 'max:100'],
            'settings.iva_rate'             => ['sometimes', 'integer', 'min:0', 'max:100'],
            'settings.calc_order'           => ['sometimes', 'in:discount_first,fee_first'],
            'settings.require_cash_session' => ['sometimes', 'boolean'],
        ], [
            'settings.margin_main.max' => 'El margen no puede pasar de 300%.',
            'settings.card_fee_pct.max' => 'El recargo no puede pasar de 100%.',
        ]);

        $businessId = $request->user()->business_id;
        $business   = Business::findOrFail($businessId);

        if (! empty($data['business'])) {
            $business->update($data['business']);
        }

        if (! empty($data['settings'])) {
            BusinessSettings::put($businessId, $data['settings']);
        }

        return response()->json([
            'message'  => 'Configuración guardada.',
            'business' => $this->businessPayload($business->fresh()),
            'defaults' => BusinessSettings::all($businessId),
        ]);
    }

    private function businessPayload(Business $business): array
    {
        return [
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
        ];
    }
}
