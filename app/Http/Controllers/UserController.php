<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\UserUpdateRequest;
use App\Services\FirebaseService;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    protected $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    public function index()
    {
        $userData = auth()->user();
        if ($userData->role === 'admin') {
            $users = User::all();
            return response()->json([
                'status' => true,
                'statusCode' => 200,
                'message' => 'User information',
                'data' => $users,
            ], 200);
        } else {
            return response()->json([
                'status' => false,
                'statusCode' => 401,
                'message' => 'Your current role not authorized',
            ], 401);
        }
    }

    public function show()
    {
        $userData = auth()->user();
        return response()->json([
            'status' => true,
            'statusCode' => 200,
            'message' => 'Profile information',
            'data' => [
                'user' => $userData,
                'social_auth' => ($userData->providerAuths()->where('user_id', $userData->id)->exists() ? $userData->providerAuths()->where('user_id', $userData->id)->first()->provider_name : null),
            ]
        ], 200);
    }

    public function update(UserUpdateRequest $request)
    {
        try
        {
            $avatarUrl = auth()->user()->avatar;

            $request->user()->fill($request->validated());

            $user = auth()->user();
            if (($user->providerAuths()->where('user_id', $user->id)->exists()) && $request->has('email')) {
                return response()->json([
                    'status' => false,
                    'statusCode' => 401,
                    'message' => 'Email cannot be change',
                ], 401);
            }

            $user->name = $request->has('name') ? $request->name : $user->name;
            $user->email = $request->has('email') ? $request->email : $user->email;

            if($request->has('avatarImage')) {
                if ($request->hasFile('avatarImage')) {
                    if ($user->avatar) {
                        $this->firebaseService->deleteFile($avatarUrl);
                    }
                    $fileUrl = $this->firebaseService->uploadFileAvatar($request->file('avatarImage'));
                    $user->avatar = $fileUrl;
                }
            } else if ($request->has('avatarUrl')) {
                if ($request->avatarUrl != 'https://firebasestorage.googleapis.com/v0/b/jabaraya-test.appspot.com/o/avatars%2Fdefault-avatar-1.png?alt=media' && $request->avatarUrl != 'https://firebasestorage.googleapis.com/v0/b/jabaraya-test.appspot.com/o/avatars%2Fdefault-avatar-2.png?alt=media') {
                    return response()->json([
                        'status' => false,
                        'statusCode' => 401,
                        'message' => 'Avatar url is not valid',
                    ], 401);
                }

                $avatarUrl = $request->avatarUrl;
                $user->avatar = $avatarUrl;
            } else {
                $avatarUrl = 'https://firebasestorage.googleapis.com/v0/b/jabaraya-test.appspot.com/o/avatars%2Fdefault-avatar-null.png?alt=media';
                $user->avatar = $avatarUrl;
            }

            $user->phone = $request->has('phone') ? $request->phone : $user->phone;
            $user->save();
    
            return response()->json([
                'status' => true,
                'statusCode' => 200,
                'message' => 'Profile successfully updated',
                'data' => [
                    'user' => $user,
                    'social_auth' => ($user->providerAuths()->where('user_id', $user->id)->exists() ? $user->providerAuths()->where('user_id', $user->id)->first()->provider_name : null),
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

    public function changePassword(Request $request)
    {
        try
        {
            $user = Auth::user();
            if (($user->providerAuths()->where('user_id', $user->id)->exists())) {
                return response()->json([
                    'status' => false,
                    'statusCode' => 401,
                    'message' => 'Password cannot be change',
                ], 401);
            }

            $validator = Validator::make($request->all(), [
                'current_password' => 'required',
                'new_password' => 'required|string|min:8',
                'new_password_confirmation' => 'required|string|min:8|same:new_password',
            ]);
    
            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

    
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'status' => false,
                    'statusCode' => 400,
                    'message' => 'Current password is incorrect',
                ], 400);
            }
    
            $user->password = Hash::make($request->new_password);
            $user->save();
    
            return response()->json([
                'status' => true,
                'statusCode' => 200,
                'message' => 'Password changed successfully',
            ], 200);
        } catch(\Throwable $th) {
            return response()->json([
                'status' => false,
                'statusCode' => 500,
                'message' => $th->getMessage()
            ], 500);
        }
    }
}