<?php

namespace App\Http\Requests\Api;

use App\Helpers\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
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
        // لو إحنا في دالة التعديل (PUT)، بنتجاهل الـ ID الحالي في التحقق من التكرار
        $categoryId = $this->route('category');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('job_categories', 'name')->ignore($categoryId),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Category Name',
        ];
    }
}
