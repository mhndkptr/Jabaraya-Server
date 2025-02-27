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
        try {
            $userData = Auth::user();
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
                    'statusCode' => 403,
                    'message' => 'Your current role not authorized',
                ], 403);
            }
        } catch(\Throwable $th) {
            return response()->json([
                'status' => false,
                'statusCode' => 500,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function adminDelete(Request $request, $id)
    {
        try {
            $userData = Auth::user();

            $validator = Validator::make($request->all(), [
                'password' => 'required|string|min:8',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'statusCode' => 422,
                    'message' => 'validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            if (!Hash::check($request->password, $userData->password)) {
                return response()->json([
                    'status' => false,
                    'statusCode' => 422,
                    'message' => 'Password is incorrect',
                ], 422);
            }

            if ($userData->role === 'admin') {
                $selectedUser = User::where('id', $id)->first();

                if (!$selectedUser) {
                    return response()->json([
                        'status' => false,
                        'statusCode' => 404,
                        'message' => 'User not found',
                    ], 404);
                }
                
                if($selectedUser->role === "admin") {
                    return response()->json([
                        'status' => false,
                        'statusCode' => 403,
                        'message' => 'You cannot delete this account',
                    ], 403);
                }

                $selectedUser->delete();
                return response()->json([
                    'status' => true,
                    'statusCode' => 200,
                    'message' => 'User deleted successfully',
                ], 200);
            } else {
                return response()->json([
                    'status' => false,
                    'statusCode' => 403,
                    'message' => 'Your current role not authorized',
                ], 403);
            }
        } catch(\Throwable $th) {
            return response()->json([
                'status' => false,
                'statusCode' => 500,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function delete(Request $request)
    {
        try {
            $userData = Auth::user();

            $validator = Validator::make($request->all(), [
                'password' => 'required|string|min:8',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'statusCode' => 422,
                    'message' => 'validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            if (!Hash::check($request->password, $userData->password)) {
                return response()->json([
                    'status' => false,
                    'statusCode' => 422,
                    'message' => 'Password is incorrect',
                ], 422);
            }
            
            if($userData->role === "admin") {
                return response()->json([
                    'status' => false,
                    'statusCode' => 403,
                    'message' => 'You cannot delete this account',
                ], 403);
            }
                
            $userData->delete();
            return response()->json([
                'status' => true,
                'statusCode' => 200,
                'message' => 'User deleted successfully',
            ], 200);
        } catch(\Throwable $th) {
            return response()->json([
                'status' => false,
                'statusCode' => 500,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function show()
    {
        try {
            $userData = Auth::user();
            return response()->json([
                'status' => true,
                'statusCode' => 200,
                'message' => 'Profile information',
                'data' => [
                    'user' => $userData,
                    'social_auth' => ($userData->providerAuths()->where('user_id', $userData->id)->exists() ? $userData->providerAuths()->where('user_id', $userData->id)->pluck('provider_name') : null),
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

    public function update(UserUpdateRequest $request)
    {
        try
        {
            $avatarUrl = Auth::user()->avatar;

            $request->user()->fill($request->validated());

            $user = Auth::user();
            if (($user->providerAuths()->where('user_id', $user->id)->exists()) && $request->has('email')) {
                return response()->json([
                    'status' => false,
                    'statusCode' => 422,
                    'message' => 'Email cannot be change',
                ], 422);
            }

            $user->name = $request->has('name') ? $request->name : $user->name;
            $user->email = $request->has('email') ? $request->email : $user->email;

            if($request->has('avatarImage')) {
                if ($request->hasFile('avatarImage')) {
                    if ($user->avatar) {
                        $this->firebaseService->deleteFile($avatarUrl);
                    }
                    $fileUrl = $this->firebaseService->uploadFile($request->file('avatarImage'), 'avatars');
                    $user->avatar = $fileUrl;
                }
            } else if ($request->has('avatarUrl')) {
                if ($request->avatarUrl != 'https://firebasestorage.googleapis.com/v0/b/jabaraya-test.appspot.com/o/avatars%2Fdefault-avatar-1.png?alt=media' && $request->avatarUrl != 'https://firebasestorage.googleapis.com/v0/b/jabaraya-test.appspot.com/o/avatars%2Fdefault-avatar-2.png?alt=media' && $request->avatarUrl != 'https://firebasestorage.googleapis.com/v0/b/jabaraya-test.appspot.com/o/avatars%2Fdefault-avatar-null.png?alt=media') {
                    return response()->json([
                        'status' => false,
                        'statusCode' => 422,
                        'message' => 'Avatar url is not valid',
                    ], 422);
                }
                if ($user->avatar) {
                    $this->firebaseService->deleteFile($avatarUrl);
                }
                $avatarUrl = $request->avatarUrl;
                $user->avatar = $avatarUrl;
            } else {
                $user->avatar = $user->avatar;
            }

            $user->phone = $request->has('phone') ? $request->phone : $user->phone;
            $user->save();
    
            return response()->json([
                'status' => true,
                'statusCode' => 200,
                'message' => 'Profile successfully updated',
                'data' => [
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

    public function changePassword(Request $request)
    {
        try
        {
            $user = Auth::user();
            if (($user->providerAuths()->where('user_id', $user->id)->exists()) && $user->password === null) {
                return response()->json([
                    'status' => false,
                    'statusCode' => 422,
                    'message' => 'Password cannot be change',
                ], 422);
            }

            $validator = Validator::make($request->all(), [
                'current_password' => 'required',
                'new_password' => 'required|string|min:8',
                'new_password_confirmation' => 'required|string|min:8|same:new_password',
            ]);
    
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'statusCode' => 422,
                    'message' => 'validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

    
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'status' => false,
                    'statusCode' => 422,
                    'message' => 'Current password is incorrect',
                ], 422);
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
