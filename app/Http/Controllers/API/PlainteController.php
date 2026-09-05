<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePlainteRequest;
use App\Http\Requests\UpdatePlainteRequest;
use App\Models\Attachment;
use App\Models\Plainte;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Http\Requests\StoreAttachmentRequest;
use Illuminate\Support\Facades\Storage;
use App\Models\Historique;
use App\Services\CampayService;

class PlainteController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Plainte::query();
        // Citizens only see their own plaintes.
        if ($user->roles()->where('name', 'citoyen')->exists()) {
            $query->where('plaignant_id', $user->id);
        }

        // Enqueteur may only see plaintes that are assigned to them via an Enquete
        if ($user->roles()->where('name', 'enqueteur')->exists()) {
            $query->whereHas('enquetes', function ($q) use ($user) {
                $q->where('enqueteur_id', $user->id);
            });
        }

        return response()->json($query->with('commissariat', 'plaignant')->paginate(15));
    }

    public function store(StorePlainteRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();

        // Only citizens may create plaintes via this endpoint.
        if (! $user->roles()->where('name', 'citoyen')->exists()) {
            return response()->json(['message' => 'Seuls les citoyens peuvent déposer une plainte.'], 403);
        }

        // If citizen, we persist the plainte first in pending payment state,
        // then attempt stimulation; final confirmation will come via webhook.
        $isCitizen = $user->roles()->where('name', 'citoyen')->exists();

        if ($isCitizen) {
            // ensure payment metadata defaults
            $data['paid'] = $data['paid'] ?? false;
            $data['payment_status'] = $data['payment_status'] ?? 'pending';
        }

        $data['plaignant_id'] = $user->id;
        $data['reference'] = 'PLT-' . Str::upper(Str::random(8)) . '-' . time();

        $plainte = Plainte::create($data);

        // After persisting, if citizen chose mobile, attempt stimulation and update record.
        if ($isCitizen && (($data['payment_method'] ?? '') === 'mobile')) {
            $campay = app(CampayService::class);
            $phone = $data['payment_phone'] ?? '';
            $operator = $data['payment_operator'] ?? '';
            $amount = (int) ($data['payment_amount'] ?? config('campay.default_amount'));

            $res = $campay->stimulate($phone, $operator, $amount);

            if (! ($res['success'] ?? false)) {
                // mark attempt as failed but keep plainte persisted for retry or manual follow-up
                $plainte->update([
                    'payment_status' => 'failed',
                ]);
            } else {
                $plainte->update([
                    'payment_txn_id' => $res['transaction_id'] ?? null,
                    'payment_status' => 'processing',
                    'payment_amount' => $amount,
                ]);
            }
        }

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('attachments', 'public');
                $attachment = Attachment::create([
                    'filename' => $file->getClientOriginalName(),
                    'path' => $path,
                    'mime' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                    'uploaded_by' => $request->user()->id,
                    'attachable_type' => Plainte::class,
                    'attachable_id' => $plainte->id,
                ]);
            }
        }

        return response()->json($plainte->load('attachments', 'commissariat', 'plaignant'), 201);
    }

    public function show(Request $request, Plainte $plainte)
    {
        $this->authorize('view', $plainte);

        return response()->json($plainte->load('attachments', 'commissariat', 'plaignant'));
    }

    public function update(UpdatePlainteRequest $request, Plainte $plainte)
    {
        $this->authorize('update', $plainte);

        $plainte->update($request->validated());

        return response()->json($plainte->fresh()->load('attachments', 'commissariat', 'plaignant'));
    }

    public function destroy(Request $request, Plainte $plainte)
    {
        $this->authorize('delete', $plainte);

        $plainte->delete();
        return response()->json(null, 204);
    }

    public function historiques(Request $request, Plainte $plainte)
    {
        $this->authorize('view', $plainte);

        $enqueteIds = $plainte->enquetes()->pluck('id')->toArray();

        $hist = Historique::with('user')
            ->where(function ($q) use ($plainte, $enqueteIds) {
                $q->where(function ($q2) use ($plainte) {
                    $q2->where('subject_type', Plainte::class)->where('subject_id', $plainte->id);
                });

                if (! empty($enqueteIds)) {
                    $q->orWhere(function ($q3) use ($enqueteIds) {
                        $q3->where('subject_type', Enquete::class)->whereIn('subject_id', $enqueteIds);
                    });
                }
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($hist);
    }

    public function uploadAttachment(StoreAttachmentRequest $request, Plainte $plainte)
    {
        $this->authorize('update', $plainte);

        $user = $request->user();

        $file = $request->file('file');
        $path = $file->store('attachments', 'public');

        $attachment = Attachment::create([
            'filename' => $file->getClientOriginalName(),
            'path' => $path,
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => $user->id,
            'attachable_type' => Plainte::class,
            'attachable_id' => $plainte->id,
        ]);

        return response()->json($attachment, 201);
    }

    public function setRecevable(Request $request, Plainte $plainte)
    {
        $user = $request->user();

        // only agent_accueil can set recevability
        if (! $user->roles()->where('name', 'agent_accueil')->exists()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'recevable' => 'required|boolean',
            'note' => 'nullable|string|max:1000',
        ]);

        $plainte->recevable = $data['recevable'];
        $plainte->save();

        // log historique
        Historique::create([
            'subject_type' => Plainte::class,
            'subject_id' => $plainte->id,
            'user_id' => $user->id,
            'action' => $data['recevable'] ? 'marked_recevable' : 'marked_non_recevable',
            'details' => json_encode(['note' => $data['note'] ?? null]),
        ]);

        return response()->json($plainte->fresh()->load('attachments', 'commissariat', 'plaignant'));
    }
}
