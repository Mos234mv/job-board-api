<?php

namespace App\Http\Requests\Api;

use App\Helpers\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreVacancyRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'location' => ['required', 'string', 'max:255'],
            'salary' => ['required', 'numeric', 'min:0'],
            'type' => ['required', 'in:Full-time,Contract,Remote,Hybrid'],
            'category_id' => ['required', 'uuid', 'exists:job_categories,id'],
            'company_id' => ['nullable', 'uuid', 'exists:companies,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'Job Title',
            'description' => 'Job Description',
            'location' => 'Job Location',
            'salary' => 'Salary',
            'type' => 'Job Type',
            'category_id' => 'Category',
            'company_id' => 'Company',
        ];
    }
}
