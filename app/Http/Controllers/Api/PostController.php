<?php

namespace App\Http\Controllers\Api;

//import model Post
use App\Models\Post;

use App\Http\Controllers\Controller;

//import resource PostResource
use App\Http\Resources\PostResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PostController extends Controller
{    
    /**
     * index
     *
     * @return void
     */
    public function index()
    {
        //get all posts
        $posts = Post::latest()->paginate(8);

        //return collection of posts as a resource
        return new PostResource(true, 'List Data Posts', $posts);
    }

    public function store(Request $request)
    {
        // Define validation rules
        $validator = Validator::make($request->all(), [
            'title'              => 'required',
            'description'         => 'required',
            'image'              => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'status'             => 'required'
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Upload image
        $newName = '';
        if ($request->hasFile('image')) {
            try {
                $extension = $request->file('image')->getClientOriginalExtension();
                $newName = $request->title . '-' . now()->timestamp . '.' . $extension;
                $request->file('image')->storeAs('posts', $newName, 'public');
            } catch (\Exception $e) {
                return response()->json(['error' => 'File upload failed'], 500);
            }
        }

        // Create post
        try {
            $post = Post::create([
                'image'       => $newName,
                'title'        => $request->title,
                'description' => $request->description,
                'status'      => $request->status
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Post creation failed'], 500);
        }

        // Return response
        return new PostResource(true, 'Post Added Successfully!', $post);
    }

    public function show($id)
    {
        try {
            // Find post by ID
            $post = Post::findOrFail($id);

            // Return single post as a resource
            return new PostResource(true, 'Post Detail!', $post);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Return error response if post not found
            return response()->json(['error' => 'Post not found'], 404);
        } catch (\Exception $e) {
            // Return general error response for any other exceptions
            return response()->json(['error' => 'An error occurred', 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        // Define validation rules
        $validator = Validator::make($request->all(), [
            'title'              => 'required',
            'description'        => 'required',
            'image'              => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'status'             => 'required' 
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Find post by ID
        $post = Post::find($id);

        if (!$post) {
            return response()->json(['error' => 'Post not found'], 404);
        }

        // Start database transaction
        DB::beginTransaction();

        try {
            // Check if image is not empty
            if ($request->hasFile('image')) {
                // Upload image
                $extension = $request->file('image')->extension();
                $newName = $request->title . '-' . now()->timestamp . '.' . $extension;
                $request->file('image')->storeAs('posts', $newName, 'public');
                $request['image'] = $newName;

                // Delete old image if exists
                if ($post->image) {
                    Storage::delete('public/posts/' . basename($post->image));
                }

                // Update post with new image
                $post->update([
                    'image'       => $newName,
                    'title'        => $request->title,
                    'description' => $request->description,
                    'status'      => $request->status
                ]);
            } else {
                // Update post without image
                $post->update([
                    'title'           => $request->title,
                    'description'    => $request->description,
                    'status'         => $request->status,
                ]);
            }

            // Commit the transaction
            DB::commit();

            // Return response
            return new PostResource(true, 'Post Updated Successfully!', $post);
        } catch (\Exception $e) {
            // Rollback the transaction
            DB::rollBack();

            // Return error response
            return response()->json(['error' => 'Post update failed', 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id) 
    {
        try {
            // Find post by ID
            $post = Post::find($id);

            // Check if post exists
            if (!$post) {
                return response()->json(['error' => 'post not found'], 404);
            }

            // Delete the post's image if it exists
            if ($post->image) {
                Storage::disk('public')->delete('posts/' . $post->image);
            }

            // Delete the post
            $post->delete();

            // Return response
            return new PostResource(true, 'post Deleted Successfully', $post);
        } catch (\Exception $e) {
            // Log the exception
            Log::error('Error deleting post', ['message' => $e->getMessage()]);

            // Return error response
            return response()->json(['error' => 'Error deleting post', 'message' => $e->getMessage()], 500);
        }
}
}