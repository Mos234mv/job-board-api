<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreResumeRequest;
use App\Http\Requests\Api\UpdateResumeRequest;
use App\Http\Resources\Api\V1\ResumeResource;
use App\Models\JobApplication;
use App\Models\Resume;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http; // <-- ضيف دي
use Illuminate\Support\Facades\Storage; // <-- ضيف دي
use Smalot\PdfParser\Parser;

class ResumeController extends Controller
{
    public function index(Request $request)
    {
        $user = auth('api')->user();
        $query = Resume::with('user');

        if ($user->role === 'job-seeker') {
            $query->where('user_id', $user->id);
        }
        $resumes = $query->latest()->paginate(10);
        $resumes->appends($request->query());

        return ApiResponse::sendResponse(200, 'Resumes retrieved successfully', [
            'resumes' => ResumeResource::collection($resumes),
            'meta' => [
                'current_page' => $resumes->currentPage(),
                'last_page' => $resumes->lastPage(),
                'per_page' => $resumes->perPage(),
                'total' => $resumes->total(),
                'next_page_url' => $resumes->nextPageUrl(),
                'prev_page_url' => $resumes->previousPageUrl(),
            ],
        ]);

    }

    // ====================== Show specific  Resume =====================

    public function show($id)
    {
        $user = auth('api')->user();

        $resume = Resume::with('user')->find($id);

        if (! $resume) {
            return ApiResponse::sendResponse(404, 'Resume not found', null);
        }

        if ($user->role === 'job-seeker' && $resume->user_id !== $user->id) {
            return ApiResponse::sendResponse(403, 'Forbidden: You can only view your own resume.', null);
        }

        if ($user->role === 'company-owner') {
            $companyId = $user->company?->id;

            if (! $companyId) {
                return ApiResponse::sendResponse(403, 'Forbidden: You do not have a registered company.', null);
            }

            $hasAppliedToMyCompany = JobApplication::where('resume_id', $resume->id)
                ->whereHas('jobVacancy', function ($query) use ($companyId) {
                    $query->where('company_id', $companyId);
                })->exists();

            if (! $hasAppliedToMyCompany) {
                return ApiResponse::sendResponse(403, 'Forbidden: You can only view resumes of applicants who applied to your company.', null);
            }
        }

        return ApiResponse::sendResponse(200, 'Resume details retrieved successfully', new ResumeResource($resume));
    }

    // ====================== Store Resume =====================
    public function store(StoreResumeRequest $request)
    {
        $user = auth('api')->user();

        // 1. رفع الملف على Laravel Cloud (الـ Disk اللي اسمه cloud)
        $file = $request->file('resume_file');
        $filePath = $file->store('resumes', 'cloud');

        // اللينك المباشر للملف
        $fileUrl = Storage::disk('cloud')->url($filePath);

        // 2. استخراج النص من ملف الـ PDF
        $parser = new Parser;
        $pdf = $parser->parseFile($file->getPathname());
        $text = $pdf->getText();

        // 3. إرسال النص للـ AI (ChatGPT) لاستخراج البيانات
        $response = Http::withToken(config('services.openai.key'))
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini', // موديل سريع ورخيص ومناسب للمهمة دي
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a resume parser. Extract the following info from the text and return ONLY a valid JSON object with these keys: contactDetails, education, summary, experience, skills. Do not add any markdown or extra text.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $text,
                    ],
                ],
                'response_format' => ['type' => 'json_object'],
            ]);

        // لو الـ API رجع إيرور (زي نفاد الرصيد أو مشكلة في الشبكة)
        if ($response->failed()) {
            return ApiResponse::sendResponse(500, 'Failed to analyze resume with AI.', $response->json());
        }

        // 4. استلام البيانات من الـ AI
        $aiData = json_decode($response->json()['choices'][0]['message']['content'] ?? '{}', true);

        // 5. حفظ السيرة الذاتية في الداتابيز
        $resume = Resume::create([
            'user_id' => $user->id,
            'fileName' => $file->getClientOriginalName(),
            'fileUrl' => $fileUrl,
            'contactDetails' => is_array($aiData['contactDetails'] ?? null) ? json_encode($aiData['contactDetails']) : ($aiData['contactDetails'] ?? null),
            'education' => is_array($aiData['education'] ?? null) ? json_encode($aiData['education']) : ($aiData['education'] ?? null),
            'summary' => is_array($aiData['summary'] ?? null) ? json_encode($aiData['summary']) : ($aiData['summary'] ?? null),
            'experience' => is_array($aiData['experience'] ?? null) ? json_encode($aiData['experience']) : ($aiData['experience'] ?? null),
            'skills' => is_array($aiData['skills'] ?? null) ? json_encode($aiData['skills']) : ($aiData['skills'] ?? null),
        ]);

        $resume->load('user');

        return ApiResponse::sendResponse(201, 'Resume uploaded and parsed by AI successfully', new ResumeResource($resume));
    }

    public function update(UpdateResumeRequest $request, $id)
    {
        $user = auth('api')->user();

        $resume = Resume::find($id);
        if (! $resume) {
            return ApiResponse::sendResponse(404, 'Resume not found', null);
        }

        if ($resume->user_id != $user->id) {
            return ApiResponse::sendResponse(403, 'You are not authorized to update this resume', null);
        }
        $resume->update($request->validated());

        $resume->load('user');

        return ApiResponse::sendResponse(200, 'Resume updated successfully', new ResumeResource($resume));
    }

    // Destroy a resume

    public function destroy($id)
    {
        $user = auth('api')->user();
        $resume = Resume::find($id);
        if (! $resume) {
            return ApiResponse::sendResponse(404, 'Resume not found', null);
        }

        if ($user->role === 'job-seeker' && $resume->user_id !== $user->id) {
            return ApiResponse::sendResponse(403, 'Forbidden: You can only delete your own resume.', null);
        }
        $resume->delete();

        return ApiResponse::sendResponse(200, 'Resume deleted successfully', null);

    }
}
