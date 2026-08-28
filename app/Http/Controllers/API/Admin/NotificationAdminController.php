<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationAdminController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (! $user->roles()->where('name','admin')->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $q = DB::table('notifications')->orderBy('created_at','desc');
        if ($request->filled('user_id')) {
            $q->where('user_id', $request->input('user_id'));
        }

        $perPage = (int) $request->input('per_page', 50);
        $page = (int) $request->input('page', 1);
        $results = $q->forPage($page, $perPage)->get();

        return response()->json($results);
    }

    public function markReadBulk(Request $request)
    {
        $user = $request->user();
        if (! $user->roles()->where('name','admin')->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $ids = $request->input('ids', []);
        if (! is_array($ids) || empty($ids)) {
            return response()->json(['message' => 'ids required'], 422);
        }

        $now = now();
        $updated = DB::table('notifications')->whereIn('id', $ids)->update(['read_at' => $now, 'updated_at' => $now]);

        return response()->json(['updated' => $updated]);
    }

    public function deleteBulk(Request $request)
    {
        $user = $request->user();
        if (! $user->roles()->where('name','admin')->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $ids = $request->input('ids', []);
        if (! is_array($ids) || empty($ids)) {
            return response()->json(['message' => 'ids required'], 422);
        }

        $deleted = DB::table('notifications')->whereIn('id', $ids)->delete();
        return response()->json(['deleted' => $deleted]);
    }
}
