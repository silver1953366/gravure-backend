<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Material;
use App\Models\Quote; // Ajout d'un modèle traçable
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AttachmentTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /** @test */
    public function an_attachment_can_be_created_and_persisted_with_all_attributes(): void
    {
        // 1. Arrangement & Action (Utiliser la Factory pour créer la pièce jointe)
        $attachment = Attachment::factory()->create();

        // 2. Assertion (Vérifier qu'elle existe dans la base de données)
        $this->assertDatabaseHas('attachments', [
            'id' => $attachment->id,
            'user_id' => $attachment->user_id,
            'attachable_id' => $attachment->attachable_id,
            'attachable_type' => $attachment->attachable_type,
            'mime_type' => $attachment->mime_type,
            'disk' => $attachment->disk,
        ]);
    }

    /** @test */
    public function it_belongs_to_a_user_who_uploaded_it(): void
    {
        // 1. Arrangement
        $user = User::factory()->create();
        $attachment = Attachment::factory()->create(['user_id' => $user->id]);

        // 2. Action & Assertion
        $this->assertInstanceOf(User::class, $attachment->user);
        $this->assertEquals($user->id, $attachment->user->id);
    }

    /** @test */
    public function it_can_track_a_polymorphic_attachable_model_like_a_material(): void
    {
        // 1. Arrangement
        $material = Material::factory()->create();
        $attachment = Attachment::factory()->create([
            'attachable_id' => $material->id,
            'attachable_type' => $material->getMorphClass(),
        ]);

        // 2. Action & Assertion
        // Vérifie la relation polymorphique (attachable)
        $this->assertInstanceOf(Material::class, $attachment->attachable);
        $this->assertEquals($material->id, $attachment->attachable->id);
    }
    
    /** @test */
    public function it_can_track_a_polymorphic_attachable_model_like_a_quote(): void
    {
        // 1. Arrangement
        $quote = Quote::factory()->create();
        $attachment = Attachment::factory()->create([
            'attachable_id' => $quote->id,
            'attachable_type' => $quote->getMorphClass(),
        ]);

        // 2. Action & Assertion
        $this->assertInstanceOf(Quote::class, $attachment->attachable);
        $this->assertEquals($quote->id, $attachment->attachable->id);
    }


    /** @test */
    public function deleting_a_user_cascades_and_deletes_their_attachments_from_db(): void
    {
        // 1. Arrangement
        $user = User::factory()->create();
        $attachment = Attachment::factory()->create(['user_id' => $user->id]);

        $this->assertDatabaseHas('attachments', ['id' => $attachment->id]);

        // 2. Action (Supprimer l'utilisateur)
        $user->delete();

        // 3. Assertion (Vérifier que la pièce jointe est supprimée de la BDD)
        $this->assertDatabaseMissing('attachments', ['id' => $attachment->id]);
    }

    /** @test */
    public function deleting_the_attachable_model_cascades_and_deletes_the_attachment_entry_from_db(): void
    {
        // 1. Arrangement
        $material = Material::factory()->create();
        $attachment = Attachment::factory()->create([
            'attachable_id' => $material->id,
            'attachable_type' => $material->getMorphClass(),
        ]);

        $this->assertDatabaseHas('attachments', ['id' => $attachment->id]);

        // 2. Action (Supprimer le modèle Material)
        $material->delete();

        // 3. Assertion (Vérifier que l'entrée Attachment est supprimée de la BDD)
        $this->assertDatabaseMissing('attachments', ['id' => $attachment->id]);
    }
}
