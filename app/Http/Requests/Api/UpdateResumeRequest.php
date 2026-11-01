<?php

namespace App\Http\Requests\Api;

use App\Helpers\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateResumeRequest extends FormRequest
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
            'fileName' => ['sometimes', 'required', 'string', 'max:255'],
            'fileUrl' => ['sometimes', 'required', 'string'],
            'contactDetails' => ['sometimes', 'required', 'string'],
            'education' => ['sometimes', 'required', 'string'],
            'summary' => ['sometimes', 'required', 'string'],
            'experience' => ['sometimes', 'required', 'string'],
            'skills' => ['sometimes', 'required', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'fileName' => 'File Name',
            'fileUrl' => 'File URL',
            'contactDetails' => 'Contact Details',
            'education' => 'Education',
            'summary' => 'Summary',
            'experience' => 'Experience',
            'skills' => 'Skills',
        ];
    }
}
