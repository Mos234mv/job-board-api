<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\JobApplication;
use App\Models\JobVacancy;
use App\Models\Resume;
use App\Models\User;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function adminStats()
    {

        $activeUsersToday = User::where('last_login_at', '>=', Carbon::now()->subDay())->count();

        $topVacancies = JobVacancy::withCount('jobApplications')
            ->orderBy('viewCount', 'desc')
            ->take(5)
            ->get()
            ->map(function ($vacancy) {
                return [
                    'title' => $vacancy->title,
                    'views' => $vacancy->viewCount,
                    'applications' => $vacancy->job_applications_count,
                ];
            });

        $stats = [
            'active_users_today' => $activeUsersToday,
            'total_users' => User::count(),
            'total_companies' => Company::count(),
            'total_vacancies' => JobVacancy::count(),
            'total_applications' => JobApplication::count(),
            'total_resumes' => Resume::count(),

            'top_vacancies_stats' => $topVacancies,
        ];

        return ApiResponse::sendResponse(200, 'Admin statistics retrieved successfully', $stats);
    }

    public function companyStats()
    {
        $companyId = auth('api')->user()->company?->id;

        if (! $companyId) {
            return ApiResponse::sendResponse(404, 'You do not have a registered company.', null);
        }

        $companyVacanciesStats = JobVacancy::where('company_id', $companyId)
            ->withCount('jobApplications')
            ->get()
            ->map(function ($vacancy) {
                return [
                    'id' => $vacancy->id,
                    'title' => $vacancy->title,
                    'views' => $vacancy->viewCount,
                    'applications' => $vacancy->job_applications_count,
                ];
            });

        $applicationsQuery = JobApplication::whereHas('jobVacancy', function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        });

        $stats = [
            'total_vacancies' => JobVacancy::where('company_id', $companyId)->count(),
            'total_views' => JobVacancy::where('company_id', $companyId)->sum('viewCount'),
            'total_applications' => $applicationsQuery->count(),

            'pending_applications' => (clone $applicationsQuery)->where('status', 'pending')->count(),
            'accepted_applications' => (clone $applicationsQuery)->where('status', 'accepted')->count(),
            'rejected_applications' => (clone $applicationsQuery)->where('status', 'rejected')->count(),

            'vacancies_stats' => $companyVacanciesStats,
        ];

        return ApiResponse::sendResponse(200, 'Company statistics retrieved successfully', $stats);
    }
}
