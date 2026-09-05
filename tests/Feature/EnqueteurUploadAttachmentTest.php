<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use App\Models\User;
use App\Models\Role;
use App\Models\Plainte;
use App\Models\Enquete;

class EnqueteurUploadAttachmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_enqueteur_assigned_can_upload_attachment()
    {
        $roleEnqueteur = Role::firstOrCreate(['name' => 'enqueteur']);
        $roleCitoyen = Role::firstOrCreate(['name' => 'citoyen']);

        $enq = User::factory()->create(['email' => 'enq_upload@police.local']);
        $enq->roles()->attach($roleEnqueteur->id);

        $plaignant = User::factory()->create(['email' => 'p_upload@police.local']);
        $plaignant->roles()->attach($roleCitoyen->id);

        $plainte = Plainte::factory()->create(['plaignant_id' => $plaignant->id]);

        Enquete::create(['plainte_id' => $plainte->id, 'enqueteur_id' => $enq->id]);

        Sanctum::actingAs($enq);

        $file = UploadedFile::fake()->create('evidence.pdf', 100);

        $res = $this->postJson('/api/plaintes/' . $plainte->id . '/attachments', [
            'file' => $file,
        ]);

        $res->assertStatus(201);
    }
}
