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

class PlainteController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Plainte::query();
        if (! $user->roles()->where('name','admin')->exists()) {
            $query->where('plaignant_id', $user->id);
        }

        return response()->json($query->with('commissariat','plaignant')->paginate(15));
    }

    public function store(StorePlainteRequest $request)
    {
        $data = $request->validated();
        $data['plaignant_id'] = $request->user()->id;
        $data['reference'] = 'PLT-'.Str::upper(Str::random(8)).'-'.time();

        $plainte = Plainte::create($data);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('attachments','public');
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

        return response()->json($plainte->load('attachments','commissariat','plaignant'), 201);
    }

    public function show(Request $request, Plainte $plainte)
    {
        $this->authorize('view', $plainte);

        return response()->json($plainte->load('attachments','commissariat','plaignant'));
    }

    public function update(UpdatePlainteRequest $request, Plainte $plainte)
    {
        $this->authorize('update', $plainte);

        $plainte->update($request->validated());

        return response()->json($plainte->fresh()->load('attachments','commissariat','plaignant'));
    }

    public function destroy(Request $request, Plainte $plainte)
    {
        $this->authorize('delete', $plainte);

        $plainte->delete();
        return response()->json(null,204);
    }

    public function historiques(Request $request, Plainte $plainte)
    {
        $this->authorize('view', $plainte);

        $enqueteIds = $plainte->enquetes()->pluck('id')->toArray();

        $hist = Historique::with('user')
            ->where(function($q) use ($plainte, $enqueteIds) {
                $q->where(function($q2) use ($plainte) {
                    $q2->where('subject_type', Plainte::class)->where('subject_id', $plainte->id);
                });

                if (! empty($enqueteIds)) {
                    $q->orWhere(function($q3) use ($enqueteIds) {
                        $q3->where('subject_type', Enquete::class)->whereIn('subject_id', $enqueteIds);
                    });
                }
            })
            ->orderBy('created_at','desc')
            ->get();

        return response()->json($hist);
    }

    public function uploadAttachment(StoreAttachmentRequest $request, Plainte $plainte)
    {
        $this->authorize('update', $plainte);

        $user = $request->user();

        $file = $request->file('file');
        $path = $file->store('attachments','public');

        $attachment = Attachment::create([
            'filename' => $file->getClientOriginalName(),
            'path' => $path,
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => $user->id,
            'attachable_type' => Plainte::class,
            'attachable_id' => $plainte->id,
        ]);

        return response()->json($attachment,201);
    }
}
