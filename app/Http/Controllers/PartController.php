<?php
// app/Http/Controllers/PartController.php
namespace App\Http\Controllers;

use App\Models\Part;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class PartController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Part::query();

        // Search by name
        if ($search = $request->query('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        // Filter by category
        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        $parts = $query->get();
        return response()->json($parts);
    }

    public function show($id): JsonResponse
    {
        $part = Part::findOrFail($id);
        return response()->json($part);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'rating' => 'nullable|numeric|min:0|max:5',
            'review_count' => 'nullable|integer|min:0',
            'description' => 'required|string',
            'features' => 'nullable|array',
            'specifications' => 'nullable|array',
            'images' => 'nullable|array',
            'compatibility' => 'nullable|array',
            'image' => 'nullable|string',
        ]);

        $part = Part::create($validated);
        return response()->json($part, 201);
    }

    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $path = $request->file('image')->store('public/images');
        $url = Storage::url($path);

        return response()->json(['url' => $url], 200);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $part = Part::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'category' => 'sometimes|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'rating' => 'nullable|numeric|min:0|max:5',
            'review_count' => 'nullable|integer|min:0',
            'description' => 'sometimes|string',
            'features' => 'nullable|array',
            'specifications' => 'nullable|array',
            'images' => 'nullable|array',
            'compatibility' => 'nullable|array',
            'image' => 'nullable|string',
        ]);

        $part->update($validated);
        return response()->json($part);
    }

    public function destroy($id): JsonResponse
    {
        $part = Part::findOrFail($id);
        $part->delete();
        return response()->json(null, 204);
    }
}
