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
}
