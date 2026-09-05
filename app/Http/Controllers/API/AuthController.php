<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    protected function createUserFromPayload(array $data, string $roleName, bool $isActive): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'is_active' => $isActive,
        ]);

        if (isset($data['commissariat_id'])) {
            $user->commissariat_id = $data['commissariat_id'];
            $user->save();
        }

        $role = Role::firstOrCreate(['name' => $roleName]);
        $user->roles()->syncWithoutDetaching([$role->id]);

        return $user->fresh()->load('roles');
    }

    public function register(RegisterRequest $request)
    {
        $data = $request->validated();
        $roleName = isset($data['role']) && $data['role'] === 'citoyen' ? 'citoyen' : 'citoyen';
        $user = $this->createUserFromPayload($data, $roleName, true);
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json(['user' => $user, 'token' => $token], 201);
    }

    public function registerStaff(RegisterRequest $request)
    {
        $data = $request->validated();
        $roleName = $data['role'] ?? 'agent_accueil';

        if (! in_array($roleName, ['agent_accueil', 'enqueteur'], true)) {
            return response()->json(['message' => 'Le personnel ne peut s’inscrire qu’en tant qu’agent d’accueil ou enquêteur.'], 422);
        }

        if (empty($data['commissariat_id'])) {
            return response()->json([
                'message' => 'Le commissariat de travail est obligatoire pour créer un compte personnel.',
                'errors' => ['commissariat_id' => ['Le commissariat de travail est obligatoire.']],
            ], 422);
        }

        $user = $this->createUserFromPayload($data, $roleName, false);
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json(['user' => $user, 'token' => $token], 201);
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();

        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = Auth::user();

        if (! $user) {
            return response()->json(['message' => 'Utilisateur non authentifié.'], 401);
        }

        $user->load('roles');

        if (! $user->is_active && $user->roles()->whereIn('name', ['agent_accueil', 'enqueteur', 'admin'])->exists()) {
            return response()->json([
                'message' => 'Votre compte est en attente de validation par l\'administrateur.',
            ], 403);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json(['user' => $user, 'token' => $token], 200);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $user->currentAccessToken()?->delete();
        }

        return response()->json(['message' => 'Logged out'], 200);
    }

    public function me(Request $request)
    {
        return response()->json($request->user()->load('roles'));
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();
        $user->update($data);

        return response()->json($user->fresh()->load('roles'));
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $user = $request->user();
        $user->password = $request->validated()['password'];
        $user->save();

        return response()->json([
            'message' => 'Mot de passe mis à jour avec succès.',
            'user' => $user->fresh()->load('roles'),
        ]);
    }

    public function deleteAccount(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Utilisateur non authentifié.'], 401);
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json([
            'message' => 'Compte supprimé avec succès.',
        ]);
    }
}
