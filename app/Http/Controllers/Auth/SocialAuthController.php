<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use App\Models\ProviderAuth;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Google\Client as GoogleClient;

class SocialAuthController extends Controller
{
    protected $allowedProviders = ['google', 'facebook'];

    public function redirectToProvider($provider)
    {
        if (!in_array($provider, $this->allowedProviders)) {
            return response()->json([
                'status' => false,
                'statusCode' => 400,
                'message' => 'Error provider not supported'
            ], 400);
        }
        if ($provider == "google") {
            return Socialite::driver($provider)->stateless()->with(["prompt" => "select_account"])->redirect();
        } else {
            return Socialite::driver($provider)->stateless()->with(['auth_type' => 'reauthenticate'])->redirect();
        }
    }

    public function handleProviderCallback($provider)
    {   
        try
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
    
            return redirect()->away(env("FRONTEND_BASE_URL")."/login/redirect?token=$token");
        } catch (\Throwable $th) {
            return redirect(env("FRONTEND_BASE_URL").'/login')->withError('Something went wrong! '.$th->getMessage());
        }
        
    }

    public function handleProviderCallbackForMobile(Request $request, $provider)
    {
        if (!in_array($provider, $this->allowedProviders)) {
            return response()->json([
                'status' => false,
                'statusCode' => 400,
                'message' => 'Error provider not supported'
            ], 400);
        }
        try {
            if ($provider === "google") {
                $idToken = $request->input('id_token');
            
                $client = new GoogleClient(['client_id' => env('GOOGLE_CLIENT_ID')]);
                $payload = $client->verifyIdToken($idToken);
                
                if ($payload) {
                    $googleId = $payload['sub'];
                    $email = $payload['email'];
                    $name = $payload['name'];
                    $avatar = $payload['picture'];
    
                    $user = User::where('email', $email)->first();
    
                    if (!$user) {
                        $user = User::create([
                            'name' => $name,
                            'email' => $email,
                            'password' => null,
                            'role' => 'user',
                            'avatar' => $avatar,
                        ]);
                    }
    
                    $providerAuth = ProviderAuth::updateOrCreate(
                        [
                            'provider_id' => $googleId,
                            'provider_name' => 'google'
                        ],
                        [
                            'user_id' => $user->id,
                            'provider_token' => $idToken,
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
                            'social_auth' => ($user->providerAuths()->where('user_id', $user->id)->exists() ? $user->providerAuths()->where('user_id', $user->id)->pluck('provider_name') : null),
                        ],
                    ], 200);
                } else {
                    return response()->json([
                        'status' => false,
                        'statusCode' => 401,
                        'message' => 'Invalid Id Token',
                    ], 401);
                }
            } else {
                $token = $request->input('token');
                $socialUser = Socialite::driver($provider)->stateless()->userFromToken($token);

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
                        'social_auth' => ($user->providerAuths()->where('user_id', $user->id)->exists() ? $user->providerAuths()->where('user_id', $user->id)->pluck('provider_name') : null),
                    ],
                ], 200);
            }
            
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'statusCode' => 500,
                'message' => 'Something went wrong!',
                'error' => $th->getMessage()
            ], 500);
        }
    }
}

