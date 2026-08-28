<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Enquete;
use App\Models\Plainte;
use App\Models\Historique;
use App\Notifications\EnqueteAssigned;
use App\Notifications\EnqueteStatusChanged;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\EnqueteAssignedMail;
use App\Mail\EnqueteStatusChangedMail;
use App\Events\NotificationCreated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnqueteurController extends Controller
{
    public function assign(Request $request)
    {
        $user = $request->user();
        if (! $user->roles()->where('name','enqueteur')->exists() && ! $user->roles()->where('name','admin')->exists()) {
            return response()->json(['message'=>'Unauthorized'],403);
        }

        $data = $request->validate([
            'plainte_id' => 'required|integer|exists:plaintes,id',
            'enqueteur_id' => 'required|integer|exists:users,id',
        ]);

        $plainte = Plainte::findOrFail($data['plainte_id']);

        $enquete = Enquete::create([
            'plainte_id' => $plainte->id,
            'enqueteur_id' => $data['enqueteur_id'],
            'statut' => 'open',
        ]);

        // write a notification record for the enqueteur (existing schema uses user_id)
        $enqueteur = User::find($data['enqueteur_id']);
        if ($enqueteur) {
            DB::table('notifications')->insert([
                'user_id' => $enqueteur->id,
                'type' => EnqueteAssigned::class,
                'data' => json_encode(['enquete_id' => $enquete->id, 'plainte_id' => $plainte->id, 'message' => 'Vous avez été assigné à une enquête']),
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            Log::info('notification.enquete.assigned', ['user_id' => $enqueteur->id, 'enquete_id' => $enquete->id]);
            // queue email notification
            try {
                Mail::to($enqueteur)->queue(new EnqueteAssignedMail($enquete));
            } catch (\Throwable $e) {
                Log::warning('mail.enqueue.failed', ['error' => $e->getMessage()]);
            }
            // broadcast in real-time
            try {
                event(new NotificationCreated($enqueteur->id, ['type' => 'enquete.assigned', 'enquete_id' => $enquete->id, 'plainte_id' => $plainte->id]));
            } catch (\Throwable $e) {
                Log::warning('broadcast.failed', ['error' => $e->getMessage()]);
            }
        }

        // log historique
        Historique::create([
            'subject_type' => Plainte::class,
            'subject_id' => $plainte->id,
            'user_id' => $user->id,
            'action' => 'assign_enquete',
            'details' => json_encode(['enquete_id' => $enquete->id, 'enqueteur_id' => $enquete->enqueteur_id]),
        ]);

        return response()->json($enquete->load('plainte','enqueteur'), 201);
    }

    public function updateStatus(Request $request, Enquete $enquete)
    {
        $user = $request->user();
        if ($enquete->enqueteur_id !== $user->id && ! $user->roles()->where('name','admin')->exists()) {
            return response()->json(['message'=>'Unauthorized'],403);
        }

        $data = $request->validate([
            'statut' => 'required|string|in:open,in_progress,closed'
        ]);

        $enquete->update(['statut' => $data['statut']]);

        // log historique
        Historique::create([
            'subject_type' => Enquete::class,
            'subject_id' => $enquete->id,
            'user_id' => $user->id,
            'action' => 'update_status',
            'details' => json_encode(['statut' => $data['statut']]),
        ]);

        // also add a plainte-level historique for visibility
        Historique::create([
            'subject_type' => Plainte::class,
            'subject_id' => $enquete->plainte_id,
            'user_id' => $user->id,
            'action' => 'enquete_update_status',
            'details' => json_encode(['enquete_id' => $enquete->id, 'statut' => $data['statut']]),
        ]);

        // notify enqueteur and plaignant
        $enqueteur = $enquete->enqueteur;
        if ($enqueteur) {
            DB::table('notifications')->insert([
                'user_id' => $enqueteur->id,
                'type' => EnqueteStatusChanged::class,
                'data' => json_encode(['enquete_id' => $enquete->id, 'plainte_id' => $enquete->plainte_id, 'statut' => $data['statut'], 'message' => 'Le statut de l\'enquête a changé']),
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            Log::info('notification.enquete.status_changed', ['user_id' => $enqueteur->id, 'enquete_id' => $enquete->id, 'statut' => $data['statut']]);
            try {
                Mail::to($enqueteur)->queue(new EnqueteStatusChangedMail($enquete, $data['statut']));
            } catch (\Throwable $e) {
                Log::warning('mail.enqueue.failed', ['error' => $e->getMessage()]);
            }
            try {
                event(new NotificationCreated($enqueteur->id, ['type' => 'enquete.status_changed', 'enquete_id' => $enquete->id, 'statut' => $data['statut']]));
            } catch (\Throwable $e) {
                Log::warning('broadcast.failed', ['error' => $e->getMessage()]);
            }
        }

        $plaignant = $enquete->plainte->plaignant ?? null;
        if ($plaignant) {
            DB::table('notifications')->insert([
                'user_id' => $plaignant->id,
                'type' => EnqueteStatusChanged::class,
                'data' => json_encode(['enquete_id' => $enquete->id, 'plainte_id' => $enquete->plainte_id, 'statut' => $data['statut'], 'message' => 'Le statut de votre plainte a changé']),
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            Log::info('notification.enquete.status_changed', ['user_id' => $plaignant->id, 'enquete_id' => $enquete->id, 'statut' => $data['statut']]);
            try {
                Mail::to($plaignant)->queue(new EnqueteStatusChangedMail($enquete, $data['statut']));
            } catch (\Throwable $e) {
                Log::warning('mail.enqueue.failed', ['error' => $e->getMessage()]);
            }
            try {
                event(new NotificationCreated($plaignant->id, ['type' => 'enquete.status_changed', 'enquete_id' => $enquete->id, 'statut' => $data['statut']]));
            } catch (\Throwable $e) {
                Log::warning('broadcast.failed', ['error' => $e->getMessage()]);
            }
        }

        return response()->json($enquete->fresh()->load('plainte','enqueteur'));
    }
}
