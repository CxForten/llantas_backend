<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::with('role.permissions', 'business')
            ->where('email', $credentials['email'])
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'Las credenciales no son correctas.',
            ]);
        }

        if (! $user->active) {
            throw ValidationException::withMessages([
                'email' => 'Este usuario está desactivado.',
            ]);
        }

        // Un token por dispositivo: si vuelve a entrar, invalidamos los anteriores
        $user->tokens()->delete();

        return response()->json([
            'token' => $user->createToken('llantera-desktop')->plainTextToken,
            'user'  => new UserResource($user),
        ]);
    }

    /**
     * Login rápido con PIN para el mostrador.
     * Se activa cuando exista la gestión de roles; hoy funciona igual.
     */
    public function loginWithPin(Request $request): JsonResponse
    {
        $data = $request->validate([
            'pin' => ['required', 'string', 'min:4', 'max:12'],
        ]);

        $businessId = $request->input('business_id')
            ?? optional(\App\Models\Business::first())->id;

        $user = User::with('role.permissions', 'business')
            ->where('business_id', $businessId)
            ->where('active', true)
            ->whereNotNull('pin')
            ->get()
            ->first(fn (User $u) => Hash::check($data['pin'], $u->pin));

        if (! $user) {
            throw ValidationException::withMessages([
                'pin' => 'PIN incorrecto.',
            ]);
        }

        return response()->json([
            'token' => $user->createToken('llantera-pos')->plainTextToken,
            'user'  => new UserResource($user),
        ]);
    }

    public function me(Request $request): UserResource
    {
        return new UserResource(
            $request->user()->load('role.permissions', 'business')
        );
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada.']);
    }
}
