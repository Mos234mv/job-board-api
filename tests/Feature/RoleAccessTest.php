<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth; // ضفنا دي

class RoleAccessTest extends TestCase
{
    use RefreshDatabase; // دي بتخلي لارافيل يعمل داتابيز فاضية في كل اختبار

    public function test_job_seeker_cannot_create_category()
    {
        $seeker = User::create([
            'name' => 'Test Seeker',
            'email' => 'seeker@test.com',
            'password' => 'password123',
            'role' => 'job-seeker'
        ]);

        // استخدمنا JWTAuth هنا
        $token = JWTAuth::fromUser($seeker);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])->postJson('/api/v1/categories', [
            'name' => 'Test Category'
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_create_category()
    {
        $admin = User::create([
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'password' => 'password123',
            'role' => 'admin'
        ]);

        // استخدمنا JWTAuth هنا كمان
        $token = JWTAuth::fromUser($admin);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])->postJson('/api/v1/categories', [
            'name' => 'New Category'
        ]);

        $response->assertStatus(201);
    }
}