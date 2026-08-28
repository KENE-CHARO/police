<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Models\Plainte;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    public function download(Request $request, Plainte $plainte, $attachmentId)
    {
        $attachment = Attachment::findOrFail($attachmentId);
        $user = $request->user();

        if (! ($attachment->attachable_type === get_class($plainte) && $attachment->attachable_id == $plainte->id)) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $this->authorize('download', $attachment);

        if (! Storage::disk('public')->exists($attachment->path)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        return Storage::disk('public')->download($attachment->path, $attachment->filename, ['Content-Type' => $attachment->mime]);
    }
}
