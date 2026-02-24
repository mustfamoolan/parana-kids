<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Investor;
use App\Models\Project;
use App\Models\Treasury;

class MobileApiTest extends TestCase
{
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_investors_list()
    {
        $response = $this->actingAs($this->user)->getJson('/api/mobile/admin/investors');
        $response->assertStatus(200);
    }

    public function test_projects_list()
    {
        $response = $this->actingAs($this->user)->getJson('/api/mobile/admin/projects');
        $response->assertStatus(200);
    }

    public function test_investor_details()
    {
        $investor = Investor::factory()->create();
        $treasury = Treasury::create([
            'name' => 'Test Treasury',
            'investor_id' => $investor->id,
            'current_balance' => 1000,
            'created_by' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/mobile/admin/investors/{$investor->id}");
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'investor',
                    'current_warehouse_value',
                    'total_profit'
                ]
            ]);
    }
}
