<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreVacancyRequest;
use App\Http\Requests\Api\UpdateVacancyRequest;
use App\Http\Resources\Api\V1\JobVacancyResource;
use App\Models\JobVacancy;
use Illuminate\Http\Request;

class JobVacancyController extends Controller
{
    public function index(Request $request)
    {
        $user = auth('api')->user();
        $query = JobVacancy::with(['company', 'category']);

        if ($user->role === 'company-owner') {
            $companyId = $user->company?->id;
            if ($companyId) {
                $query->where('company_id', $companyId);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $vacancies = $query->latest()->paginate(10);

        return ApiResponse::sendResponse(200, 'Vacancies retrieved successfully', [
            'vacancies' => JobVacancyResource::collection($vacancies),
            'meta' => [
                'current_page' => $vacancies->currentPage(),
                'last_page' => $vacancies->lastPage(),
                'per_page' => $vacancies->perPage(),
                'total' => $vacancies->total(),
                // روابط التنقل
                'next_page_url' => $vacancies->nextPageUrl(),
                'prev_page_url' => $vacancies->previousPageUrl(),
            ],
        ]);
    }

    // search  vacancies

    public function search(Request $request)
    {
        $user = auth('api')->user();

        $query = JobVacancy::with(['company', 'category']);
        if ($user->role === 'company-owner') {
            $companyId = $user->company?->id;
            if ($companyId) {
                $query->where('company_id', $companyId);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $query->when($request->filled('title'), function ($q) use ($request) {
            $q->where('title', 'like', '%'.$request->title.'%');
        });

        $query->when($request->filled('location'), function ($q) use ($request) {
            $q->where('location', 'like', '%'.$request->location.'%');
        });

        $query->when($request->filled('type'), function ($q) use ($request) {
            $q->where('type', $request->type);
        });

        $query->when($request->filled('category_id'), function ($q) use ($request) {
            $q->where('category_id', $request->category_id);
        });

        $query->when($request->filled('salary'), function ($q) use ($request) {
            $q->where('salary', '>=', $request->salary);
        });

        $vacancies = $query->latest()->paginate(5);

        return ApiResponse::sendResponse(200, 'Vacancies retrieved successfully', [
            'vacancies' => JobVacancyResource::collection($vacancies),
            'meta' => [
                'current_page' => $vacancies->currentPage(),
                'last_page' => $vacancies->lastPage(),
                'per_page' => $vacancies->perPage(),
                'total' => $vacancies->total(),
                // روابط التنقل
                'next_page_url' => $vacancies->nextPageUrl(),
                'prev_page_url' => $vacancies->previousPageUrl(),
            ],
        ]);

    }

    public function show($id)
    {

        $vacancy = JobVacancy::with(['company', 'category'])->find($id);

        if (! $vacancy) {
            return ApiResponse::sendResponse(404, 'Vacancy not found', null);
        }

        $vacancy->increment('viewCount');

        return ApiResponse::sendResponse(200, 'Vacancy retrieved successfully', new JobVacancyResource($vacancy));

    }

    // create new job vacancy

    public function store(StoreVacancyRequest $request)
    {

        $user = auth('api')->user();
        $data = $request->validated();
        if ($user->role == 'company-owner') {
            $company = $user->company;
            if (! $company) {
                return ApiResponse::sendResponse(404, 'Company not found', null);

            }

            $data['company_id'] = $company->id;

        } else {
            if (empty($data['company_id'])) {
                return ApiResponse::sendResponse(422, 'Validation Errors', ['company_id' => ['The company id field is required for admin.']]);
            }

        }
        $vacancy = JobVacancy::create($data);

        $vacancy->load(['company', 'category']);

        return ApiResponse::sendResponse(201, 'Vacancy created successfully', new JobVacancyResource($vacancy));

    }

    // update vacancy
    public function update(UpdateVacancyRequest $request, $id)
    {
        $user = auth('api')->user();
        $vacancy = JobVacancy::find($id);

        if (! $vacancy) {
            return ApiResponse::sendResponse(404, 'Vacancy not found', null);
        }

        if ($user->role === 'company-owner' && $vacancy->company_id !== $user->company?->id) {
            return ApiResponse::sendResponse(403, 'Forbidden: You can only update your own company vacancies', null);
        }

        $vacancy->update($request->validated());
        $vacancy->load(['company', 'category']);

        return ApiResponse::sendResponse(200, 'Vacancy updated successfully', new JobVacancyResource($vacancy));
    }

    // delete vacancy

    public function destroy($id)
    {

        $user = auth('api')->user();
        $vacancy = JobVacancy::find($id);
        if (! $vacancy) {
            return ApiResponse::sendResponse(404, 'Vacancy not found', null);
        }

        if ($user->role === 'company-owner' && $vacancy->company_id !== $user->company?->id) {

            return ApiResponse::sendResponse(403, 'Forbidden: You can only delete your own company vacancies', null);

        }

        $vacancy->delete();

        return ApiResponse::sendResponse(200, 'Vacancy deleted successfully', null);

    }
}
