<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $categories = Category::where('business_id', $request->user()->business_id)
            ->withCount('products')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return CategoryResource::collection($categories);
    }

    public function store(StoreCategoryRequest $request): CategoryResource
    {
        $category = Category::create([
            'active'     => true,
            'sort_order' => 0,
            ...$request->validated(),
            'business_id' => $request->user()->business_id,
        ]);

        return new CategoryResource($category);
    }

    public function show(Request $request, Category $category): CategoryResource
    {
        $this->assertBelongsToBusiness($request, $category);

        return new CategoryResource($category->loadCount('products'));
    }

    public function update(StoreCategoryRequest $request, Category $category): CategoryResource
    {
        $this->assertBelongsToBusiness($request, $category);

        $category->update($request->validated());

        return new CategoryResource($category);
    }

    public function destroy(Request $request, Category $category): JsonResponse
    {
        $this->assertBelongsToBusiness($request, $category);

        if ($category->products()->exists()) {
            return response()->json([
                'message' => 'No puedes eliminar una categoría que tiene productos.',
            ], 422);
        }

        $category->delete();

        return response()->json(['message' => 'Categoría eliminada.']);
    }

    private function assertBelongsToBusiness(Request $request, Category $category): void
    {
        abort_if($category->business_id !== $request->user()->business_id, 404);
    }
}
