<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ServiceController extends Controller
{
    // Get all services
    public function index()
    {
        $services = Service::all();
        return response()->json($services, 200);
    }

    // Store a new service
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $service = new Service();
        $service->title = $request->title;
        $service->description = $request->description;

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('services', 'public');
            $service->image_path = $imagePath;
        }

        $service->save();

        return response()->json($service, 201);
    }

    // Show a single service
    public function show($id)
    {
        $service = Service::findOrFail($id);
        return response()->json($service, 200);
    }

    // Update a service
    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $service = Service::findOrFail($id);
        $service->title = $request->title;
        $service->description = $request->description;

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($service->image_path) {
                Storage::disk('public')->delete($service->image_path);
            }
            $imagePath = $request->file('image')->store('services', 'public');
            $service->image_path = $imagePath;
        }

        $service->save();

        return response()->json($service, 200);
    }

    // Delete a service
    public function destroy($id)
    {
        $service = Service::findOrFail($id);

        if ($service->image_path) {
            Storage::disk('public')->delete($service->image_path);
        }

        $service->delete();

        return response()->json(['message' => 'Service deleted successfully'], 200);
    }
}
