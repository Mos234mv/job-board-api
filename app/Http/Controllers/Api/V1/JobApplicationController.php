<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreApplicationRequest;
use App\Http\Requests\Api\UpdateApplicationRequest;
use App\Http\Resources\Api\V1\JobApplicationResource;
use App\Models\JobApplication;
use App\Models\JobVacancy;
use App\Models\Resume;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class JobApplicationController extends Controller
{
    // create index function

    public function index(Request $request)
    {

        $user = auth('api')->user();

        $query = JobApplication::with(['jobVacancy', 'user', 'resume']);

        if ($user->role === 'job-seeker') {

            $query->where('user_id', $user->id);
        } elseif ($user->role === 'company-owner') {
            $companyId = $user->company?->id;

            if (! $companyId) {
                $query->whereRaw('1 = 0');

            } else {
                $query->whereHas('jobVacancy', function ($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                });

            }
        }

        $applications = $query->latest()->paginate(10);
        $applications->appends($request->query());

        return ApiResponse::sendResponse(200, 'Applications retrieved successfully', [
            'applications' => JobApplicationResource::collection($applications),
            'meta' => [
                'current_page' => $applications->currentPage(),
                'last_page' => $applications->lastPage(),
                'per_page' => $applications->perPage(),
                'total' => $applications->total(),
                'next_page_url' => $applications->nextPageUrl(),
                'prev_page_url' => $applications->previousPageUrl(),
            ],
        ]);
    }

    public function show($id)
    {
        $user = auth('api')->user();

        $application = JobApplication::with(['jobVacancy', 'user', 'resume'])->find($id);

        if (! $application) {
            return ApiResponse::sendResponse(404, 'Application not found', null);
        }

        if ($user->role === 'job-seeker' && $application->user_id !== $user->id) {
            return ApiResponse::sendResponse(403, 'Forbidden: You can only view your own applications.', null);
        }

        if ($user->role === 'company-owner') {
            $companyId = $user->company?->id;
            if (! $companyId || $application->jobVacancy->company_id !== $companyId) {
                return ApiResponse::sendResponse(403, 'Forbidden: You can only view applications for your own company.', null);
            }
        }

        return ApiResponse::sendResponse(200, 'Application details retrieved successfully', new JobApplicationResource($application));
    }

    public function store(StoreApplicationRequest $request)
    {
        $user = auth('api')->user();

        $vacancy = JobVacancy::find($request->job_vacancy_id);
        $resume = Resume::find($request->resume_id);

        if (! $vacancy || ! $resume) {
            return ApiResponse::sendResponse(404, 'Job Vacancy or Resume not found. Please check the IDs.', null);
        }

        $resumeText = 'Skills: '.$resume->skills."\nExperience: ".$resume->experience."\nSummary: ".$resume->summary;
        $jobText = 'Title: '.$vacancy->title."\nDescription: ".$vacancy->description;

        $response = Http::withToken(config('services.openai.key'))
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an HR expert. Compare the resume with the job vacancy. Return ONLY a valid JSON object with keys: "score" (number from 0 to 10) and "feedback" (short string explaining the match in English).',
                    ],
                    [
                        'role' => 'user',
                        'content' => "Job Vacancy:\n{$jobText}\n\nResume:\n{$resumeText}",
                    ],
                ],
                'response_format' => ['type' => 'json_object'],
            ]);

        if ($response->failed()) {
            return ApiResponse::sendResponse(500, 'Failed to evaluate application with AI.', $response->json());
        }

        $aiData = json_decode($response->json()['choices'][0]['message']['content'] ?? '{}', true);
        $score = $aiData['score'] ?? 0;
        $feedback = $aiData['feedback'] ?? 'Could not generate feedback.';

        $application = JobApplication::create([
            'user_id' => $user->id,
            'job_vacancy_id' => $request->job_vacancy_id,
            'resume_id' => $request->resume_id,
            'status' => 'pending',
            'aiGeneratedScore' => $score,
            'aiGeneratedFeedback' => $feedback,
        ]);

        $application->load(['jobVacancy', 'user', 'resume']);

        return ApiResponse::sendResponse(201, 'Application submitted and evaluated by AI successfully', new JobApplicationResource($application));
    }

    public function update(UpdateApplicationRequest $request, $id)
    {
        $user = auth('api')->user();

        $application = JobApplication::with('jobVacancy')->find($id);

        if (! $application) {
            return ApiResponse::sendResponse(404, 'Application not found', null);
        }

        //
        if ($user->role === 'company-owner') {
            $companyId = $user->company?->id;

            if (! $companyId || $application->jobVacancy->company_id !== $companyId) {
                return ApiResponse::sendResponse(403, 'Forbidden: You can only update applications for your own company.', null);
            }
        }

        $application->update([
            'status' => $request->status,
        ]);

        $application->load(['jobVacancy', 'user', 'resume']);

        return ApiResponse::sendResponse(200, 'Application status updated successfully', new JobApplicationResource($application));
    }

    public function destroy($id)
    {
        $user = auth('api')->user();
        $application = JobApplication::find($id);

        if (! $application) {
            return ApiResponse::sendResponse(404, 'Application not found', null);
        }

        if ($user->role === 'job-seeker' && $application->user_id !== $user->id) {
            return ApiResponse::sendResponse(403, 'Forbidden: You can only withdraw your own application.', null);
        }

        $application->delete();

        return ApiResponse::sendResponse(200, 'Application withdrawn/deleted successfully', null);
    }
}
