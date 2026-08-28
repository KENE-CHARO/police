<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Plainte;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

class AttachmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_upload_and_download_attachment_for_their_plainte()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // create plainte
        $res = $this->postJson('/api/plaintes', ['titre' => 'T', 'description' => 'D']);
        $res->assertStatus(201);
        $plainteId = $res->json('id');

        // upload file
        $file = UploadedFile::fake()->create('doc.txt', 10);
        $upload = $this->post('/api/plaintes/' . $plainteId . '/attachments', ['file' => $file]);
        $upload->assertStatus(201)->assertJsonFragment(['filename' => 'doc.txt']);

        $attachmentId = $upload->json('id');
        $path = $upload->json('path');

        Storage::disk('public')->assertExists($path);

        // download
        $download = $this->get('/api/plaintes/' . $plainteId . '/attachments/' . $attachmentId);
        try {
            $status = $download->getStatusCode();
        } catch (\Throwable $e) {
            try {
                $status = $download->status();
            } catch (\Throwable $e2) {
                $status = null;
            }
        }
        if ($status !== 200) {
            fwrite(STDERR, "Download response status: {$status}\n");
            fwrite(STDERR, "Plainte ID: {$plainteId} Attachment ID: {$attachmentId}\n");
            fwrite(STDERR, "Upload JSON: " . print_r($upload->json(), true) . "\n");
        }

        $this->assertEquals(200, $status);
    }
}
