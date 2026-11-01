<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'address' => $this->address,
            'industry' => $this->industry,
            'website' => $this->website,
            'owner_id' => $this->owner_id,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),

            // بيانات صاحب الشركة (تظهر بس لو استخدمنا with('owner') في الكنترولر)
            'owner' => $this->whenLoaded('owner', function () {
                return [
                    'id' => $this->owner->id,
                    'name' => $this->owner->name,
                    'email' => $this->owner->email,
                ];
            }),

            // الوظائف الشاغرة (تظهر بس لو استخدمنا with('jobVacancies') في الكنترولر زي الـ Endpoint رقم 13)
            'job_vacancies' => JobVacancyResource::collection($this->whenLoaded('jobVacancies')),
        ];
    }
}
