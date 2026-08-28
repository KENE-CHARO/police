<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $notifs = DB::table('notifications')->where('user_id', $user->id)->orderBy('created_at','desc')->get();
        return response()->json($notifs);
    }

    public function markRead(Request $request, $id)
    {
        $user = $request->user();
        $notification = DB::table('notifications')->where('user_id', $user->id)->where('id', $id)->first();
        if (! $notification) {
            return response()->json(['message' => 'Not found'], 404);
        }
        DB::table('notifications')->where('id', $id)->update(['read_at' => now(), 'updated_at' => now()]);
        return response()->json(['ok' => true]);
    }
}
