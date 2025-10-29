<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrderPolicy
{
    use HandlesAuthorization;

    // Constantes de statut (pour référence, bien qu'elles viennent du modèle Order)
    public const STATUS_PENDING_PAYMENT = 'pending_payment'; 
    public const STATUS_PAID = 'paid';                      
    public const STATUS_PROCESSING = 'processing';          
    public const STATUS_SHIPPED = 'shipped';                
    public const STATUS_COMPLETED = 'completed';            
    public const STATUS_CANCELLED = 'canceled';             

    /**
     * Permet aux administrateurs et contrôleurs de contourner toutes les vérifications.
     */
    public function before(User $user, string $ability): bool|null
    {
        // Les rôles Admin et Controller ont le droit de tout voir ou gérer les commandes
        if ($user->role === User::ROLE_ADMIN || $user->role === User::ROLE_CONTROLLER) {
            return true; 
        }
        return null;
    }

    /**
     * Détermine si l'utilisateur peut voir la liste des commandes (viewAny).
     * Les rôles privilégiés sont gérés par before().
     */
    public function viewAny(User $user): bool
    {
        // Si l'exécution arrive ici, c'est que l'utilisateur n'est NI Admin, NI Controller (before=null).
        return $user->role === User::ROLE_CLIENT;
    }

    /**
     * Détermine si l'utilisateur peut voir une commande spécifique (show).
     * Les rôles privilégiés sont gérés par before().
     */
    public function view(User $user, Order $order): Response
    {
        // Vérifie si l'utilisateur actuel est le propriétaire de la commande.
        return $user->id === $order->user_id
            ? Response::allow()
            : Response::deny('Vous n\'êtes pas autorisé à voir cette commande.');
    }
    
    /**
     * Détermine si l'utilisateur peut créer une commande.
     * Cette méthode ne sera pas utilisée dans le contrôleur, car la création passe
     * par la méthode convertQuoteToOrder et sa propre Policy@convert.
     */
    public function create(User $user): bool
    {
        // On pourrait limiter la création directe d'une commande (via POST /orders)
        // car elle doit passer par un devis. On peut donc la bloquer par défaut.
        return false;
    }


    /**
     * Détermine si l'utilisateur peut modifier (update) une commande.
     * Les rôles privilégiés sont gérés par before().
     * Règle Client : Seul le propriétaire peut modifier l'adresse, et ce, uniquement si la commande
     * n'est pas encore en production (PROCESSING, SHIPPED, COMPLETED).
     */
    public function update(User $user, Order $order): Response
    {
        // 1. Vérifie si l'utilisateur est le propriétaire.
        if ($user->id !== $order->user_id) {
            return Response::deny('Vous n\'êtes pas autorisé à modifier cette commande.');
        }

        // 2. Le client ne peut pas modifier si la commande est déjà en production ou terminée.
        $forbiddenStatuses = [
            Order::STATUS_PROCESSING, 
            Order::STATUS_SHIPPED, 
            Order::STATUS_COMPLETED
        ];

        if (in_array($order->status, $forbiddenStatuses)) {
            return Response::deny('La commande ne peut plus être modifiée une fois la production ou l\'expédition démarrée.');
        }

        // Si le client est le propriétaire et que la commande est PENDING_PAYMENT, PAID ou CANCELED (pour réactivation?), c'est autorisé.
        return Response::allow();
    }

    /**
     * Détermine si l'utilisateur peut annuler (delete/destroy) une commande.
     * Les rôles privilégiés sont gérés par before().
     * Règle Client : Seul le propriétaire peut annuler, et ce, uniquement si la commande
     * n'est pas encore en production (PROCESSING, SHIPPED, COMPLETED).
     */
    public function delete(User $user, Order $order): Response
    {
        // 1. Vérifie si l'utilisateur est le propriétaire.
        if ($user->id !== $order->user_id) {
            return Response::deny('Vous ne pouvez annuler que vos propres commandes.');
        }

        // 2. Le client ne peut pas annuler si la commande est déjà en production ou terminée.
        $forbiddenStatuses = [
            Order::STATUS_PROCESSING, 
            Order::STATUS_SHIPPED, 
            Order::STATUS_COMPLETED
        ];

        if (in_array($order->status, $forbiddenStatuses)) {
            return Response::deny('La commande ne peut plus être annulée une fois la production ou l\'expédition démarrée.');
        }

        // Si le client est le propriétaire et que la commande est dans un statut annulable.
        return Response::allow();
    }
}
