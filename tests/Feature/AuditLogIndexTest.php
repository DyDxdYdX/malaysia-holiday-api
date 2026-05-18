<?php

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('data admins can view the audit logs page', function () {
    $user = User::factory()->create([
        'role' => 'data_admin',
    ]);

    AuditLog::query()->create([
        'actor_id' => $user->id,
        'action' => 'holiday_updated',
        'entity_type' => 'holiday',
        'entity_id' => 12,
        'old_values' => ['name' => 'Old Name'],
        'new_values' => ['name' => 'New Name'],
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest',
    ]);

    $this->actingAs($user)
        ->get(route('admin.audit-logs.index'))
        ->assertOk()
        ->assertSee('Audit Logs')
        ->assertSee('holiday_updated')
        ->assertSee('holiday')
        ->assertSee($user->name)
        ->assertSee('Old Name')
        ->assertSee('New Name');
});
