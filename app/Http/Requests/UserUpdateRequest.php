<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UserUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'lowercase', 'email', 'email:dns', 'max:255', Rule::unique(User::class)->ignore($this->user()->id)],
            'phone' => ['sometimes', 'string', 'min:8'],
            'avatarImage' => ['sometimes', 'image', 'max:1024', "mimes:jpeg,png,jpg"],
            'avatarUrl' => ['sometimes', 'active_url'],
        ];
    }

    public function failedValidation($validator)
    {
        throw new HttpResponseException(
            response()->json([
                'status' => false,
                'statusCode' => 422,
                'message' => 'validation error',
                'errors' => $validator->errors()
            ], 422)
        );
    }
}
