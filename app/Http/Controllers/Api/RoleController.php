<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
    public function index() 
    {
        $roles = Role::latest()->paginate(8);
        return new RoleResource(true, 'List Data Roles', $roles);
    }

    public function store(Request $request)
    {
        try {
            // Validasi input
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255'
            ]);

            // Periksa apakah validasi gagal
            if ($validator->fails()) {
                return response()->json(['error' => 'Validation failed', 'messages' => $validator->errors()], 422);
            }

            // Buat role baru dengan data yang valid
            $role = Role::create($request->only('name'));

            // Kembalikan response sukses
            return response()->json(['success' => 'Role created successfully', 'data' => $role], 201);
        } catch (\Exception $e) {
            // Tangani exception dan kembalikan response error
            return response()->json(['error' => 'Role creation failed', 'message' => $e->getMessage()], 500);
        }
    }


    public function show($id) 
    {
        try {
            // Find role by ID
            $role = Role::findOrFail($id);

            // Return single Role as a resource
            return new RoleResource(true, 'role Detail!', $role);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Return error response if role not found
            return response()->json(['error' => 'role not found'], 404);
        } catch (\Exception $e) {
            // Return general error response for any other exceptions
            return response()->json(['error' => 'An error occurred', 'message' => $e->getMessage()], 500);
        }

    }
    
    public function update(Request $request, $id)
    {
        try {
            // Validasi input
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255'
            ]);

            // Periksa apakah validasi gagal
            if ($validator->fails()) {
                return response()->json(['error' => 'Validation failed', 'messages' => $validator->errors()], 422);
            }

            // Cari role berdasarkan ID
            $role = Role::findOrFail($id);

            // Perbarui data role
            $role->update($request->only('name'));

            // Kembalikan response sukses
            return response()->json(['success' => 'Role updated successfully', 'data' => $role], 200);
        } catch (\Exception $e) {
            // Tangani exception dan kembalikan response error
            return response()->json(['error' => 'Role update failed', 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
{
    try {
        // Cari role berdasarkan ID
        $role = Role::findOrFail($id);

        // Hapus data role
        $role->delete();

        // Kembalikan response sukses
        return response()->json(['success' => 'Role deleted successfully'], 200);
    } catch (\Exception $e) {
        // Tangani exception dan kembalikan response error
        return response()->json(['error' => 'Role deletion failed', 'message' => $e->getMessage()], 500);
    }
}


}
