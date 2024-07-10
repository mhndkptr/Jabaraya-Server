<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use App\Models\ProviderAuth;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SocialAuthController extends Controller
{
    protected $allowedProviders = ['google'];

    public function redirectToProvider($provider)
    {
        if (!in_array($provider, $this->allowedProviders)) {
            return response()->json([
                'status' => false,
                'statusCode' => 400,
                'message' => 'Error provider not supported'
            ], 400);
        }
        return Socialite::driver($provider)->stateless()->redirect();
    }

    public function handleProviderCallback($provider)
    {   
        $socialUser = Socialite::driver($provider)->stateless()->user();

        $user = User::where('email', $socialUser->getEmail())->first();

        if (!$user) {
            $user = User::create([
                'name' => $socialUser->getName(),
                'email' => $socialUser->getEmail(),
                'password' => null,
                'role' => 'user', 
                'avatar' => $socialUser->getAvatar(),
            ]);
        }

        $providerAuth = ProviderAuth::updateOrCreate(
            [
                'provider_id' => $socialUser->getId(), 
                'provider_name' => $provider
            ],
            [
                'user_id' => $user->id,
                'provider_token' => $socialUser->token,
                'provider_refresh_token' => $socialUser->refreshToken,
            ]
        );

        Auth::login($user);

        $token = $user->createToken('USER_PERSONAL_TOKEN')->accessToken;

        return response()->json([
                'status' => true,
                'statusCode' => 200,
                'message' => 'User login successfully',
                'data' => [
                    'token' => $token,
                    'user' => $user,
                    'social_auth' => $provider,
                ],
        ], 200);
    }
}

