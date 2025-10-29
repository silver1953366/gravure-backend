<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Crée un utilisateur admin pour les tests (role: 'admin').
     */
    protected function createAdminUser(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /**
     * Crée un utilisateur non-admin (client) (role: 'user').
     */
    protected function createUser(): User
    {
        return User::factory()->create(['role' => 'user']);
    }

    /** @test */
    public function only_admin_can_view_the_activity_log_index(): void
    {
        // Créer quelques entrées d'activité
        Activity::factory()->count(3)->create();

        // 1. Utilisateur standard (client) : Doit être refusé (403 Forbidden)
        $user = $this->createUser();
        $this->actingAs($user)
            ->getJson(route('activities.index'))
            ->assertStatus(403); 

        // 2. Utilisateur Administrateur : Doit réussir (200 OK)
        $admin = $this->createAdminUser();
        $response = $this->actingAs($admin)
            ->getJson(route('activities.index'));

        $response->assertOk() 
            ->assertJsonCount(3, 'data') 
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'action',
                        // On vérifie que les champs polymorphiques sont présents
                        'model_type',
                        'model_id',
                        'created_at',
                        'user', 
                    ]
                ]
            ]);
    }

    /** @test */
    public function only_admin_can_view_a_specific_activity_detail(): void
    {
        $activity = Activity::factory()->create();

        // 1. Utilisateur standard (client) : Doit être refusé (403 Forbidden)
        $user = $this->createUser();
        $this->actingAs($user)
            ->getJson(route('activities.show', $activity->id))
            ->assertStatus(403);

        // 2. Utilisateur Administrateur : Doit réussir (200 OK)
        $admin = $this->createAdminUser();
        $this->actingAs($admin)
            ->getJson(route('activities.show', $activity->id))
            ->assertOk()
            ->assertJsonFragment(['id' => $activity->id]);
    }
}
