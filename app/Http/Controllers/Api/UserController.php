<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index() 
    {
        $users = User::latest()->paginate(8);
        return new UserResource(true, 'User Lists', $users);
    }

    public function store(Request $request) 
    {
        // Define validation rules
        $validator = Validator::make($request->all(), [
            'nomor_induk'      => 'required|max:30',
            'fullname'         => 'required|string|max:255',
            'username'         => 'required|string|max:255',
            'password'         => 'required|string|min:8',
            'email'            => 'required|email|unique:users,email|max:200',
            'image'            => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
            'phone'            => 'nullable|min:11|max:20'
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            // Log validation errors
            Log::error('Validation failed', $validator->errors()->toArray());
            return response()->json($validator->errors(), 422);
        }

        // Start database transaction
        DB::beginTransaction();

        try {
            // Hash the password before saving
            $data = $request->all();
            $data['password'] = Hash::make($request->password);

            // Handle image upload
            if ($request->hasFile('image')) {
                $image = $request->file('image');

                // Log file details
                Log::info('Image details', [
                    'original_name' => $image->getClientOriginalName(),
                    'mime_type' => $image->getClientMimeType(),
                    'extension' => $image->getClientOriginalExtension()
                ]);

                $extension = $image->getClientOriginalExtension();
                $newName = $request->username . '-' . now()->timestamp . '.' . $extension;
                $image->storeAs('users', $newName, 'public');
                $data['image'] = $newName;
            }

            // Create the user
            $user = User::create($data);

            // Commit the transaction
            DB::commit();

            // Return response
            return new UserResource(true, 'User Added Successfully', $user);
        } catch (\Exception $e) {
            // Rollback the transaction
            DB::rollBack();

            // Log the exception
            Log::error('User creation failed', ['message' => $e->getMessage()]);

            // Return error response
            return response()->json(['error' => 'User creation failed', 'message' => $e->getMessage()], 500);
        }
    }


    public function show($id) 
    {
        try {
            // Find user by ID
            $user = User::find($id);

            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            // Return user details
            return new UserResource(true, 'User Detail', $user);
        } catch (\Exception $e) {
            // Log the exception
            Log::error('Error retrieving user details', ['message' => $e->getMessage()]);

            // Return error response
            return response()->json(['error' => 'Error retrieving user details', 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id) 
    {
        try {
            // Define validation rules
            $validator = Validator::make($request->all(), [
                'nomor_induk'      => 'required|max:30',
                'fullname'         => 'required|string|max:255',
                'username'         => 'required|string|max:255',
                'email'            => 'required|email|unique:users,email,' . $id . '|max:200',
                'phone'            => 'nullable|min:11|max:20'
            ]);

            // Check if validation fails
            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            // Find user by ID
            $user = User::find($id);
            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            // Update user data
            $data = $request->except('password');

            // Hash the password if it is present in the request
            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->password);
            }

            // Handle image upload
            if ($request->hasFile('image')) {
                $image = $request->file('image');

                // Log file details
                Log::info('Image details', [
                    'original_name' => $image->getClientOriginalName(),
                    'mime_type' => $image->getClientMimeType(),
                    'extension' => $image->getClientOriginalExtension()
                ]);

                $extension = $image->getClientOriginalExtension();
                $newName = $request->username . '-' . now()->timestamp . '.' . $extension;
                $image->storeAs('users', $newName, 'public');
                $data['image'] = $newName;
            }

            $user->update($data);

            // Return response
            return new UserResource(true, 'User Updated Successfully', $user);
        } catch (\Exception $e) {
            // Log the exception
            Log::error('User update failed', ['message' => $e->getMessage()]);

            // Return error response
            return response()->json(['error' => 'User update failed', 'message' => $e->getMessage()], 500);
        }
    }


    public function destroy($id) 
    {
        try {
            // Find user by ID
            $user = User::find($id);

            // Check if user exists
            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            // Delete the user's image if it exists
            if ($user->image) {
                Storage::disk('public')->delete('users/' . $user->image);
            }

            // Delete the user
            $user->delete();

            // Return response
            return new UserResource(true, 'User Deleted Successfully', $user);
        } catch (\Exception $e) {
            // Log the exception
            Log::error('Error deleting user', ['message' => $e->getMessage()]);

            // Return error response
            return response()->json(['error' => 'Error deleting user', 'message' => $e->getMessage()], 500);
        }
}
}
