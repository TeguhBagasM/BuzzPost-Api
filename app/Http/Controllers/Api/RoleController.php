<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index() 
    {
        $roles = Role::latest()->paginate(8);
        return new RoleResource(true, 'List Data Roles', $roles);
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
}
