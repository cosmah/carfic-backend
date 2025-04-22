<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Snapshot;
use App\Models\EmailFinding;
use App\Models\Cookie;
use App\Models\LocalStorage;
use App\Models\SessionStorage;
use App\Models\FormData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SnapshotController extends Controller
{
    /**
     * Store a new snapshot.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'summary' => 'required|array',
            'domain_filter' => 'nullable|string',
            'cookies' => 'nullable|array',
            'localStorage' => 'nullable|array',
            'sessionStorage' => 'nullable|array',
            'formData' => 'nullable|array',
            'emailDetection' => 'nullable|array|sometimes', // Email detection data
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        // Ensure the user is authenticated
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Create the snapshot
        $snapshot = Snapshot::create([
            'user_id' => Auth::id(),
            'domain_filter' => $request->domain_filter,
            'summary' => json_encode($request->summary),
        ]);

        // Save cookies
        if ($request->has('cookies') && is_array($request->input('cookies'))) {
            $cookiesData = [];
            foreach ($request->input('cookies') as $cookie) {
                $cookiesData[] = [
                    'snapshot_id' => $snapshot->id,
                    'name' => $cookie['name'] ?? null,
                    'value' => $cookie['value'] ?? null,
                    'domain' => $cookie['domain'] ?? null,
                    'path' => $cookie['path'] ?? null,
                    'expires' => $cookie['expires'] ?? null,
                    'secure' => $cookie['secure'] ?? null,
                    'http_only' => $cookie['httpOnly'] ?? null,
                    'same_site' => $cookie['sameSite'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            Cookie::insert($cookiesData);
        }

        // Save local storage
        if ($request->has('localStorage') && is_array($request->input('localStorage'))) {
            $localStorageData = [];
            foreach ($request->input('localStorage') as $domain => $items) {
                foreach ($items as $key => $value) {
                    $localStorageData[] = [
                        'snapshot_id' => $snapshot->id,
                        'key' => $key ?? null,
                        'value' => $value ?? null,
                        'domain' => $domain ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
            LocalStorage::insert($localStorageData);
        }

        // Save session storage
        if ($request->has('sessionStorage') && is_array($request->input('sessionStorage'))) {
            $sessionStorageData = [];
            foreach ($request->input('sessionStorage') as $domain => $items) {
                foreach ($items as $key => $value) {
                    $sessionStorageData[] = [
                        'snapshot_id' => $snapshot->id,
                        'key' => $key ?? null,
                        'value' => $value ?? null,
                        'domain' => $domain ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
            SessionStorage::insert($sessionStorageData);
        }

        // Save form data
        if ($request->has('formData') && is_array($request->input('formData'))) {
            $formDataEntries = [];
            foreach ($request->input('formData') as $formName => $fields) {
                foreach ($fields as $fieldName => $fieldValue) {
                    $formDataEntries[] = [
                        'snapshot_id' => $snapshot->id,
                        'form_name' => $formName ?? null,
                        'field_name' => $fieldName ?? null,
                        'field_value' => $fieldValue ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
            FormData::insert($formDataEntries);
        }

        // Process and save email findings (if present)
        if ($request->has('emailDetection') && isset($request->input('emailDetection')['possibleEmailsFound']) && is_array($request->input('emailDetection')['possibleEmailsFound'])) {
            $emailFindingsData = [];
            foreach ($request->input('emailDetection')['possibleEmailsFound'] as $finding) {
                $emailFindingsData[] = [
                    'snapshot_id' => $snapshot->id,
                    'domain' => $finding['domain'] ?? 'unknown',
                    'data_type' => $finding['source']['type'] ?? 'unknown',
                    'source_name' => $finding['source']['name'] ?? $finding['source']['key'] ?? 'N/A',
                    'value' => $finding['match'] ?? null,
                    'match' => $finding['match'] ?? '',
                    'match_type' => $finding['matchType'] ?? 'unknown',
                    'confidence' => $finding['confidence'] ?? 'low',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            EmailFinding::insert($emailFindingsData);
        }

        // Return a success response with the snapshot ID
        return response()->json([
            'message' => 'Snapshot saved successfully',
            'snapshot_id' => $snapshot->id,
        ], 201);
    }

    public function show($id)
    {
        $snapshot = Snapshot::with(['emailFindings', 'cookies', 'localStorage', 'sessionStorage', 'formData'])->find($id);

        if (!$snapshot) {
            return response()->json(['error' => 'Snapshot not found'], 404);
        }

        if ($snapshot->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($snapshot);
    }
}
