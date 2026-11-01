<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {

        $query = User::query();

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->latest()->paginate(15);
        $users->appends($request->query());

        return ApiResponse::sendResponse(200, 'Users retrieved successfully', [
            'users' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'next_page_url' => $users->nextPageUrl(),
                'prev_page_url' => $users->previousPageUrl(),
            ],
        ]);
    }

    public function show(Request $request, $id)
    {
        $user = User::find($id);

        if (! $user) {
            return ApiResponse::sendResponse(404, 'User not found', null);
        }

        return ApiResponse::sendResponse(200, 'User details retrieved successfully', $user);
    }

    public function destroy(Request $request, $id)
    {
        $currentUser = auth('api')->user();
        $user = User::find($id);

        if (! $user) {
            return ApiResponse::sendResponse(404, 'User not found', null);
        }

        if ($currentUser->id === $user->id) {
            return ApiResponse::sendResponse(403, 'Forbidden: You cannot delete your own account.', null);
        }

        $user->delete();

        return ApiResponse::sendResponse(200, 'User account suspended/deleted successfully', null);
    }
}
