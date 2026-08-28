<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

class AdminNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_mark_read_and_delete_notifications()
    {
        $adminRole = Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole->id);

        // create some notifications
        DB::table('notifications')->insert([
            ['user_id' => $admin->id, 'type' => 'test', 'data' => json_encode(['foo'=>'bar']), 'read_at' => null, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $admin->id, 'type' => 'test2', 'data' => json_encode(['a'=>'b']), 'read_at' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        Sanctum::actingAs($admin);

        $list = $this->getJson('/api/admin/notifications');
        $list->assertStatus(200)->assertJsonCount(2);

        $ids = DB::table('notifications')->pluck('id')->toArray();

        $res = $this->postJson('/api/admin/notifications/mark-read', ['ids' => $ids]);
        $res->assertStatus(200)->assertJson(['updated' => 2]);

        $res2 = $this->deleteJson('/api/admin/notifications', ['ids' => $ids]);
        $res2->assertStatus(200)->assertJson(['deleted' => 2]);
    }
}
