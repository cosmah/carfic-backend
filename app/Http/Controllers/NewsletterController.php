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

        // Retrieve the newsletter
        $newsletter = Newsletter::findOrFail($id);

        // Check if the newsletter is already published
        if ($newsletter->status === 'published') {
            return response()->json([
                'message' => 'Newsletter is already published',
                'data' => $newsletter
            ], 400);
        }

        // Instead of dispatching a job, process directly
        try {
            // Create an instance of the job and call handle() directly
            $job = new \App\Jobs\SendNewsletterJob($newsletter);
            $job->handle(); // This immediately processes the job
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send the newsletter',
                'error' => $e->getMessage()
            ], 500);
        }

        // Update the newsletter status to published
        try {
            $newsletter->update([
                'status' => 'published',
                'published_at' => now(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update newsletter status',
                'error' => $e->getMessage()
            ], 500);
        }


        return response()->json([
            'message' => 'Newsletter sent successfully',
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
