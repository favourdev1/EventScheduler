<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Responses\ApiResponse;
use App\Models\EventCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EventCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware(AdminMiddleware::class)->except(['index', 'show']);
    }

    public function index()
    {
        try {
            $categories = EventCategory::withCount('events')->get();
            return ApiResponse::success($categories, 'Categories retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::error(null, 'Failed to retrieve categories');
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:event_categories',
                'description' => 'nullable|string'
            ]);

            $category = EventCategory::create($validated);
            return ApiResponse::successCreated($category, 'Category created successfully');
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        } catch (\Exception $e) {
            return ApiResponse::error(null, 'Failed to create category');
        }
    }

    public function show(EventCategory $category)
    {
        try {
            $category->load(['events' => function($query) {
                $query->whereNull('deleted_at')
                      ->where('status', '!=', 'cancelled');
            }]);

            return ApiResponse::success($category, 'Category details retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::error(null, 'Failed to retrieve category details');
        }
    }

    public function update(Request $request, EventCategory $category)
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255|unique:event_categories,name,' . $category->id,
                'description' => 'nullable|string'
            ]);

            $category->update($validated);
            return ApiResponse::success($category, 'Category updated successfully');
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        } catch (\Exception $e) {
            return ApiResponse::error(null, 'Failed to update category');
        }
    }

    public function destroy(EventCategory $category)
    {
        try {
            $category->delete();
            return ApiResponse::success(null, 'Category deleted successfully');
        } catch (\Exception $e) {
            return ApiResponse::error(null, 'Failed to delete category');
        }
    }
}
