<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Plainte;
use App\Models\Attachment;
use Laravel\Sanctum\Sanctum;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AuthorizationNegativeTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_owner_cannot_download_attachment()
    {
        Storage::fake('public');

        $owner = User::factory()->create();
        $other = User::factory()->create();

        Sanctum::actingAs($owner);
        $plainte = Plainte::create(['titre'=>'T','description'=>'D','plaignant_id'=>$owner->id,'reference'=>'R1']);

        $file = UploadedFile::fake()->create('sec.txt', 1);
        $path = $file->store('attachments','public');
        $attachment = Attachment::create([
            'filename' => $file->getClientOriginalName(),
            'path' => $path,
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => $owner->id,
            'attachable_type' => Plainte::class,
            'attachable_id' => $plainte->id,
        ]);

        Sanctum::actingAs($other);
        $res = $this->get('/api/plaintes/' . $plainte->id . '/attachments/' . $attachment->id);
        $res->assertStatus(403);
    }
}
