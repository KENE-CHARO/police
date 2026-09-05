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
        if (! $user->roles()->where('name', 'admin')->exists()) {
            abort(403, 'Unauthorized');
        }
    }

    public function listUsers(Request $request)
    {
        $this->ensureAdmin($request);

        $users = User::with('roles')
            ->orderBy('is_active', 'asc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($users);
    }

    public function listEnqueteurs(Request $request)
    {
        $user = $request->user();
        if (! $user->roles()->whereIn('name', ['admin', 'agent_accueil'])->exists()) {
            abort(403, 'Unauthorized');
        }

        $enqueteurs = User::with('roles')
            ->whereHas('roles', function ($q) {
                $q->where('name', 'enqueteur');
            })
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json($enqueteurs);
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

    public function activateUser(Request $request, User $user)
    {
        $this->ensureAdmin($request);

        $user->is_active = true;
        $user->save();

        return response()->json([
            'message' => 'Compte validé avec succès.',
            'user' => $user->load('roles'),
        ]);
    }

    public function deleteUser(Request $request, User $user)
    {
        $this->ensureAdmin($request);

        $user->tokens()->delete();
        $user->delete();

        return response()->json([
            'message' => 'Compte utilisateur supprimé avec succès.',
        ]);
    }
}
