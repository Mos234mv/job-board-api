<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CompanyController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\JobApplicationController;
use App\Http\Controllers\Api\V1\JobVacancyController;
use App\Http\Controllers\Api\V1\ResumeController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // ==========================================
    // 1. Auth Module (Public Routes) - Strict Rate Limiting
    // ==========================================
    Route::middleware('throttle:5,1')->group(function () {
        Route::post('/auth/register', [AuthController::class, 'register']);
        Route::post('/auth/login', [AuthController::class, 'login']);
        Route::post('/auth/refresh', [AuthController::class, 'refresh']);
    });

    // ==========================================
    // 2. Auth Module (Protected Routes)
    // ==========================================
    Route::middleware(['auth:api', 'throttle:60,1'])->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']); // البروفايل
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::put('/auth/profile', [AuthController::class, 'updateProfile']);

        // ======================== Categories Routes =====================
        Route::get('/categories', [CategoryController::class, 'index']);
        Route::get('/categories/{id}', [CategoryController::class, 'show']);
        
        // ======================== Companies Routes =====================
        Route::get('/companies', [CompanyController::class, 'index']);
        Route::get('/companies/{id}', [CompanyController::class, 'show']);

        // ======================= Job Vacancies Routes =====================
        Route::get('/vacancies', [JobVacancyController::class, 'index']);
        Route::get('/vacancies/search', [JobVacancyController::class, 'search']);
        Route::get('/vacancies/{id}', [JobVacancyController::class, 'show']);
        
        // ======================== Search Resume =====================
        Route::get('/resumes/{id}', [ResumeController::class, 'show']);

        // ======================== Job Applications Routes =====================
        Route::get('/applications', [JobApplicationController::class, 'index']);
        Route::get('/applications/{id}', [JobApplicationController::class, 'show']);
    });

    // ========================================= Admin Module (Protected Routes) ========================================
    Route::middleware(['auth:api', 'role:admin', 'throttle:60,1'])->group(function () {
        // Categories
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{id}', [CategoryController::class, 'update']);
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

        // Companies
        Route::post('/companies', [CompanyController::class, 'store']);
        Route::delete('/companies/{id}', [CompanyController::class, 'destroy']);

        // Users
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);
        
        // Dashboard
        Route::get('/dashboard/admin', [DashboardController::class, 'adminStats']);
    });

    // ======================================== Admin && Company Owner Module ========================================
    Route::middleware(['auth:api', 'role:admin,company-owner', 'throttle:60,1'])->group(function () {
        Route::put('/companies/{id}', [CompanyController::class, 'update']);

        // Vacancies
        Route::post('/vacancies', [JobVacancyController::class, 'store']);
        Route::put('/vacancies/{id}', [JobVacancyController::class, 'update']);
        Route::delete('/vacancies/{id}', [JobVacancyController::class, 'destroy']);

        // Applications
        Route::put('/applications/{id}', [JobApplicationController::class, 'update']);
    });

    // ======================================== Admin && Job Seeker Module ========================================
    Route::middleware(['auth:api', 'role:admin,job-seeker', 'throttle:60,1'])->group(function () {
        Route::get('/resumes', [ResumeController::class, 'index']);
        Route::delete('/resumes/{id}', [ResumeController::class, 'destroy']);
        Route::delete('/applications/{id}', [JobApplicationController::class, 'destroy']);
    });

    // ======================================== Job Seeker Module ========================================
    Route::middleware(['auth:api', 'role:job-seeker', 'throttle:60,1'])->group(function () {
        Route::post('/resumes', [ResumeController::class, 'store']);
        Route::put('/resumes/{id}', [ResumeController::class, 'update']);
        Route::post('/applications', [JobApplicationController::class, 'store']);
    });

    // ======================================== Company Owner Module ========================================
    Route::middleware(['auth:api', 'role:company-owner', 'throttle:60,1'])->group(function () {
        Route::get('/dashboard/company', [DashboardController::class, 'companyStats']);
    });

});