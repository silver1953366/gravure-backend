<?php

namespace App\Policies;

use App\Models\Quote;
use App\Models\User;
use Illuminate\Auth\Access\Response; 
use Illuminate\Auth\Access\HandlesAuthorization;

class QuotePolicy
{
    use HandlesAuthorization;

    /**
     * Permet aux administrateurs ET contrôleurs de contourner toutes les vérifications.
     * C'est le "super-pouvoir" des rôles privilégiés.
     */
    public function before(User $user, string $ability): bool|null
    {
        // Les rôles privilégiés (ADMIN et CONTROLLER) peuvent toujours tout faire.
        if ($user->role === User::ROLE_ADMIN || $user->role === User::ROLE_CONTROLLER) {
            return true; 
        }
        return null;
    }

    /**
     * Détermine si l'utilisateur peut lister les devis.
     * Les rôles privilégiés sont gérés par la méthode before().
     */
    public function viewAny(User $user): bool
    {
        // Seuls les clients, admins, et contrôleurs (via before) peuvent voir la liste.
        return $user->role === User::ROLE_CLIENT;
    }

    /**
     * Détermine si l'utilisateur peut voir un devis spécifique (show).
     * Les rôles privilégiés sont gérés par la méthode before().
     */
    public function view(User $user, Quote $quote): bool
    {
        // Seul le Client peut voir ses propres devis ici.
        return $user->id === $quote->user_id;
    }

    /**
     * Détermine si l'utilisateur peut créer un devis.
     */
    public function create(User $user): bool
    {
        return $user->role === User::ROLE_CLIENT;
    }

    /**
     * Détermine si l'utilisateur peut modifier (update) un devis.
     * Les rôles privilégiés sont gérés par before().
     */
    public function update(User $user, Quote $quote): Response
    {
        // Règle 1 : Vérifie la propriété
        if ($user->id !== $quote->user_id) {
            return Response::deny('Vous n\'êtes pas autorisé à modifier ce devis.'); 
        }

        // Règle 2 : Le devis doit être en DRAFT pour être modifiable par le client.
        if ($quote->status !== Quote::STATUS_DRAFT) {
            return Response::deny('Le devis ne peut plus être modifié une fois qu\'il a été soumis ou traité.');
        }

        return Response::allow();
    }
    
    /**
     * Détermine si l'utilisateur peut supprimer (delete) un devis.
     * Les rôles privilégiés sont gérés par before().
     */
    public function delete(User $user, Quote $quote): Response
    {
        // Règle 1 : Vérifie la propriété
        if ($user->id !== $quote->user_id) {
             return Response::deny('Vous ne pouvez supprimer que vos propres devis.');
        }

        // Règle 2 : Seuls les DRAFT sont supprimables par le client
        return $quote->status === Quote::STATUS_DRAFT
            ? Response::allow()
            : Response::deny('Seuls les devis en statut brouillon peuvent être supprimés.');
    }

    /**
     * Détermine si l'utilisateur peut convertir un devis en commande.
     * Les rôles privilégiés sont gérés par before().
     */
    public function convert(User $user, Quote $quote): Response
    {
        // 1. Vérification de la propriété
        if ($user->id !== $quote->user_id) {
            return Response::deny('Vous ne pouvez convertir que vos propres devis en commande.');
        }

        // 2. Vérification que le devis n'a pas déjà été commandé
        if ($quote->order_id !== null) {
            return Response::deny('Ce devis a déjà été converti en commande.');
        }

        // 3. Vérification du statut : Doit être CALCULATED
        // Le client ne peut convertir qu'une fois le prix final fixé.
        if ($quote->status !== Quote::STATUS_CALCULATED) {
             return Response::deny('Le devis n\'a pas encore un prix final fixé (statut non CALCULATED).');
        }
        
        return Response::allow();
    }
}
