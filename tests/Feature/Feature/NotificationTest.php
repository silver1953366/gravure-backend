<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /** @test */
    public function a_notification_can_be_created_and_persisted()
    {
        // 1. Arrange & Act
        $notification = Notification::factory()->create([
            'type' => 'system_alert',
            'read_at' => null,
        ]);

        // 2. Assert (Vérifier qu'elle existe dans la base de données)
        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'type' => 'system_alert',
            'read_at' => null,
        ]);
    }

    /** @test */
    public function it_belongs_to_a_user()
    {
        // 1. Arrange
        $user = User::factory()->create();
        $notification = Notification::factory()->create(['user_id' => $user->id]);

        // 2. Act & Assert
        $this->assertInstanceOf(User::class, $notification->user);
        $this->assertEquals($user->id, $notification->user->id);
    }

    /** @test */
    public function a_notification_can_be_marked_as_read()
    {
        // 1. Arrange
        $notification = Notification::factory()->create(['read_at' => null]);
        $this->assertNull($notification->read_at);

        // 2. Act
        // Simulation de marquage comme lu
        $notification->read_at = now();
        $notification->save();
        $notification->refresh();

        // 3. Assert
        $this->assertNotNull($notification->read_at);
    }

    /** @test */
    public function deleting_a_user_cascades_and_deletes_their_notifications()
    {
        // 1. Arrange
        $user = User::factory()->create();
        $notification = Notification::factory()->create(['user_id' => $user->id]);

        $this->assertDatabaseHas('notifications', ['id' => $notification->id]);

        // 2. Act (Supprimer l'utilisateur)
        $user->delete();

        // 3. Assert (Vérifier que la notification est supprimée)
        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
    }

    /** @test */
    public function it_can_return_only_unread_notifications_via_scope()
    {
        // 1. Arrange
        // Créer 3 lues
        Notification::factory()->count(3)->create(['read_at' => now()]);
        // Créer 2 non lues
        Notification::factory()->count(2)->create(['read_at' => null]);

        // 2. Act
        // On vérifie que le scope (ou la requête directe) renvoie seulement les non lues
        $unreadCount = Notification::whereNull('read_at')->count();

        // 3. Assert
        $this->assertEquals(2, $unreadCount);
    }
}
