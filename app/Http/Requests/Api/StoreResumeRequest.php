<?php

namespace App\Http\Requests\Api;

use App\Helpers\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreResumeRequest extends FormRequest
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
            // الملف إجباري، ولازم يكون PDF، وحجمه مش أكبر من 5 ميجا (5120 كيلوبايت)
            'resume_file' => ['required', 'file', 'mimes:pdf', 'max:5120'],

            // الحقول دي بقت اختيارية لأن الـ AI هو اللي هيستخرجها ويحطها
            'contactDetails' => ['nullable', 'string'],
            'education' => ['nullable', 'string'],
            'summary' => ['nullable', 'string'],
            'experience' => ['nullable', 'string'],
            'skills' => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'resume_file' => 'Resume File (PDF)',
            'contactDetails' => 'Contact Details',
            'education' => 'Education',
            'summary' => 'Summary',
            'experience' => 'Experience',
            'skills' => 'Skills',
        ];
    }
}
