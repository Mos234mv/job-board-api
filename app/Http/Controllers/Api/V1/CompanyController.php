<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreCompanyRequest;
use App\Http\Requests\Api\UpdateCompanyRequest;
use App\Http\Resources\Api\V1\CompanyResource;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CompanyController extends Controller
{
    public function index()
    {
        // show all companies to admin and job seeker but company owner can only see his company
        $user = auth('api')->user();
        $query = Company::query();
        if ($user->role === 'company-owner') {
            $query->where('owner_id', $user->id);
        }

        $companies = $query->with('owner')->latest()->get();

        return ApiResponse::sendResponse(
            200,
            'Companies retrieved successfully',
            CompanyResource::collection($companies)

        );
    }

    // View details for a specific company, along with a list of its posted job vacancies.

    public function show($id)
    {
        $company = Company::with('jobVacancies')->find($id);
        if (! $company) {
            return ApiResponse::sendResponse(404, 'Company not found', null);
        }

        return ApiResponse::sendResponse(200, 'Company details retrieved successfully', new CompanyResource($company));
    }

    // The company owner then creates the company and links it to their own `owner_id`.

    public function store(StoreCompanyRequest $request)
    {
        $company = DB::transaction(function () use ($request) {

            // 1. إنشاء صاحب الشركة (لاحظ الـ owner_)
            $owner = User::create([
                'name' => $request->owner_name,
                'email' => $request->owner_email,
                'password' => $request->owner_password,
                'role' => 'company-owner',
            ]);

            // 2. إنشاء الشركة وربطها بالـ owner_id
            return Company::create([
                'name' => $request->name,
                'address' => $request->address,
                'industry' => $request->industry,
                'website' => $request->website,
                'owner_id' => $owner->id,
            ]);
        });

        // تحميل بيانات صاحب الشركة عشان تظهر في الـ Response
        $company->load('owner');

        return ApiResponse::sendResponse(201, 'Company created successfully', new CompanyResource($company));
    }

    // The company: May only modify the data of its own company.

    public function update(UpdateCompanyRequest $request, $id)
    {
        $user = auth('api')->user();
        $company = Company::find($id);

        // 1. التأكد إن الشركة موجودة
        if (! $company) {
            return ApiResponse::sendResponse(404, 'Company not found', null);
        }

        // 2. التأكد من الصلاحيات (قبل أي تعديل)
        if ($user->role === 'company-owner' && $company->owner_id !== $user->id) {
            return ApiResponse::sendResponse(403, 'Forbidden: You can only modify your own company data.', null);
        }

        // 3. تحديث البيانات بالحقول اللي جات في الـ Request بس
        $company->update($request->validated());

        // 4. تحميل بيانات المالك وإرجاع الرد
        $company->load('owner');

        return ApiResponse::sendResponse(200, 'Company updated successfully', new CompanyResource($company));
    }

    // delete company

    public function destroy($id)
    {

        $company = Company::find($id);
        if (! $company) {
            return ApiResponse::sendResponse(404, 'Company not found', null);
        }

        $company->delete();

        return ApiResponse::sendResponse(200, 'Company deleted successfully', null);

    }
}
