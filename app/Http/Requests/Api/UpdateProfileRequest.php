<?php

namespace App\Http\Requests\Api;

use App\Helpers\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator): void
    {
        if ($this->is('api/*')) {
            throw new HttpResponseException(
                ApiResponse::sendResponse(422, 'Validation Errors', $validator->errors())
            );
        }
    }

    public function rules(): array
    {
        $user = auth('api')->user();
        $userId = $user->id;

        // القواعد الأساسية للإيميل (متاحة للباحث عن عمل والأدمن)
        $emailRules = [
            'sometimes', 
            'required', 
            'string', 
            'email', 
            'max:255', 
            Rule::unique('users')->ignore($userId)
        ];

        // لو المستخدم صاحب شركة، بنمنعه من تغيير الإيميل
        if ($user->role === 'company-owner') {
            $emailRules = [
                'sometimes',
                function ($attribute, $value, $fail) use ($user) {
                    // لو الإيميل اللي باعته مختلف عن إيميله الحالي، ارفض
                    if ($value !== $user->email) {
                        $fail('Company owners cannot change their email address. Please contact the admin.');
                    }
                },
            ];
        }

        return [
            'name'     => ['sometimes', 'required', 'string', 'max:255'],
            'email'    => $emailRules,
            'password' => ['nullable', 'string', 'min:8'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name'     => 'Full Name',
            'email'    => 'Email Address',
            'password' => 'Password',
        ];
    }
}