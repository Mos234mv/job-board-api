<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\JobApplication;
use App\Models\JobCategories;
use App\Models\JobVacancy;
use App\Models\Resume;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        // seed the root admin
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('12345678'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        $jobdata = json_decode(file_get_contents(database_path('data/job_data.json')), true);
        $jobApplications = json_decode(file_get_contents(database_path('data/job_applications.json')), true);

        foreach ($jobdata['jobCategories'] as $categoryName) {
            JobCategories::firstOrCreate([
                'name' => $categoryName,
            ]);
        }

        // Create Companies
        foreach ($jobdata['companies'] as $company) {
            // Create company owner
            $companyOwner = User::firstOrCreate(
                ['email' => fake()->unique()->safeEmail()],
                [
                    'name' => fake()->name(),
                    'password' => Hash::make('12345678'),
                    'role' => 'company-owner',
                    'email_verified_at' => now(),
                ]);

            Company::firstOrCreate([
                'name' => $company['name'],
            ], [
                'address' => $company['address'],
                'industry' => $company['industry'],
                'website' => $company['website'],
                'owner_id' => $companyOwner->id,
            ]);
        }

        // Create Job Vacancies
        foreach ($jobdata['jobVacancies'] as $job) {
            // Get the created company
            $company = Company::where('name', $job['company'])->firstOrFail();

            // Get the created job category
            $jobCategory = JobCategories::where('name', $job['category'])->firstOrFail();

            JobVacancy::firstOrCreate([
                'title' => $job['title'],
                'company_id' => $company->id,
            ], [
                'description' => $job['description'],
                'location' => $job['location'],
                'type' => $job['type'],
                'salary' => $job['salary'],
                'category_id' => $jobCategory->id,

            ]);
        }

        // Create Job Applications
        foreach ($jobApplications['jobApplications'] as $application) {
            // Get random job vacancy
            $jobVacancy = JobVacancy::inRandomOrder()->first();

            // Create applicant (job-seeker)
            $applicant = User::firstOrCreate([
                'email' => fake()->unique()->safeEmail(),
            ], [
                'name' => fake()->name(),
                'password' => Hash::make('12345678'),
                'role' => 'job-seeker',
                'email_verified_at' => now(),
            ]);

            // Create resume
            $resume = Resume::create([
                'user_id' => $applicant->id,
                'fileName' => $application['resume']['filename'],
                'fileUrl' => $application['resume']['fileUri'],
                'contactDetails' => $application['resume']['contactDetails'],
                'summary' => $application['resume']['summary'],
                'skills' => $application['resume']['skills'],
                'experience' => $application['resume']['experience'],
                'education' => $application['resume']['education'],
            ]);

            // Create job application
            JobApplication::create([
                'job_vacancy_id' => $jobVacancy->id,
                'user_id' => $applicant->id,
                'resume_id' => $resume->id,
                'status' => $application['status'],
                'aiGeneratedScore' => $application['aiGeneratedScore'],
                'aiGeneratedFeedback' => $application['aiGeneratedFeedback'],
            ]);
        }
    }
}
