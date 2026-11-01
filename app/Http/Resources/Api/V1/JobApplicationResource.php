<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'aiGeneratedScore' => $this->aiGeneratedScore,
            'aiGeneratedFeedback' => $this->aiGeneratedFeedback,
            'resume_id' => $this->resume_id,
            'user_id' => $this->user_id,
            'job_vacancy_id' => $this->job_vacancy_id,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),

            'job_vacancy' => $this->whenLoaded('jobVacancy', function () {
                return [
                    'id' => $this->jobVacancy->id,
                    'title' => $this->jobVacancy->title,
                    'location' => $this->jobVacancy->location,
                ];
            }),

            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),

            'resume' => $this->whenLoaded('resume', function () {
                return [
                    'id' => $this->resume->id,
                    'fileName' => $this->resume->fileName,
                    'fileUrl' => $this->resume->fileUrl,

                    'skills' => $this->resume->skills,
                ];
            }),
        ];
    }
}
