<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Plainte;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AgentController extends Controller
{
    public function storePlainte(Request $request)
    {
        $user = $request->user();
        if (! $user->roles()->where('name', 'agent_accueil')->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'plaignant_id' => 'required|integer|exists:users,id',
            'commissariat_id' => 'nullable|integer|exists:commissariats,id',
        ]);

        $data['reference'] = 'PLT-'.Str::upper(Str::random(8)).'-'.time();

        $plainte = Plainte::create([
            'titre' => $data['titre'],
            'description' => $data['description'] ?? null,
            'plaignant_id' => $data['plaignant_id'],
            'commissariat_id' => $data['commissariat_id'] ?? null,
            'reference' => $data['reference'],
        ]);

        return response()->json($plainte->load('attachments','commissariat','plaignant'), 201);
    }
}
