<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;

class AdminController extends Controller
{
    protected function ensureAdmin(Request $request)
    {
        $user = $request->user();
        if (! $user->roles()->where('name','admin')->exists()) {
            abort(403, 'Unauthorized');
        }
    }

    public function listUsers(Request $request)
    {
        $this->ensureAdmin($request);
        return response()->json(User::with('roles')->paginate(20));
    }

    public function listRoles(Request $request)
    {
        $this->ensureAdmin($request);
        return response()->json(Role::all());
    }

    public function assignRole(Request $request, User $user)
    {
        $this->ensureAdmin($request);

        $data = $request->validate(['role' => 'required|string']);
        $role = Role::firstOrCreate(['name' => $data['role']]);
        $user->roles()->syncWithoutDetaching([$role->id]);

        return response()->json($user->load('roles'));
    }

    public function removeRole(Request $request, User $user)
    {
        $this->ensureAdmin($request);

        $data = $request->validate(['role' => 'required|string']);
        $role = Role::where('name', $data['role'])->first();
        if ($role) {
            $user->roles()->detach($role->id);
        }

        return response()->json($user->load('roles'));
    }
}
