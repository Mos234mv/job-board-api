<?php

namespace App\Http\Requests\Api;

use App\Helpers\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateVacancyRequest extends FormRequest
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
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string'],
            'location' => ['sometimes', 'required', 'string', 'max:255'],
            'salary' => ['sometimes', 'required', 'numeric', 'min:0'],
            'type' => ['sometimes', 'required', 'in:Full-time,Contract,Remote,Hybrid'],
            'category_id' => ['sometimes', 'required', 'uuid', 'exists:job_categories,id'],
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
        ];
    }
}
