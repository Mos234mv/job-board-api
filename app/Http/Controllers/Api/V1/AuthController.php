<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Requests\Api\UpdateProfileRequest;
use App\Http\Resources\Api\V1\LoginResource;
use App\Http\Resources\Api\V1\ProfileResource;
use App\Http\Resources\Api\V1\RegisterResource;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth; // استخدمنا الفاسيد بتاع JWT مباشرة

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        // 1. إنشاء المستخدم
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'role' => 'job-seeker',
        ]);

        // 2. توليد الـ JWT Token
        $token = JWTAuth::fromUser($user);
        $expires_in = JWTAuth::factory()->getTTL() * 60;

        // 3. تجهيز البيانات
        $data = [
            'user' => $user,
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $expires_in,
        ];

        // 4. إرجاع الرد باستخدام الـ Resource
        return ApiResponse::sendResponse(
            201,
            'User registered successfully',
            new RegisterResource($data)
        );
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        // استخدام JWTAuth مباشرة
        $token = JWTAuth::attempt($credentials);

        if (! $token) {
            return ApiResponse::sendResponse(401, 'Invalid Email or Password', null);
        }

        $user = JWTAuth::user();

        // تحديث وقت آخر دخول زي ما الـ PDF طلب
        $user->update(['last_login_at' => now()]);

        $data = [
            'user' => $user,
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
        ];

        return ApiResponse::sendResponse(200, 'User Logged In Successfully', new LoginResource($data));
    }

    // دالة me (البروفايل)
    public function me()
    {
        $user = JWTAuth::user();

        return ApiResponse::sendResponse(200, 'Profile fetched successfully', new ProfileResource($user));
    }

    public function logout()
    {
        try {
            // إبطال التوكن الحالي حتى يصبح غير صالح للاستخدام بعد تسجيل الخروج
            JWTAuth::invalidate(JWTAuth::getToken());

            return ApiResponse::sendResponse(200, 'User Logged Out Successfully', null);
        } catch (\Exception $e) {
            return ApiResponse::sendResponse(500, 'Failed to logout, please try again', null);
        }
    }

    public function refresh()
    {
        try {
            // محاولة جلب التوكن وتجديده مباشرة
            $token = JWTAuth::parseToken()->refresh();
        } catch (\Exception $e) {
            return ApiResponse::sendResponse(400, 'Token error: '.$e->getMessage(), null);
        }

        return ApiResponse::sendResponse(200, 'Token Refreshed Successfully', [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
        ]);
    }

    // جوه AuthController.php

    // تأكد إن السطر ده موجود فوق مع باقي الـ use
    // use App\Http\Requests\Api\UpdateProfileRequest;

    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = JWTAuth::user();

        // بنجمع بس الحاجات اللي الـ Request سمح بيها
        $data = $request->only('name', 'email');

        // لو المستخدم باعت باسورد جديد، هنضيفه للبيانات (الموديل هيعمله Hash تلقائياً)
        if ($request->filled('password')) {
            $data['password'] = $request->password;
        }

        // تحديث البيانات
        $user->update($data);

        return ApiResponse::sendResponse(200, 'Profile updated successfully', new ProfileResource($user));
    }
}
