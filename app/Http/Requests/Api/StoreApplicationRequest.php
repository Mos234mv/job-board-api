<?php

namespace App\Http\Requests\Api;

use App\Helpers\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreApplicationRequest extends FormRequest
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
        $userId = auth('api')->id();

        return [
            'job_vacancy_id' => [
                'required',
                'uuid',
                'exists:job_vacancies,id',

                Rule::unique('job_applications', 'job_vacancy_id')->where(function ($query) use ($userId) {
                    return $query->where('user_id', $userId);
                }),
            ],
            'resume_id' => [
                'required',
                'uuid',
                'exists:resumes,id',

                Rule::exists('resumes', 'id')->where(function ($query) use ($userId) {
                    return $query->where('user_id', $userId);
                }),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'job_vacancy_id' => 'Job Vacancy',
            'resume_id' => 'Resume',
        ];
    }
}
