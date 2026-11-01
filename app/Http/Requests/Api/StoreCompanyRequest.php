<?php

namespace App\Http\Requests\Api;

use App\Helpers\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreCompanyRequest extends FormRequest
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
        return [
            // بيانات صاحب الشركة (عشان نعمل له User جديد)
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'owner_password' => ['required', 'string', 'min:8'],

            // بيانات الشركة
            'name' => ['required', 'string', 'max:255', 'unique:companies,name'],
            'address' => ['required', 'string', 'max:255'],
            'industry' => ['required', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'owner_name' => 'Owner Name',
            'owner_email' => 'Owner Email',
            'owner_password' => 'Owner Password',
            'name' => 'Company Name',
            'address' => 'Company Address',
            'industry' => 'Company Industry',
            'website' => 'Company Website',
        ];
    }
}
