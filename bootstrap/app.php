<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\IsController; // <-- Assurez-vous d'importer la classe

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Enregistrement des middlewares globaux, si nécessaire.

        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class, // (Votre AdminMiddleware existant)
            'controller' => IsController::class, // <-- AJOUTEZ CETTE LIGNE POUR ENREGISTRER LE NOUVEAU MIDDLEWARE
        ]);

        // Vous pouvez également ajouter le middleware dans les groupes 'web' ou 'api' si vous voulez qu'il s'applique globalement à toutes les routes de ce groupe, 
        // mais l'alias est préférable pour les protections spécifiques.

    })
    ->withExceptions(function (Exceptions $exceptions) {
        // ...
    })->create();
