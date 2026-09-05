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
        // Creation of plaintes by agents is disabled: only citizens may create plaintes.
        return response()->json(['message' => 'Les comptes personnels ne peuvent pas déposer de plainte.'], 403);
    }
}
