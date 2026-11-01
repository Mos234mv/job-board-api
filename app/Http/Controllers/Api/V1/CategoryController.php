<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CategoryRequest;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Models\JobCategories;

class CategoryController extends Controller
{
    public function index()
    {
        // Get all categories from the database
        $categories = JobCategories::latest()->get();

        // Return the categories as a JSON response
        return ApiResponse::sendResponse(
            200,
            'Categories retrieved successfully',
            CategoryResource::collection($categories)
        );
    }

    // Display a list of related job vacancies (jobVacancies) with a specific classification.

    public function show($id)
    {
        // Retrieve the category by its ID

        $category = JobCategories::with('jobVacancies')->find($id);

        if (! $category) {
            return ApiResponse::sendResponse(404, 'Category not found', null);
        }

        return ApiResponse::sendResponse(
            200,
            'Category retrieved successfully',
            new CategoryResource($category)
        );

    }

    public function store(CategoryRequest $request)
    {
        $category = JobCategories::create([
            'name' => $request->name,
        ]);

        return ApiResponse::sendResponse(201, 'Category created successfully', new CategoryResource($category));
    }

    // update a category by its ID

    public function update(CategoryRequest $request, $id)
    {

        $category = JobCategories::find($id);

        if (! $category) {
            return ApiResponse::sendResponse(404, 'Category not found', null);
        }

        $category->update([
            'name' => $request->name,
        ]);

        return ApiResponse::sendResponse(200, 'Category updated successfully', new CategoryResource($category));

    }

    public function destroy($id)
    {
        $category = JobCategories::find($id);

        if (! $category) {
            return ApiResponse::sendResponse(404, 'Category not found', null);
        }

        $category->delete();

        return ApiResponse::sendResponse(204, 'Category deleted successfully', null);

    }
}
