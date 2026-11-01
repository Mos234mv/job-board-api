<?php

namespace App\Http\Requests\Api;

use App\Helpers\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateCompanyRequest extends FormRequest
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
        // بنجيب الـ ID بتاع الشركة اللي بنعدلها عشان نتجاهله في التحقق من الاسم
        $companyId = $this->route('id');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('companies', 'name')->ignore($companyId)],
            'address' => ['sometimes', 'required', 'string', 'max:255'],
            'industry' => ['sometimes', 'required', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Company Name',
            'address' => 'Company Address',
            'industry' => 'Company Industry',
            'website' => 'Company Website',
        ];
    }
}
