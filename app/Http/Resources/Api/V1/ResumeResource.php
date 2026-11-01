<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResumeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fileName' => $this->fileName,
            'fileUrl' => $this->fileUrl,
            'contactDetails' => $this->contactDetails,
            'education' => $this->education,
            'summary' => $this->summary,
            'experience' => $this->experience,
            'skills' => $this->skills,
            'user_id' => $this->user_id,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),

            // بنجيب بيانات صاحب السيرة الذاتية (لو طلبناها في الكنترولر)
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),
        ];
    }
}
