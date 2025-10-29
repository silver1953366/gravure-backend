<?php

namespace App\Providers;

// Importation de la classe de base pour les Providers
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

// Importation des modèles et des politiques
use App\Models\Quote;
use App\Models\Order; // Ajout du modèle Order
use App\Policies\QuotePolicy;
use App\Policies\OrderPolicy; // Ajout de la politique OrderPolicy

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // Enregistrement de la politique pour le modèle Quote
        Quote::class => QuotePolicy::class,
        
        // IMPORTANT : Enregistrement de la politique pour le modèle Order
        // Nécessaire pour les méthodes view et convert dans OrderController
        Order::class => OrderPolicy::class, 
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // Vous pouvez définir ici d'autres Gates si nécessaire.
    }
}
