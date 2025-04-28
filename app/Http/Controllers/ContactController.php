<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;

class ContactController extends Controller
{
    public function store(ContactRequest $request): JsonResponse
    {
        // Create the contact record with a default status of 0 (pending)
        $contact = Contact::create(array_merge(
            $request->validated(),
            ['status' => 0] // Default status
        ));

        // Handle file uploads
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('contacts/' . $contact->id, 'public');

                $contact->attachments()->create([
                    'filename' => $file->getClientOriginalName(),
                    'path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Contact message sent successfully',
            'data' => new ContactResource($contact)
        ], 201);
    }

    public function index(): JsonResponse
    {
        // Fetch all contacts
        $contacts = Contact::with('attachments')->get();

        return response()->json([
            'success' => true,
            'data' => ContactResource::collection($contacts)
        ]);
    }

    public function show(int $id): JsonResponse
    {
        // Fetch a single contact by ID
        $contact = Contact::with('attachments')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new ContactResource($contact)
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        // Find the contact by ID
        $contact = Contact::findOrFail($id);

        // Delete associated attachments
        foreach ($contact->attachments as $attachment) {
            \Storage::disk('public')->delete($attachment->path);
            $attachment->delete();
        }

        // Delete the contact
        $contact->delete();

        return response()->json([
            'success' => true,
            'message' => 'Contact deleted successfully'
        ]);
    }
}
