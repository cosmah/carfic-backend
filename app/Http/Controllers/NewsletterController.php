<?php

namespace App\Http\Controllers;

use App\Models\Newsletter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NewsletterController extends Controller
{
    /**
     * Display a listing of the newsletters.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $newsletters = Newsletter::orderBy('created_at', 'desc')->get();

        return response()->json([
            'data' => $newsletters
        ]);
    }

    /**
     * Store a newly created newsletter in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'status' => 'required|in:draft,scheduled,published',
            'published_at' => 'nullable|date|required_if:status,scheduled',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $newsletter = Newsletter::create([
            'title' => $request->title,
            'content' => $request->content,
            'status' => $request->status,
            'published_at' => $request->published_at,
        ]);

        return response()->json([
            'message' => 'Newsletter created successfully',
            'data' => $newsletter
        ], 201);
    }

    /**
     * Display the specified newsletter.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $newsletter = Newsletter::findOrFail($id);

        return response()->json([
            'data' => $newsletter
        ]);
    }

    /**
     * Update the specified newsletter in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $newsletter = Newsletter::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
            'status' => 'sometimes|required|in:draft,scheduled,published',
            'published_at' => 'nullable|date|required_if:status,scheduled',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $newsletter->update($request->all());

        return response()->json([
            'message' => 'Newsletter updated successfully',
            'data' => $newsletter
        ]);
    }

    /**
     * Send the newsletter to all active subscribers.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function send($id)
    {
        $newsletter = Newsletter::findOrFail($id);

        // Dispatch job to send newsletter
        \App\Jobs\SendNewsletterJob::dispatch($newsletter);

        // Update status to published
        $newsletter->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        return response()->json([
            'message' => 'Newsletter queued for sending',
            'data' => $newsletter
        ]);
    }

    /**
     * Remove the specified newsletter from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $newsletter = Newsletter::findOrFail($id);
        $newsletter->delete();

        return response()->json([
            'message' => 'Newsletter deleted successfully'
        ]);
    }
}
