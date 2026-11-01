<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobVacancyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'location' => $this->location,
            'type' => $this->type,
            'salary' => $this->salary,
            'viewCount' => $this->viewCount,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),

            // بيانات الشركة (تظهر بس لو استخدمنا with('company') في الكنترولر)
            'company' => $this->whenLoaded('company', function () {
                return [
                    'id' => $this->company->id,
                    'name' => $this->company->name,
                    'website' => $this->company->website,
                ];
            }),

            // بيانات التصنيف (تظهر بس لو استخدمنا with('category') في الكنترولر)
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                ];
            }),
        ];
    }
}
