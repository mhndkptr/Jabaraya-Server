<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Services\FirebaseService;

class AuthController extends Controller
{
    protected $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    public function register(Request $request)
    {
        try 
        {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users|email:dns',
                'password' => 'required|string|min:8',
                'password_confirmation' => 'required|string|min:8|same:password',
                'avatarUrl' => 'sometimes|string',
                'avatarImage' => 'sometimes|image|max:1024|mimes:jpeg,png,jpg',
                'phone' => 'required|string|min:8',
            ]);
    
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'statusCode' => 422,
                    'message' => 'validation error',
                    'errors' => $validator->errors()
                ], 422);

            }

            if($request->has('avatarImage')) {
                if ($request->hasFile('avatarImage')) {
                    $fileUrl = $this->firebaseService->uploadFileAvatar($request->file('avatarImage'));
                    $avatarUrl = $fileUrl;
                }
            } else if ($request->has('avatarUrl')) {
                if ($request->avatarUrl != 'https://firebasestorage.googleapis.com/v0/b/jabaraya-test.appspot.com/o/avatars%2Fdefault-avatar-1.png?alt=media' && $request->avatarUrl != 'https://firebasestorage.googleapis.com/v0/b/jabaraya-test.appspot.com/o/avatars%2Fdefault-avatar-2.png?alt=media' && $request->avatarUrl != 'https://firebasestorage.googleapis.com/v0/b/jabaraya-test.appspot.com/o/avatars%2Fdefault-avatar-null.png?alt=media') {
                    return response()->json([
                        'status' => false,
                        'statusCode' => 422,
                        'message' => 'Avatar url is not valid',
                    ], 422);
                }

                $avatarUrl = $request->avatarUrl;
            } else {
                $avatarUrl = 'https://firebasestorage.googleapis.com/v0/b/jabaraya-test.appspot.com/o/avatars%2Fdefault-avatar-null.png?alt=media';
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'avatar' => $avatarUrl,
                'phone' => $request->phone,
                'role' => 'user',
            ]);
    
            $token = $user->createToken('USER_PERSONAL_TOKEN')->accessToken;
    
            return response()->json([
                'status' => true,
                'statusCode' => 200,
                'message' => 'User created successfully',
                'data' => [
                    'token' => $token,
                    'user' => $user,
                    'social_auth' => ($user->providerAuths()->where('user_id', $user->id)->exists() ? $user->providerAuths()->where('user_id', $user->id)->pluck('provider_name') : null),
                ],
            ], 200);
        } catch(\Throwable $th) {
            return response()->json([
                'status' => false,
                'statusCode' => 500,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function login(Request $request)
    {
        try
        {
            $credentials = $request->only('email', 'password');
            
            if (Auth::attempt($credentials)) {
                $user = Auth::user();
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
                    'statusCode' => 422,
                    'message' => 'Email & password does not match',
                ], 422);
            }
        } catch(\Throwable $th) {
            return response()->json([
                'status' => false,
                'statusCode' => 500,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try
        {
            $request->user()->token()->revoke();
            return response()->json([
                'status' => true,
                'statusCode' => 200,
                'message' => 'User successfully logged out',
                'data' => []
            ], 200);
        } catch(\Throwable $th) {
            return response()->json([
                'status' => false,
                'statusCode' => 500,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function tokenUpdate()
    {
        try 
        {
            $user = Auth::user();
            $user->token()->revoke();
            $token = $user->createToken('USER_PERSONAL_TOKEN')->accessToken;
            return response()->json([
                'status' => true,
                'statusCode' => 200,
                'message' => 'User verified',
                'data' => [
                    'token' => $token,
                    'role' => $user->role
                ]
            ], 200);
        } catch(\Throwable $th) {
            return response()->json([
                'status' => false,
                'statusCode' => 500,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function unauthenticated()
    {
        return response()->json([
            "status" => false,
            'statusCode' => 401,
            "message" => "Unauthenticated. Please login first",
        ], 401);
    }
}
