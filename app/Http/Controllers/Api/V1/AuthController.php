<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\UserResource;
use App\Models\User;
use App\Services\AssetRequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /** Masa berlaku token aplikasi mobile. */
    public const TOKEN_DAYS = 30;

    /**
     * POST /api/v1/auth/login  {nik, password, device_name}
     * Sama dengan login web: NIK + password, maksimal 5 percobaan gagal per menit per NIK & IP.
     */
    public function login(Request $request)
    {
        $data = $request->validate([
            'nik' => 'required|string|min:5|max:20',
            'password' => 'required|string',
            'device_name' => 'required|string|max:100',
        ]);

        $key = 'api-login|' . Str::lower(trim($data['nik'])) . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'nik' => 'Terlalu banyak percobaan. Coba lagi dalam ' . RateLimiter::availableIn($key) . ' detik.',
            ])->status(429);
        }

        $user = User::where('nik', trim($data['nik']))->first();
        if (!$user || !$user->password || !Hash::check($data['password'], $user->password)) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['password' => 'NIK atau password salah.']);
        }
        if ((string) $user->is_active === '0') {
            throw ValidationException::withMessages(['nik' => 'Akun Anda tidak aktif. Hubungi tim IT.'])->status(403);
        }

        RateLimiter::clear($key);
        $token = $user->createToken($data['device_name'], ['*'], now()->addDays(self::TOKEN_DAYS));

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at?->toIso8601String(),
            'user' => $this->profile($user),
        ]);
    }

    /** POST /api/v1/auth/logout — cabut token yang sedang dipakai. */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Berhasil keluar.']);
    }

    /** GET /api/v1/me — profil, role, permission, dan fitur yang boleh dipakai. */
    public function me(Request $request)
    {
        return response()->json(['user' => $this->profile($request->user())]);
    }

    protected function profile(User $user): array
    {
        $user->loadMissing(['position:id,name', 'department:id,code,name', 'jobsite:id,code,name']);

        return (new UserResource($user))->resolve() + [
            'roles' => $user->getRoleNames()->values(),
            'permissions' => $user->getAllPermissions()->pluck('name')->values(),
            'features' => [
                'stock_opname' => $user->can('Stock Opname'),
                'submit_asset_request_for_others' => AssetRequestService::canSubmitForOthers($user),
            ],
        ];
    }
}
