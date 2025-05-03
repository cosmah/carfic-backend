<?php

namespace App\Http\Controllers;

use App\Models\Harvestor;
use Illuminate\Http\Request;

class HarvestorController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'harvestor' => 'required|array',
            'harvestor.*.type' => 'required|in:email,phone',
            'harvestor.*.value' => 'required|string',
        ]);

        foreach ($data['harvestor'] as $contact) {
            Harvestor::updateOrCreate(
                ['value' => $contact['value']],
                ['type' => $contact['type']]
            );
        }

        return response()->json(['message' => 'harvestor saved successfully']);
    }
}
